/**
 * Frontend Calculator logic for Service Price Calculator (TSPC)
 */

jQuery(document).ready(function($) {
    var $container = $('#tspc-calculator-container');
    if (!$container.length) return;

    // Read discounts and currency from container dataset attributes
    var currency = $container.data('currency') || '৳';
    
    var discount2 = parseFloat($container.data('discount-2'));
    if (isNaN(discount2)) discount2 = 0;
    
    var discount3 = parseFloat($container.data('discount-3'));
    if (isNaN(discount3)) discount3 = 0;
    
    var discount4 = parseFloat($container.data('discount-4'));
    if (isNaN(discount4)) discount4 = 0;

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

    // Init price calculations
    calculateCalculator();

    // Expand first service if setting is enabled
    var expandFirst = parseInt($container.attr('data-expand-first')) === 1;
    if (expandFirst) {
        var $firstCard = $('.tspc-service-card-container').first();
        if ($firstCard.length) {
            $firstCard.addClass('tspc-expanded');
            var $subWrapper = $firstCard.find('.tspc-sub-services-wrapper');
            if ($subWrapper.length) {
                $subWrapper.show();
                var $subs = $subWrapper.find('.tspc-sub-service-checkbox');
                $subs.prop('disabled', false); // Enable so they can be clicked
            }
        }
    }

    // Event listener: Checkbox change
    $servicesCheckboxes.on('change', function() {
        var $this = $(this);
        var $container = $this.closest('.tspc-service-card-container');
        var $subWrapper = $container.find('.tspc-sub-services-wrapper');
        var $subs = $subWrapper.find('.tspc-sub-service-checkbox');

        if ($this.is(':checked')) {
            $container.addClass('tspc-expanded');
            $subWrapper.slideDown(250);
            $subs.prop('disabled', false).prop('checked', true); // Auto select all nested sub-services
            $subs.closest('.tspc-sub-service-item').addClass('tspc-sub-checked');
        } else {
            $container.removeClass('tspc-expanded');
            $subWrapper.slideUp(200);
            $subs.prop('checked', false).prop('disabled', true);
            $subs.closest('.tspc-sub-service-item').removeClass('tspc-sub-checked');
        }
        calculateCalculator();
    });

    // Event listener for sub-services checkboxes
    $(document).on('change', '.tspc-sub-service-checkbox', function() {
        var $this = $(this);
        var $label = $this.closest('.tspc-sub-service-item');
        var $container = $this.closest('.tspc-service-card-container');
        var $mainCheckbox = $container.find('.tspc-service-checkbox');

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
                $mainCheckbox.prop('checked', false);
                var isFirstCard = $container.is($('.tspc-service-card-container').first());
                if (!(expandFirst && isFirstCard)) {
                    $container.removeClass('tspc-expanded');
                    $container.find('.tspc-sub-services-wrapper').slideUp(200);
                }
            }
        }
        calculateCalculator();
    });

    // Event listener: Clear All click
    $clearAllBtn.on('click', function(e) {
        e.preventDefault();
        $servicesCheckboxes.prop('checked', false).trigger('change');
    });

    // Subroutine: Math & UI updates
    function calculateCalculator() {
        state.selectedServices = [];
        state.subtotal = 0;

        // Dynamic Card Price Display updates
        $('.tspc-service-card-container').each(function() {
            var $container = $(this);
            var $mainCheckbox = $container.find('.tspc-service-checkbox');
            var $priceTagDisplay = $container.find('.tspc-price-tag-display');
            var basePrice = parseFloat($container.find('.tspc-service-price-tag').data('base-price')) || 0;

            var $allSubs = $container.find('.tspc-sub-service-checkbox');
            var totalSubsCount = $allSubs.length;

            if ($mainCheckbox.is(':checked')) {
                if (totalSubsCount > 0) {
                    var $checkedSubs = $container.find('.tspc-sub-service-checkbox:checked');
                    var checkedSubsCount = $checkedSubs.length;

                    if (checkedSubsCount === totalSubsCount) {
                        $priceTagDisplay.text(basePrice.toLocaleString());
                    } else {
                        var selectedSubsSum = 0;
                        $checkedSubs.each(function() {
                            selectedSubsSum += parseFloat($(this).data('price')) || 0;
                        });
                        $priceTagDisplay.text(selectedSubsSum.toLocaleString());
                    }
                } else {
                    $priceTagDisplay.text(basePrice.toLocaleString());
                }
            } else {
                // If unchecked, show Base Price
                $priceTagDisplay.text(basePrice.toLocaleString());
            }
        });

        // Loop over checked items
        $servicesCheckboxes.each(function() {
            var $this = $(this);
            if ($this.is(':checked')) {
                var parentId = $this.val();
                var title = $this.data('title');
                var price = parseFloat($this.data('price')) || 0;

                var $container = $this.closest('.tspc-service-card-container');
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
                            title: title + ' (' + subTitles.join(', ') + ')',
                            price: price
                        });
                        state.subtotal += price;
                    } else {
                        // If only some or none are selected, show the sum of selected sub-services
                        var subTitles = [];
                        var combinedPrice = 0;
                        $checkedSubs.each(function() {
                            var $sub = $(this);
                            subTitles.push($sub.data('title'));
                            combinedPrice += parseFloat($sub.data('price')) || 0;
                        });

                        var displayTitle = title;
                        if (subTitles.length > 0) {
                            displayTitle += ' (' + subTitles.join(', ') + ')';
                        }

                        state.selectedServices.push({
                            id: parentId,
                            title: displayTitle,
                            price: combinedPrice
                        });
                        state.subtotal += combinedPrice;
                    }
                } else {
                    // No sub-services config
                    state.selectedServices.push({
                        id: parentId,
                        title: title,
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

        // Determine Discount Tier
        if (count === 2) {
            state.discountPct = discount2;
        } else if (count === 3) {
            state.discountPct = discount3;
        } else if (count >= 4) {
            state.discountPct = discount4;
        } else {
            state.discountPct = 0;
        }

        // Calculations
        state.discountAmount = state.subtotal * (state.discountPct / 100);
        state.total = state.subtotal - state.discountAmount;

        // Render Cart Panel
        renderSelectedList();

        // Update Pricing Layout
        animatePriceValue($subtotalDisplay, state.subtotal);
        
        if (state.discountAmount > 0) {
            $discountLabel.html('Discount (' + state.discountPct + '%)');
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
            listHtml += '  <span class="tspc-cart-item-title">' + escapeHtml(item.title) + '</span>';
            listHtml += '  <div class="tspc-cart-item-right">';
            listHtml += '    <span class="tspc-cart-item-price">' + currency + item.price.toLocaleString() + '</span>';
            listHtml += '    <button type="button" class="tspc-remove-cart-item" title="Remove service"><span class="dashicons dashicons-no-alt"></span></button>';
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
        if ($nameEl.length && !clientName) {
            alert('Please fill out your Name.');
            return;
        }
        if ($phoneEl.length && !clientPhone) {
            alert('Please fill out your Phone Number.');
            return;
        }
        if ($emailEl.length && !clientEmail) {
            alert('Please fill out your Email Address.');
            return;
        }

        if (state.selectedServices.length === 0) {
            alert('Please select at least one service.');
            return;
        }

        $submitBtn.prop('disabled', true).text('Sending Request...');

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
                $submitBtn.prop('disabled', false).text('Send Proposal Inquiry');
                if (response && response.success) {
                    var thanksText = 'Thank you! We have logged your request of <strong>' + currency + state.total.toLocaleString() + '</strong>.';
                    if (clientName) {
                        thanksText = 'Thank you <strong>' + escapeHtml(clientName) + '</strong>! We have logged your request of <strong>' + currency + state.total.toLocaleString() + '</strong>.';
                    }
                    var contactInfo = [];
                    if (clientPhone) contactInfo.push('<strong>' + escapeHtml(clientPhone) + '</strong>');
                    if (clientEmail) contactInfo.push('<strong>' + escapeHtml(clientEmail) + '</strong>');
                    if (contactInfo.length > 0) {
                        thanksText += '<br>Our team will contact you on ' + contactInfo.join(' or ') + ' soon.';
                    }
                    $('.tspc-overlay-client-msg').html(thanksText);
                    $successOverlay.css('display', 'flex');
                } else {
                    $errorOverlay.css('display', 'flex');
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text('Send Proposal Inquiry');
                $errorOverlay.css('display', 'flex');
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
        $servicesCheckboxes.prop('checked', false).trigger('change');
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
