/**
 * SemigApp Admin JavaScript
 *
 * @package SemigApp
 */

(function($) {
    'use strict';

    // Main Admin object
    window.SemigAppAdmin = {
        // Initialize
        init: function() {
            this.bindEvents();
            this.initDatepickers();
            this.initColorPickers();
            this.initMediaUploader();
            this.initCharts();
            this.initSortables();
        },

        // Bind events
        bindEvents: function() {
            // Delete confirmation
            $(document).on('click', '.semigapp-delete', this.confirmDelete.bind(this));

            // Form submissions
            $(document).on('submit', '.semigapp-ajax-form', this.handleFormSubmit.bind(this));

            // Toggle switches
            $(document).on('change', '.semigapp-toggle input', this.handleToggle.bind(this));

            // Modal handling
            $(document).on('click', '[data-modal]', this.openModal.bind(this));
            $(document).on('click', '.semigapp-modal-close, .semigapp-modal-overlay', this.closeModal.bind(this));

            // Tab navigation
            $(document).on('click', '.semigapp-tabs a', this.handleTabs.bind(this));

            // Status updates
            $(document).on('change', '.semigapp-status-select', this.handleStatusChange.bind(this));

            // Bulk actions
            $(document).on('click', '.semigapp-bulk-action-apply', this.handleBulkAction.bind(this));

            // Select all checkbox
            $(document).on('change', '.semigapp-select-all', this.handleSelectAll.bind(this));

            // Search
            $(document).on('keyup', '.semigapp-search input', this.debounce(this.handleSearch.bind(this), 300));
        },

        // AJAX helper
        ajax: function(data, callback) {
            data.nonce = semigappAdmin.nonce;

            $.ajax({
                url: semigappAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    SemigAppAdmin.showNotice(semigappAdmin.i18n.error, 'error');
                }
            });
        },

        // Confirm delete
        confirmDelete: function(e) {
            if (!confirm(semigappAdmin.i18n.confirmDelete)) {
                e.preventDefault();
                return false;
            }
        },

        // Handle form submit
        handleFormSubmit: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $button = $form.find('button[type="submit"]');

            $button.prop('disabled', true).text(semigappAdmin.i18n.saving);

            var formData = new FormData($form[0]);
            formData.append('action', 'semigapp_admin_action');
            formData.append('nonce', semigappAdmin.nonce);

            $.ajax({
                url: semigappAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $button.prop('disabled', false).text($button.data('text') || 'Save');

                    if (response.success) {
                        SemigAppAdmin.showNotice(semigappAdmin.i18n.saved, 'success');

                        if (response.data && response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        SemigAppAdmin.showNotice(response.data.message || semigappAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text($button.data('text') || 'Save');
                    SemigAppAdmin.showNotice(semigappAdmin.i18n.error, 'error');
                }
            });
        },

        // Handle toggle
        handleToggle: function(e) {
            var $toggle = $(e.currentTarget);
            var setting = $toggle.attr('name');
            var value = $toggle.is(':checked') ? 1 : 0;

            this.ajax({
                action: 'semigapp_admin_action',
                admin_action: 'toggle_setting',
                setting: setting,
                value: value
            });
        },

        // Open modal
        openModal: function(e) {
            e.preventDefault();
            var modalId = $(e.currentTarget).data('modal');
            $('#' + modalId).addClass('active');
        },

        // Close modal
        closeModal: function(e) {
            if ($(e.target).hasClass('semigapp-modal-overlay') || $(e.target).hasClass('semigapp-modal-close')) {
                $('.semigapp-modal-overlay').removeClass('active');
            }
        },

        // Handle tabs
        handleTabs: function(e) {
            e.preventDefault();
            var $tab = $(e.currentTarget);
            var target = $tab.attr('href');

            $tab.closest('.semigapp-tabs').find('a').removeClass('active');
            $tab.addClass('active');

            $(target).siblings('.semigapp-tab-content').hide();
            $(target).show();
        },

        // Handle status change
        handleStatusChange: function(e) {
            var $select = $(e.currentTarget);
            var id = $select.data('id');
            var module = $select.data('module');
            var status = $select.val();

            this.ajax({
                action: 'semigapp_admin_action',
                admin_action: 'update_status',
                module: module,
                id: id,
                status: status
            }, function(response) {
                if (response.success) {
                    SemigAppAdmin.showNotice('Status updated', 'success');
                }
            });
        },

        // Handle bulk action
        handleBulkAction: function(e) {
            e.preventDefault();
            var $button = $(e.currentTarget);
            var action = $button.siblings('select').val();

            if (!action) {
                return;
            }

            var ids = [];
            $('.semigapp-row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                alert('Please select items');
                return;
            }

            if (action === 'delete' && !confirm(semigappAdmin.i18n.confirmDelete)) {
                return;
            }

            this.ajax({
                action: 'semigapp_admin_action',
                admin_action: 'bulk_' + action,
                ids: ids,
                module: $button.data('module')
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        },

        // Handle select all
        handleSelectAll: function(e) {
            var checked = $(e.currentTarget).is(':checked');
            $('.semigapp-row-checkbox').prop('checked', checked);
        },

        // Handle search
        handleSearch: function(e) {
            var query = $(e.currentTarget).val();
            var $table = $(e.currentTarget).closest('.semigapp-admin-card').find('.semigapp-admin-table');

            $table.find('tbody tr').each(function() {
                var $row = $(this);
                var text = $row.text().toLowerCase();

                if (text.indexOf(query.toLowerCase()) !== -1) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },

        // Initialize datepickers
        initDatepickers: function() {
            if (typeof $.fn.datepicker !== 'undefined') {
                $('.semigapp-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });

                $('.semigapp-datetimepicker').each(function() {
                    var $input = $(this);
                    // For datetime, we'd need a more advanced picker
                    // This is a basic implementation
                    $input.datepicker({
                        dateFormat: 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true
                    });
                });
            }
        },

        // Initialize color pickers
        initColorPickers: function() {
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.semigapp-color-picker').wpColorPicker();
            }
        },

        // Initialize media uploader
        initMediaUploader: function() {
            var self = this;

            $(document).on('click', '.semigapp-upload-button', function(e) {
                e.preventDefault();

                var $button = $(this);
                var $input = $button.siblings('.semigapp-upload-input');
                var $preview = $button.siblings('.semigapp-upload-preview');

                var frame = wp.media({
                    title: semigappAdmin.i18n.selectImage,
                    button: { text: semigappAdmin.i18n.useImage },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.url);
                    $preview.html('<img src="' + attachment.url + '" style="max-width:200px;">');
                });

                frame.open();
            });

            $(document).on('click', '.semigapp-remove-upload', function(e) {
                e.preventDefault();
                var $button = $(this);
                $button.siblings('.semigapp-upload-input').val('');
                $button.siblings('.semigapp-upload-preview').empty();
            });
        },

        // Initialize charts
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }

            // Revenue chart
            var $revenueChart = $('#semigapp-revenue-chart');
            if ($revenueChart.length && $revenueChart.data('values')) {
                new Chart($revenueChart, {
                    type: 'line',
                    data: {
                        labels: $revenueChart.data('labels'),
                        datasets: [{
                            label: 'Revenue',
                            data: $revenueChart.data('values'),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

            // Orders chart
            var $ordersChart = $('#semigapp-orders-chart');
            if ($ordersChart.length && $ordersChart.data('values')) {
                new Chart($ordersChart, {
                    type: 'bar',
                    data: {
                        labels: $ordersChart.data('labels'),
                        datasets: [{
                            label: 'Orders',
                            data: $ordersChart.data('values'),
                            backgroundColor: '#10b981'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        },

        // Initialize sortables
        initSortables: function() {
            if (typeof $.fn.sortable !== 'undefined') {
                $('.semigapp-sortable').sortable({
                    handle: '.semigapp-sort-handle',
                    update: function(event, ui) {
                        var order = $(this).sortable('toArray', { attribute: 'data-id' });
                        var module = $(this).data('module');

                        SemigAppAdmin.ajax({
                            action: 'semigapp_admin_action',
                            admin_action: 'reorder',
                            module: module,
                            order: order
                        });
                    }
                });
            }
        },

        // Show notice
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            $('.semigapp-admin-wrap').prepend($notice);

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        // Debounce utility
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },

        // Format price
        formatPrice: function(price) {
            var currency = semigappAdmin.currency;
            var formatted = parseFloat(price).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

            if (currency.position === 'before') {
                return currency.symbol + formatted;
            }
            return formatted + ' ' + currency.symbol;
        }
    };

    // Campaign Editor
    SemigAppAdmin.CampaignEditor = {
        init: function() {
            if ($('.semigapp-campaign-editor').length === 0) {
                return;
            }

            this.bindEvents();
            this.initEditor();
        },

        bindEvents: function() {
            $(document).on('click', '.semigapp-insert-placeholder', this.insertPlaceholder.bind(this));
            $(document).on('click', '.semigapp-preview-campaign', this.previewCampaign.bind(this));
            $(document).on('click', '.semigapp-send-test', this.sendTest.bind(this));
        },

        initEditor: function() {
            // Initialize TinyMCE if available
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.initialize('campaign-content', {
                    tinymce: {
                        toolbar1: 'formatselect,bold,italic,underline,|,bullist,numlist,|,link,unlink,|,alignleft,aligncenter,alignright,|,undo,redo'
                    },
                    quicktags: true
                });
            }
        },

        insertPlaceholder: function(e) {
            e.preventDefault();
            var placeholder = $(e.currentTarget).data('placeholder');
            var $editor = $('#campaign-content');

            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('campaign-content')) {
                tinyMCE.get('campaign-content').execCommand('mceInsertContent', false, placeholder);
            } else {
                var cursorPos = $editor[0].selectionStart;
                var text = $editor.val();
                $editor.val(text.substring(0, cursorPos) + placeholder + text.substring(cursorPos));
            }
        },

        previewCampaign: function(e) {
            e.preventDefault();
            // Open preview modal
        },

        sendTest: function(e) {
            e.preventDefault();
            var email = prompt('Enter email address for test:');

            if (!email) {
                return;
            }

            SemigAppAdmin.ajax({
                action: 'semigapp_admin_action',
                admin_action: 'send_test_campaign',
                campaign_id: $('#campaign-id').val(),
                email: email
            }, function(response) {
                if (response.success) {
                    SemigAppAdmin.showNotice('Test email sent!', 'success');
                } else {
                    SemigAppAdmin.showNotice(response.data.message, 'error');
                }
            });
        }
    };

    // Product Editor
    SemigAppAdmin.ProductEditor = {
        init: function() {
            if ($('.semigapp-product-editor').length === 0) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('change', '#manage-stock', this.toggleStock.bind(this));
            $(document).on('change', '#downloadable', this.toggleDownloadable.bind(this));
        },

        toggleStock: function(e) {
            var $checkbox = $(e.currentTarget);
            $('.stock-fields').toggle($checkbox.is(':checked'));
        },

        toggleDownloadable: function(e) {
            var $checkbox = $(e.currentTarget);
            $('.download-fields').toggle($checkbox.is(':checked'));
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SemigAppAdmin.init();
        SemigAppAdmin.CampaignEditor.init();
        SemigAppAdmin.ProductEditor.init();
    });

})(jQuery);
