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
        var html = '<div class="tspc-modal-grid">';
        
        html += '<div class="tspc-modal-label">Submission Date</div>';
        html += '<div class="tspc-modal-val">' + formattedDate + '</div>';

        html += '<div class="tspc-modal-label">Client Name</div>';
        html += '<div class="tspc-modal-val"><strong>' + escapeHtml(inquiryData.name) + '</strong></div>';

        html += '<div class="tspc-modal-label">Email Address</div>';
        html += '<div class="tspc-modal-val"><a href="mailto:' + inquiryData.email + '">' + escapeHtml(inquiryData.email) + '</a></div>';

        html += '<div class="tspc-modal-label">Phone Number</div>';
        if (inquiryData.phone && inquiryData.phone !== '') {
            html += '<div class="tspc-modal-val"><a href="tel:' + escapeHtml(inquiryData.phone) + '" style="display:inline-flex;align-items:center;gap:5px;"><span class="dashicons dashicons-phone" style="font-size:14px;width:14px;height:14px;"></span>' + escapeHtml(inquiryData.phone) + '</a></div>';
        } else {
            html += '<div class="tspc-modal-val" style="color:#94a3b8;">— Not provided —</div>';
        }

        html += '<div class="tspc-modal-label">Selected Services</div>';
        html += '<div class="tspc-modal-val">' + servicesHtml + '</div>';

        html += '<div class="tspc-modal-label">Subtotal</div>';
        html += '<div class="tspc-modal-val">৳' + parseFloat(inquiryData.subtotal).toLocaleString() + '</div>';

        html += '<div class="tspc-modal-label">Discount Saved</div>';
        html += '<div class="tspc-modal-val">-৳' + parseFloat(inquiryData.discount).toLocaleString() + '</div>';

        html += '<div class="tspc-modal-label">Total Price</div>';
        html += '<div class="tspc-modal-val"><span class="tspc-lead-price">৳' + parseFloat(inquiryData.total).toLocaleString() + '</span></div>';

        html += '<div class="tspc-modal-label">Message / Notes</div>';
        html += '<div class="tspc-modal-val"><pre>' + escapeHtml(inquiryData.message || 'No additional comments.') + '</pre></div>';

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
        var subIndex = $subContainer.find('.tspc-sub-service-row').length;

        $addSubBtn.on('click', function(e) {
            e.preventDefault();
            var html = '<div class="tspc-sub-service-row" style="display: flex; gap: 10px; margin-bottom: 8px; align-items: center;">' +
                       '  <input type="text" name="sub_services[' + subIndex + '][title]" placeholder="Sub-service Title (e.g. E-commerce System)" style="flex-grow: 2;">' +
                       '  <input type="number" name="sub_services[' + subIndex + '][price]" placeholder="Extra Price (e.g. 5000)" style="width: 150px;">' +
                       '  <button type="button" class="button tspc-remove-sub-btn" style="color: #ef4444; border-color: #fca5a5;"><span class="dashicons dashicons-trash" style="margin-top: 4px;"></span></button>' +
                       '</div>';
            $subContainer.append(html);
            subIndex++;
        });

        $subContainer.on('click', '.tspc-remove-sub-btn', function(e) {
            e.preventDefault();
            $(this).closest('.tspc-sub-service-row').remove();
        });
    }
});
