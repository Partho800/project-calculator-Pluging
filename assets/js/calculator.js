/**
 * Frontend Calculator logic for Service Price Calculator (TSPC)
 */

jQuery(document).ready(function($) {
    var $container = $('#tspc-calculator-container');
    if (!$container.length) return;

    // Read discounts and currency from container dataset attributes
    var currency = $container.data('currency') || '৳';
    // Dynamic Discounts
    var dynamicDiscounts = [];
    try {
        var rawDiscounts = $container.attr('data-dynamic-discounts');
        if (rawDiscounts) {
            dynamicDiscounts = JSON.parse(rawDiscounts);
        }
    } catch(e) {}
    
    // Sort descending by required services
    dynamicDiscounts.sort(function(a, b) {
        return b.services - a.services;
    });

    // State
    var state = {
        selectedServices: [],
        subtotal: 0,
        discountPct: 0,
        discountAmount: 0,
        total: 0
    };

    // DOM cache
    var $servicesCheckboxes = $('.tspc-service-checkbox');
    var $selectedPanel = $('#tspc-selected-services-list');
    var $subtotalDisplay = $('#tspc-subtotal-val');
    var $discountDisplay = $('#tspc-discount-val');
    var $discountLabel = $('#tspc-discount-label');
    var $discountRow = $('.discount-row');
    var $totalDisplay = $('#tspc-total-val');
    var $submitBtn = $('#tspc-submit-btn');
    var $quoteForm = $('#tspc-client-quote-form');
    var $clearAllBtn = $('#tspc-clear-all-btn');
    
    var $successOverlay = $('#tspc-overlay-success');
    var $errorOverlay = $('#tspc-overlay-error');

    // Force required checkboxes to be checked and expanded on page load
    $servicesCheckboxes.each(function() {
        var $cb = $(this);
        if ($cb.data('required') == 1 || $cb.attr('data-required') == '1') {
            $cb.prop('checked', true);
            var $container = $cb.closest('.tspc-service-card-container');
            $container.addClass('tspc-expanded');
            var $subWrapper = $container.find('.tspc-sub-services-wrapper');
            if ($subWrapper.length) {
                $subWrapper.show();
                $subWrapper.find('.tspc-sub-service-checkbox').prop('disabled', false);
            }
        }
    });

    // Init price calculations
    calculateCalculator();

    // Expand first service if setting is enabled
    var expandFirst = parseInt($container.attr('data-expand-first')) === 1;
    if (expandFirst) {
        var $firstCard = $('.tspc-service-card-container').first();
        if ($firstCard.length) {
            var $mainCb = $firstCard.find('.tspc-service-checkbox');
            // Only expand if it's not already expanded by a required rule
            if (!$firstCard.hasClass('tspc-expanded')) {
                $firstCard.addClass('tspc-expanded');
                var $subWrapper = $firstCard.find('.tspc-sub-services-wrapper');
                if ($subWrapper.length) {
                    $subWrapper.show();
                    var $subs = $subWrapper.find('.tspc-sub-service-checkbox');
                    if ($mainCb.is(':checked')) {
                        $subs.prop('disabled', false);
                    } else {
                        $subs.prop('disabled', true);
                    }
                }
            }
        }
    }

    // Event listener: Checkbox click (prevent unchecking required services)
    $servicesCheckboxes.on('click', function(e) {
        if ($(this).data('required') == 1 || $(this).attr('data-required') == '1') {
            // Only prevent unchecking (click reverts it if we preventDefault)
            if (!$(this).is(':checked')) {
                e.preventDefault();
            }
        }
    });

    // Event listener: Checkbox change
    $servicesCheckboxes.on('change', function() {
        var $this = $(this);
        var $container = $this.closest('.tspc-service-card-container');
        var $subWrapper = $container.find('.tspc-sub-services-wrapper');
        var $subs = $subWrapper.find('.tspc-sub-service-checkbox');

        if ($this.is(':checked')) {
            $container.addClass('tspc-expanded');
            $subWrapper.slideDown(250);
            $subs.prop('disabled', false);
            var hasDefaults = $subs.filter('[data-default-checked="1"]').length > 0;
            if (hasDefaults) {
                $subs.each(function() {
                    var $sub = $(this);
                    if ($sub.data('default-checked') == 1 || $sub.attr('data-default-checked') == '1') {
                        $sub.prop('checked', true).closest('.tspc-sub-service-item').addClass('tspc-sub-checked');
                        if ($sub.hasClass('tspc-child-sub-checkbox')) {
                            var pIdx = $sub.data('parent-sub-index');
                            $container.find('.tspc-parent-sub-checkbox[data-sub-index="' + pIdx + '"]').prop('checked', true).closest('.tspc-sub-service-item').addClass('tspc-sub-checked');
                        }
                    } else {
                        $sub.prop('checked', false).closest('.tspc-sub-service-item').removeClass('tspc-sub-checked');
                    }
                });

                // Update expand states of child wrappers based on parent default-checked
                $container.find('.tspc-parent-sub-checkbox').each(function() {
                    var $pSub = $(this);
                    var pIdx = $pSub.data('sub-index');
                    var $pLabel = $pSub.closest('.tspc-parent-sub-item');
                    var $childrenWrapper = $container.find('.tspc-sub-children-wrapper[data-parent-sub-index="' + pIdx + '"]');
                    if ($pSub.data('default-checked') == 1 || $pSub.attr('data-default-checked') == '1') {
                        $pLabel.addClass('tspc-sub-expanded');
                        $childrenWrapper.show();
                    } else {
                        $pLabel.removeClass('tspc-sub-expanded');
                        $childrenWrapper.hide();
                    }
                });
            } else {
                $subs.prop('checked', true).closest('.tspc-sub-service-item').addClass('tspc-sub-checked');
                $container.find('.tspc-parent-sub-item').addClass('tspc-sub-expanded');
                $container.find('.tspc-sub-children-wrapper').show();
            }
        } else {
            $container.removeClass('tspc-expanded');
            $subWrapper.slideUp(200);
            $subs.prop('checked', false).prop('disabled', true);
            $subs.closest('.tspc-sub-service-item').removeClass('tspc-sub-checked');
            $container.find('.tspc-parent-sub-item').removeClass('tspc-sub-expanded');
            $container.find('.tspc-sub-children-wrapper').hide();
        }
        calculateCalculator();
    });

    // Event listener for sub-services checkboxes
    $(document).on('click', '.tspc-sub-service-checkbox', function(e) {
        if ($(this).data('default-checked') == 1 || $(this).attr('data-default-checked') == '1') {
            if (!$(this).is(':checked')) {
                e.preventDefault();
            }
        }
    });

    // Event listener: Toggle child sub-services accordion via chevron button
    $(document).on('click', '.tspc-sub-chevron-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var $parentLabel = $btn.closest('.tspc-parent-sub-item');
        var subIndex = $parentLabel.data('sub-index');
        var $container = $parentLabel.closest('.tspc-service-card-container');
        var $childrenWrapper = $container.find('.tspc-sub-children-wrapper[data-parent-sub-index="' + subIndex + '"]');

        if ($parentLabel.hasClass('tspc-sub-expanded') || $childrenWrapper.is(':visible')) {
            $parentLabel.removeClass('tspc-sub-expanded');
            $childrenWrapper.stop(true, true).slideUp(200);
        } else {
            $parentLabel.addClass('tspc-sub-expanded');
            $childrenWrapper.stop(true, true).slideDown(200);
        }
    });

    $(document).on('mousedown pointerdown', '.tspc-sub-chevron-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $(document).on('change', '.tspc-sub-service-checkbox', function() {
        var $this = $(this);
        var $label = $this.closest('.tspc-sub-service-item');
        var $container = $this.closest('.tspc-service-card-container');
        var $mainCheckbox = $container.find('.tspc-service-checkbox');

        // Parent to children sync: If parent is unchecked, uncheck its children and collapse if not default ON
        if ($this.hasClass('tspc-parent-sub-checkbox')) {
            var subIndex = $this.data('sub-index');
            var $childSubs = $container.find('.tspc-child-sub-checkbox[data-parent-sub-index="' + subIndex + '"]');
            var $childrenWrapper = $container.find('.tspc-sub-children-wrapper[data-parent-sub-index="' + subIndex + '"]');
            if (!$this.is(':checked')) {
                $childSubs.prop('checked', false).closest('.tspc-sub-service-item').removeClass('tspc-sub-checked');
                if ($this.data('default-checked') != 1 && $this.attr('data-default-checked') != '1') {
                    $label.removeClass('tspc-sub-expanded');
                    $childrenWrapper.slideUp(200);
                }
            } else {
                $label.addClass('tspc-sub-expanded');
                $childrenWrapper.slideDown(200);
            }
        }

        // Child to parent sync: If child is checked, ensure parent sub-service is checked
        if ($this.hasClass('tspc-child-sub-checkbox') && $this.is(':checked')) {
            var parentSubIndex = $this.data('parent-sub-index');
            var $parentSub = $container.find('.tspc-parent-sub-checkbox[data-sub-index="' + parentSubIndex + '"]');
            if (!$parentSub.is(':checked')) {
                $parentSub.prop('checked', true).closest('.tspc-sub-service-item').addClass('tspc-sub-checked');
            }
        }

        if ($this.is(':checked')) {
            $label.addClass('tspc-sub-checked');
            if (!$mainCheckbox.is(':checked')) {
                $mainCheckbox.prop('checked', true);
                $container.addClass('tspc-expanded');
            }
        } else {
            $label.removeClass('tspc-sub-checked');
            var $checkedSubs = $container.find('.tspc-sub-service-checkbox:checked');
            if ($checkedSubs.length === 0 && $mainCheckbox.is(':checked')) {
                if ($mainCheckbox.data('required') != 1 && $mainCheckbox.attr('data-required') != '1') {
                    $mainCheckbox.prop('checked', false);
                    var isFirstCard = $container.is($('.tspc-service-card-container').first());
                    if (!(expandFirst && isFirstCard)) {
                        $container.removeClass('tspc-expanded');
                        $container.find('.tspc-sub-services-wrapper').slideUp(200);
                    }
                }
            }
        }
        calculateCalculator();
    });

    // Event listener: Clear All click
    $clearAllBtn.on('click', function(e) {
        e.preventDefault();
        $servicesCheckboxes.each(function() {
            var $cb = $(this);
            if ($cb.data('required') != 1 && $cb.attr('data-required') != '1') {
                $cb.prop('checked', false).trigger('change');
            } else {
                var $container = $cb.closest('.tspc-service-card-container');
                var $subs = $container.find('.tspc-sub-service-checkbox');
                $subs.each(function() {
                    var $sub = $(this);
                    if ($sub.data('default-checked') == 1 || $sub.attr('data-default-checked') == '1') {
                        $sub.prop('checked', true).closest('.tspc-sub-service-item').addClass('tspc-sub-checked');
                    } else {
                        $sub.prop('checked', false).closest('.tspc-sub-service-item').removeClass('tspc-sub-checked');
                    }
                });
            }
        });
        calculateCalculator();
    });

    // Subroutine: Math & UI updates
    function calculateCalculator() {
        state.selectedServices = [];
        state.subtotal = 0;

        // Dynamic Card Price Display updates
        $('.tspc-service-card-container').each(function() {
            var $container = $(this);
            var $mainCheckbox = $container.find('.tspc-service-checkbox');
            var $priceTag = $container.find('.tspc-service-price-tag');
            var $priceTagDisplay = $container.find('.tspc-price-tag-display');
            var $allPkgLabel = $container.find('.tspc-all-pkg-label');

            var basePrice = parseFloat($priceTag.data('base-price')) || 0;

            var $allSubs = $container.find('.tspc-sub-service-checkbox');
            var totalSubsCount = $allSubs.length;

            if ($mainCheckbox.is(':checked')) {
                if (totalSubsCount > 0) {
                    var $checkedSubs = $container.find('.tspc-sub-service-checkbox:checked');
                    var checkedSubsCount = $checkedSubs.length;

                    if (checkedSubsCount === totalSubsCount) {
                        $priceTagDisplay.text(basePrice.toLocaleString());
                        $allPkgLabel.show();
                    } else {
                        var selectedSubsSum = 0;
                        $checkedSubs.each(function() {
                            selectedSubsSum += parseFloat($(this).data('price')) || 0;
                        });
                        $priceTagDisplay.text(selectedSubsSum.toLocaleString());
                        $allPkgLabel.hide();
                    }
                } else {
                    $priceTagDisplay.text(basePrice.toLocaleString());
                    $allPkgLabel.hide();
                }
            } else {
                // If unchecked, show Base Price
                $priceTagDisplay.text(basePrice.toLocaleString());
                $allPkgLabel.hide();
            }
        });

        var maxPackageDiscount = 0;

        // Loop over checked items
        $servicesCheckboxes.each(function() {
            var $this = $(this);
            if ($this.is(':checked')) {
                var parentId = $this.val();
                var title = $this.data('title');
                var price = parseFloat($this.data('price')) || 0;

                var $container = $this.closest('.tspc-service-card-container');
                var $priceTag = $container.find('.tspc-service-price-tag');
                var discPercent = parseFloat($priceTag.data('discount-percent')) || 0;

                var $allSubs = $container.find('.tspc-sub-service-checkbox');
                var totalSubsCount = $allSubs.length;

                if (totalSubsCount > 0) {
                    var $checkedSubs = $container.find('.tspc-sub-service-checkbox:checked');
                    var checkedSubsCount = $checkedSubs.length;

                    if (checkedSubsCount === totalSubsCount) {
                        // If all are selected, show only the main price
                        var subTitles = [];
                        $checkedSubs.each(function() {
                            subTitles.push($(this).data('title'));
                        });

                        state.selectedServices.push({
                            id: parentId,
                            title: title + ' (All Package Price)',
                            displayTitle: title + ' (All Package Price)',
                            price: price,
                            isAllPackage: true,
                            packageDiscount: discPercent
                        });
                        state.subtotal += price;

                        if (discPercent > maxPackageDiscount) {
                            maxPackageDiscount = discPercent;
                        }
                    } else {
                        // If only some or none are selected, show the sum of selected sub-services
                        var subTitles = [];
                        var combinedPrice = 0;
                        $checkedSubs.each(function() {
                            var $sub = $(this);
                            subTitles.push($sub.data('title'));
                            combinedPrice += parseFloat($sub.data('price')) || 0;
                        });

                        var fullTitle = title;
                        var displayTitle = title;
                        if (subTitles.length > 0) {
                            fullTitle += ' (' + subTitles.join(', ') + ')';

                            // Show 2-3 items and '+X more' for the rest
                            if (subTitles.length > 3) {
                                var first3Text = subTitles.slice(0, 3).join(', ');
                                var showCount = (first3Text.length <= 28) ? 3 : 2;
                                var visibleList = subTitles.slice(0, showCount).join(', ');
                                var moreCount = subTitles.length - showCount;
                                displayTitle += ' (' + visibleList + ' +' + moreCount + ' more)';
                            } else {
                                displayTitle = fullTitle;
                            }
                        }

                        state.selectedServices.push({
                            id: parentId,
                            title: fullTitle,
                            displayTitle: displayTitle,
                            price: combinedPrice
                        });
                        state.subtotal += combinedPrice;
                    }
                } else {
                    // No sub-services config
                    state.selectedServices.push({
                        id: parentId,
                        title: title,
                        displayTitle: title,
                        price: price
                    });
                    state.subtotal += price;
                }
            }
        });

        // Count total selected items (if main service has sub-services, count selected sub-services; else count 1 for the main service)
        var count = 0;
        $servicesCheckboxes.each(function() {
            var $this = $(this);
            if ($this.is(':checked')) {
                var $container = $this.closest('.tspc-service-card-container');
                var $allSubs = $container.find('.tspc-sub-service-checkbox');
                if ($allSubs.length > 0) {
                    count += $container.find('.tspc-sub-service-checkbox:checked').length;
                } else {
                    count += 1;
                }
            }
        });

        // Determine Discount Tier Dynamically
        state.discountPct = 0;
        for (var i = 0; i < dynamicDiscounts.length; i++) {
            if (count >= dynamicDiscounts[i].services) {
                state.discountPct = parseFloat(dynamicDiscounts[i].discount) || 0;
                break;
            }
        }

        // If package discount is configured and all sub-services are selected, apply package discount
        if (maxPackageDiscount > 0) {
            state.discountPct = Math.max(state.discountPct, maxPackageDiscount);
        }

        // Calculations
        state.discountAmount = state.subtotal * (state.discountPct / 100);
        state.total = state.subtotal - state.discountAmount;

        // Render Cart Panel
        renderSelectedList();

        // Update Pricing Layout
        animatePriceValue($subtotalDisplay, state.subtotal);
        
        if (state.discountAmount > 0) {
            if (maxPackageDiscount > 0 && state.discountPct === maxPackageDiscount) {
                $discountLabel.html('total for discount ' + state.discountPct + '%');
            } else {
                $discountLabel.html('Discount (' + state.discountPct + '%)');
            }
            animatePriceValue($discountDisplay, state.discountAmount);
            $discountRow.slideDown(200);
        } else {
            $discountRow.slideUp(200);
            $discountDisplay.text('0');
        }

        animatePriceValue($totalDisplay, state.total);

        // Toggle submit button state and clear-all button state
        if (count > 0) {
            $submitBtn.prop('disabled', false);
            $clearAllBtn.fadeIn(150);
        } else {
            $submitBtn.prop('disabled', true);
            $clearAllBtn.fadeOut(150);
        }
    }

    // Render right-side cart
    function renderSelectedList() {
        $selectedPanel.empty();

        if (state.selectedServices.length === 0) {
            $selectedPanel.addClass('tspc-selected-items-empty');
            $selectedPanel.html('<p>No services selected.</p>');
            return;
        }

        $selectedPanel.removeClass('tspc-selected-items-empty');
        var listHtml = '<ul class="tspc-cart-items-list">';
        
        state.selectedServices.forEach(function(item) {
            listHtml += '<li data-id="' + item.id + '">';
            listHtml += '  <div class="tspc-cart-item-info">';
            listHtml += '    <span class="tspc-cart-item-title" title="' + escapeHtml(item.title) + '">' + escapeHtml(item.displayTitle || item.title) + '</span>';
            if (item.isAllPackage && item.packageDiscount > 0) {
                listHtml += '    <div class="tspc-cart-item-sub-disc">total for discount ' + item.packageDiscount + '%</div>';
            }
            listHtml += '  </div>';
            listHtml += '  <div class="tspc-cart-item-right">';
            listHtml += '    <span class="tspc-cart-item-price">' + currency + item.price.toLocaleString() + '</span>';
            
            // Only show remove button if NOT required!
            var isRequired = false;
            var $matchingCheckbox = $servicesCheckboxes.filter('[value="' + item.id + '"]');
            if ($matchingCheckbox.length && ($matchingCheckbox.data('required') == 1 || $matchingCheckbox.attr('data-required') == '1')) {
                isRequired = true;
            }
            if (!isRequired) {
                listHtml += '    <button type="button" class="tspc-remove-cart-item" title="Remove service"><span class="dashicons dashicons-no-alt"></span></button>';
            }
            listHtml += '  </div>';
            listHtml += '</li>';
        });

        listHtml += '</ul>';
        $selectedPanel.html(listHtml);
    }

    // Animated counting increments
    function animatePriceValue($el, targetValue) {
        var current = parseFloat($el.text().replace(/,/g, '')) || 0;
        var start = current;
        var end = targetValue;
        var duration = 400; // ms
        var startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = timestamp - startTime;
            var fraction = Math.min(progress / duration, 1);
            
            // Ease out quad
            var easeVal = fraction * (2 - fraction);
            var currentVal = Math.floor(start + (end - start) * easeVal);
            
            $el.text(currentVal.toLocaleString());

            if (fraction < 1) {
                requestAnimationFrame(step);
            } else {
                $el.text(Math.floor(end).toLocaleString());
            }
        }
        
        requestAnimationFrame(step);
    }

    // Handle AJAX quote submission
    $quoteForm.on('submit', function(e) {
        e.preventDefault();

        var $nameEl  = $('#tspc-name');
        var $emailEl = $('#tspc-email');
        var $phoneEl = $('#tspc-phone');
        var $msgEl   = $('#tspc-msg');

        var clientName  = $nameEl.length ? $nameEl.val().trim() : '';
        var clientEmail = $emailEl.length ? $emailEl.val().trim() : '';
        var clientPhone = $phoneEl.length ? $phoneEl.val().trim() : '';
        var clientMsg   = $msgEl.length ? $msgEl.val().trim() : '';

        // Validation for visible fields
        if ($nameEl.length && $nameEl.prop('required') && !clientName) {
            $nameEl.focus();
            alert('Please fill out your Full Name. This field is required.');
            return;
        }
        if ($phoneEl.length && $phoneEl.prop('required') && !clientPhone) {
            $phoneEl.focus();
            alert('Please fill out your Phone Number. This field is required.');
            return;
        }
        if ($emailEl.length && $emailEl.prop('required') && !clientEmail) {
            $emailEl.focus();
            alert('Please fill out your Email Address. This field is required.');
            return;
        }
        if ($msgEl.length && $msgEl.prop('required') && !clientMsg) {
            $msgEl.focus();
            alert('Please fill out your Project Details / Message. This field is required.');
            return;
        }

        if (state.selectedServices.length === 0) {
            alert('Please select at least one service.');
            return;
        }

        $submitBtn.prop('disabled', true).css({'background-color': '', 'color': ''}).text('Sending Request...');

        var ajaxData = {
            action: 'tspc_submit_estimate',
            tspc_nonce: $('#tspc_nonce').val(),
            name: clientName,
            email: clientEmail,
            phone: clientPhone,
            message: clientMsg,
            subtotal: state.subtotal,
            discount: state.discountAmount,
            total: state.total,
            services: JSON.stringify(state.selectedServices)
        };

        $.ajax({
            url: tspc_ajax_obj.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    $submitBtn.prop('disabled', false)
                              .css({'background-color': '#10b981', 'color': '#ffffff', 'transition': 'all 0.3s ease'})
                              .html('<span class="dashicons dashicons-saved"></span> Submit Successful');

                    // Auto-reset form fields and selections
                    $quoteForm[0].reset();
                    $servicesCheckboxes.each(function() {
                        var $cb = $(this);
                        if ($cb.data('required') != 1 && $cb.attr('data-required') != '1') {
                            $cb.prop('checked', false).trigger('change');
                        } else {
                            $cb.prop('checked', true).trigger('change');
                        }
                    });

                    setTimeout(function() {
                        $submitBtn.css({'background-color': '', 'color': ''}).text('Send Cost Inquiry');
                    }, 4000);
                } else {
                    var errMsg = 'Failed to send request. Please try again.';
                    if (response && response.data && response.data.message) {
                        errMsg = response.data.message;
                    }
                    $submitBtn.prop('disabled', false)
                              .css({'background-color': '#ef4444', 'color': '#ffffff', 'transition': 'all 0.3s ease'})
                              .html('<span class="dashicons dashicons-no-alt"></span> ' + errMsg);
                    
                    // Revert to original state after 4 seconds
                    setTimeout(function() {
                        $submitBtn.css({'background-color': '', 'color': ''}).text('Send Cost Inquiry');
                    }, 4000);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false)
                          .css({'background-color': '#ef4444', 'color': '#ffffff', 'transition': 'all 0.3s ease'})
                          .html('<span class="dashicons dashicons-no-alt"></span> Failed to send');
                
                setTimeout(function() {
                    $submitBtn.css({'background-color': '', 'color': ''}).text('Send Cost Inquiry');
                }, 4000);
            }
        });
    });

    // Handle individual service removal from cart list
    $selectedPanel.on('click', '.tspc-remove-cart-item', function(e) {
        e.preventDefault();
        var id = String($(this).closest('li').data('id'));
        if (id.indexOf('-') !== -1) {
            var parts = id.split('-');
            var parentId = parts[0];
            var subIndex = parts[1];
            $('.tspc-sub-service-checkbox[data-parent-id="' + parentId + '"][value="' + subIndex + '"]').prop('checked', false).trigger('change');
        } else {
            $servicesCheckboxes.filter('[value="' + id + '"]').prop('checked', false).trigger('change');
        }
    });

    // Reset overlay
    $('#tspc-reset-calculator').on('click', function() {
        $successOverlay.hide();
        $quoteForm[0].reset();
        $servicesCheckboxes.each(function() {
            var $cb = $(this);
            if ($cb.data('required') != 1 && $cb.attr('data-required') != '1') {
                $cb.prop('checked', false).trigger('change');
            } else {
                $cb.prop('checked', true).trigger('change');
            }
        });
    });

    $('#tspc-dismiss-error').on('click', function() {
        $errorOverlay.hide();
    });

    // Helpers
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
