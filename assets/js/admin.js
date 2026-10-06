/**
 * Admin Panel Javascript for Techsoul Project Calculator (TSPC)
 */

jQuery(document).ready(function($) {
    // ── Initialize WordPress Color Picker ──────────────────────────────
    if ( $.fn.wpColorPicker ) {
        $('.tspc-color-picker').wpColorPicker({
            change: function( event, ui ) {
                var hex = ui.color.toString();
                $('#tspc-preview-chip').css('background', hex);
            },
            clear: function() {
                $('#tspc-preview-chip').css('background', '#6366f1');
            }
        });
    }
    // ──────────────────────────────────────────────────────────────────
    var $modal = $('#tspc-lead-modal');
    var $modalBody = $('#tspc-modal-body');
    var $modalLeadId = $('#tspc-modal-lead-id');
    var $modalStatus = $('#tspc-modal-status');

    // Handle view details click
    $('.tspc-view-details').on('click', function() {
        var inquiryData = $(this).data('inquiry');
        if (!inquiryData) return;

        // Parse services list
        var services = [];
        try {
            services = JSON.parse(inquiryData.services);
        } catch(e) {
            services = inquiryData.services;
        }
        
        var servicesHtml = '<ul style="margin: 0; padding-left: 20px; list-style-type: disc;">';
        if ($.isArray(services)) {
            services.forEach(function(s) {
                servicesHtml += '<li>' + escapeHtml(s.title) + ' - <strong>৳' + parseFloat(s.price).toLocaleString() + '</strong></li>';
            });
        } else {
            servicesHtml += '<li>' + escapeHtml(inquiryData.services) + '</li>';
        }
        servicesHtml += '</ul>';

        // Format dates
        var date = new Date(inquiryData.created_at);
        var formattedDate = date.toLocaleString();

        // Build HTML
        var html = '<div class="tspc-modal-header-meta"><span><i class="dashicons dashicons-calendar-alt"></i> Submitted: ' + formattedDate + '</span></div>';

        html += '<div class="tspc-modal-body-content">';
        
        // Client Card
        html += '<div class="tspc-modal-card">';
        html += '<h3>Client Information</h3>';
        html += '<div class="tspc-modal-card-row"><span>Name:</span> <strong>' + escapeHtml(inquiryData.name) + '</strong></div>';
        if (inquiryData.email && inquiryData.email !== '') {
            html += '<div class="tspc-modal-card-row"><span>Email:</span> <a href="mailto:' + inquiryData.email + '">' + escapeHtml(inquiryData.email) + '</a></div>';
        }
        if (inquiryData.phone && inquiryData.phone !== '') {
            html += '<div class="tspc-modal-card-row"><span>Phone:</span> <a href="tel:' + escapeHtml(inquiryData.phone) + '">' + escapeHtml(inquiryData.phone) + '</a></div>';
        }
        html += '</div>';

        // Services Card
        html += '<div class="tspc-modal-card">';
        html += '<h3>Selected Services</h3>';
        html += '<div class="tspc-modal-services-list">' + servicesHtml + '</div>';
        html += '</div>';

        // Pricing Card
        html += '<div class="tspc-modal-card tspc-pricing-card">';
        html += '<div class="tspc-modal-card-row"><span>Subtotal:</span> <strong>৳' + parseFloat(inquiryData.subtotal).toLocaleString() + '</strong></div>';
        html += '<div class="tspc-modal-card-row"><span>Discount:</span> <strong>-৳' + parseFloat(inquiryData.discount).toLocaleString() + '</strong></div>';
        html += '<div class="tspc-modal-card-row tspc-total-row"><span>Total Price:</span> <strong>৳' + parseFloat(inquiryData.total).toLocaleString() + '</strong></div>';
        html += '</div>';

        if (inquiryData.message && inquiryData.message !== '') {
            html += '<div class="tspc-modal-card tspc-msg-card">';
            html += '<h3>Message / Notes</h3>';
            html += '<pre>' + escapeHtml(inquiryData.message) + '</pre>';
            html += '</div>';
        }

        html += '</div>';

        $modalBody.html(html);
        $modalLeadId.val(inquiryData.id);
        $modalStatus.val(inquiryData.status);
        
        $modal.css('display', 'flex');
    });

    // Close modal
    $('.tspc-close-modal').on('click', function() {
        $modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == $modal[0]) {
            $modal.hide();
        }
    });

    // Helpers
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // ── Icon Picker Logic ──────────────────────────────────────────────
    var commonDashicons = [
        'dashicons-admin-site', 'dashicons-admin-home', 'dashicons-admin-users', 'dashicons-admin-settings', 
        'dashicons-admin-appearance', 'dashicons-admin-plugins', 'dashicons-admin-comments', 'dashicons-admin-links', 
        'dashicons-admin-media', 'dashicons-admin-page', 'dashicons-admin-post', 'dashicons-admin-tools',
        'dashicons-store', 'dashicons-cart', 'dashicons-chart-bar', 'dashicons-chart-pie', 'dashicons-chart-line', 
        'dashicons-money', 'dashicons-money-alt', 'dashicons-portfolio', 'dashicons-awards', 'dashicons-businessman',
        'dashicons-email', 'dashicons-email-alt', 'dashicons-phone', 'dashicons-megaphone', 'dashicons-chat', 
        'dashicons-format-chat', 'dashicons-share', 'dashicons-rss', 'dashicons-desktop', 'dashicons-laptop', 
        'dashicons-tablet', 'dashicons-smartphone', 'dashicons-camera', 'dashicons-video-alt', 'dashicons-video-alt2', 
        'dashicons-images-alt', 'dashicons-playlist-video', 'dashicons-microphone', 'dashicons-editor-code', 
        'dashicons-edit', 'dashicons-welcome-write-blog', 'dashicons-art', 'dashicons-color-picker', 'dashicons-hammer', 
        'dashicons-shield', 'dashicons-clock', 'dashicons-calendar', 'dashicons-location', 'dashicons-lightbulb', 
        'dashicons-star-filled', 'dashicons-cloud', 'dashicons-database', 'dashicons-networking', 'dashicons-update', 
        'dashicons-lock', 'dashicons-privacy', 'dashicons-yes', 'dashicons-no', 'dashicons-plus', 'dashicons-minus',
        'dashicons-visibility', 'dashicons-hidden', 'dashicons-search', 'dashicons-share-alt', 'dashicons-undo', 
        'dashicons-redo', 'dashicons-download', 'dashicons-upload', 'dashicons-category', 'dashicons-tag',
        'dashicons-heart', 'dashicons-smiley', 'dashicons-thumbs-up', 'dashicons-thumbs-down', 'dashicons-warning'
    ];

    var $iconGrid = $('#tspc-icon-picker-grid');
    var $iconInput = $('#icon');
    var $iconPreview = $('#tspc-icon-preview-box');
    var $iconDropdown = $('#tspc-icon-picker-dropdown');
    var $iconSearch = $('#tspc-icon-search');
    var $iconSelectBtn = $('#tspc-select-icon-btn');

    if ($iconGrid.length) {
        // Function to populate grid
        function populateIconGrid(filterText) {
            $iconGrid.empty();
            var currentIcon = $iconInput.val();
            
            commonDashicons.forEach(function(icon) {
                if (filterText && icon.indexOf(filterText.toLowerCase()) === -1) {
                    return; // skip if doesn't match filter
                }
                
                var activeClass = (icon === currentIcon) ? ' active' : '';
                var $btn = $('<button type="button" class="tspc-icon-btn' + activeClass + '" data-icon="' + icon + '" title="' + icon + '"><span class="dashicons ' + icon + '"></span></button>');
                
                $btn.on('click', function(e) {
                    e.preventDefault();
                    var selected = $(this).data('icon');
                    $iconInput.val(selected);
                    $iconPreview.html('<span class="dashicons ' + selected + '"></span>');
                    $iconGrid.find('.tspc-icon-btn').removeClass('active');
                    $(this).addClass('active');
                    $iconDropdown.hide();
                });
                
                $iconGrid.append($btn);
            });

            if ($iconGrid.children().length === 0) {
                $iconGrid.html('<div style="grid-column: 1/-1; padding: 20px 0; text-align: center; color: #94a3b8; font-size: 13px;">No icons found</div>');
            }
        }

        // Initialize grid
        populateIconGrid();

        // Toggle dropdown
        $iconSelectBtn.add($iconPreview).on('click', function(e) {
            e.stopPropagation();
            $iconDropdown.toggle();
            if ($iconDropdown.is(':visible')) {
                $iconSearch.val('').focus();
                populateIconGrid();
            }
        });

        // Prevent click inside dropdown from closing it
        $iconDropdown.on('click', function(e) {
            e.stopPropagation();
        });

        // Search/Filter
        $iconSearch.on('input', function() {
            var val = $(this).val();
            populateIconGrid(val);
        });

        // Close on clicking outside
        $(document).on('click', function() {
            $iconDropdown.hide();
        });
    }

    // ── Sub-services dynamic rows in admin ─────────────────────────────
    var $subContainer = $('#tspc-sub-services-container');
    var $addSubBtn = $('#tspc-add-sub-btn');
    if ($subContainer.length && $addSubBtn.length) {
        var subIndex = $subContainer.find('.tspc-sub-service-group').length || $subContainer.find('.tspc-sub-service-row').length;

        // Add parent sub-service
        $addSubBtn.on('click', function(e) {
            e.preventDefault();
            var html = '<div class="tspc-sub-service-group" data-index="' + subIndex + '">' +
                       '  <div class="tspc-sub-service-row">' +
                       '    <input type="text" name="sub_services[' + subIndex + '][title]" placeholder="Sub-service Title">' +
                       '    <input type="number" name="sub_services[' + subIndex + '][price]" placeholder="Price">' +
                       '    <label class="tspc-sub-default-label">' +
                       '      <input type="checkbox" name="sub_services[' + subIndex + '][default_checked]" value="1">' +
                       '      Default ON' +
                       '    </label>' +
                       '    <button type="button" class="button tspc-add-child-sub-btn" title="Add child item under this sub-service">' +
                       '      <span class="dashicons dashicons-plus"></span> Add Sub-item' +
                       '    </button>' +
                       '    <button type="button" class="button tspc-remove-sub-btn" title="Delete this sub-service"><span class="dashicons dashicons-trash"></span></button>' +
                       '  </div>' +
                       '  <div class="tspc-sub-children-container"></div>' +
                       '</div>';
            $subContainer.append(html);
            subIndex++;
        });

        // Add child sub-service
        $subContainer.on('click', '.tspc-add-child-sub-btn', function(e) {
            e.preventDefault();
            var $group = $(this).closest('.tspc-sub-service-group');
            var parentIndex = $group.attr('data-index');
            var $childrenContainer = $group.find('.tspc-sub-children-container');
            var childIndex = $childrenContainer.find('.tspc-child-sub-row').length;

            var arrowSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v8a3 3 0 0 0 3 3h10"></path><polyline points="15 10 19 14 15 18"></polyline></svg>';

            var childHtml = '<div class="tspc-child-sub-row">' +
                            '  <span class="tspc-child-arrow-indicator" title="Child sub-service">' + arrowSvg + '</span>' +
                            '  <input type="text" name="sub_services[' + parentIndex + '][children][' + childIndex + '][title]" placeholder="Child item title">' +
                            '  <input type="number" name="sub_services[' + parentIndex + '][children][' + childIndex + '][price]" placeholder="Price">' +
                            '  <label class="tspc-sub-default-label">' +
                            '    <input type="checkbox" name="sub_services[' + parentIndex + '][children][' + childIndex + '][default_checked]" value="1">' +
                            '    Default ON' +
                            '  </label>' +
                            '  <button type="button" class="button tspc-remove-child-sub-btn" title="Delete child option"><span class="dashicons dashicons-trash"></span></button>' +
                            '</div>';
            $childrenContainer.append(childHtml);
        });

        // Remove child sub-service
        $subContainer.on('click', '.tspc-remove-child-sub-btn', function(e) {
            e.preventDefault();
            $(this).closest('.tspc-child-sub-row').remove();
        });

        // Remove parent sub-service
        $subContainer.on('click', '.tspc-remove-sub-btn', function(e) {
            e.preventDefault();
            var $group = $(this).closest('.tspc-sub-service-group');
            if ($group.length) {
                $group.remove();
            } else {
                $(this).closest('.tspc-sub-service-row').remove();
            }
        });
    }

    // ── Dynamic Discounts rows in settings ─────────────────────────────
    var $discountContainer = $('#tspc-dynamic-discounts-list');
    var $addDiscountBtn = $('#tspc-add-discount-btn');
    if ($discountContainer.length && $addDiscountBtn.length) {
        var discountIndex = $discountContainer.find('.tspc-discount-row').length;

        $addDiscountBtn.on('click', function(e) {
            e.preventDefault();
            var html = '<div class="tspc-discount-row">' +
                       '  <input type="number" name="dynamic_discounts[' + discountIndex + '][services]" placeholder="No. of Services (e.g. 5)" required min="2">' +
                       '  <input type="number" step="0.1" name="dynamic_discounts[' + discountIndex + '][discount]" placeholder="Discount % (e.g. 15)" required min="0">' +
                       '  <button type="button" class="button tspc-remove-discount-btn"><span class="dashicons dashicons-trash"></span></button>' +
                       '</div>';
            $discountContainer.append(html);
            discountIndex++;
        });

        $discountContainer.on('click', '.tspc-remove-discount-btn', function(e) {
            e.preventDefault();
            $(this).closest('.tspc-discount-row').remove();
        });
    }
});
