/**
 * SemigApp Frontend JavaScript
 *
 * @package SemigApp
 */

(function($) {
    'use strict';

    // Main SemigApp object
    window.SemigApp = {
        // Initialize
        init: function() {
            this.bindEvents();
            this.initCart();
            this.initForms();
        },

        // Bind global events
        bindEvents: function() {
            // Add to cart
            $(document).on('click', '.semigapp-add-to-cart', this.handleAddToCart.bind(this));

            // Update cart quantity
            $(document).on('change', '.semigapp-cart-quantity', this.handleQuantityChange.bind(this));

            // Remove from cart
            $(document).on('click', '.semigapp-remove-from-cart', this.handleRemoveFromCart.bind(this));

            // Newsletter form
            $(document).on('submit', '.semigapp-newsletter-form', this.handleNewsletterSubmit.bind(this));

            // Event registration
            $(document).on('submit', '.semigapp-event-registration-form', this.handleEventRegistration.bind(this));

            // Task status change
            $(document).on('change', '.semigapp-task-status', this.handleTaskStatusChange.bind(this));
        },

        // Initialize cart
        initCart: function() {
            this.updateCartCount();
        },

        // Initialize forms
        initForms: function() {
            // Add validation to forms
            $('form.semigapp-form').each(function() {
                $(this).on('submit', function(e) {
                    if (!SemigApp.validateForm($(this))) {
                        e.preventDefault();
                    }
                });
            });
        },

        // Validate form
        validateForm: function($form) {
            var valid = true;
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();

                if (!value) {
                    valid = false;
                    $field.addClass('error');
                } else {
                    $field.removeClass('error');
                }

                // Email validation
                if ($field.attr('type') === 'email' && value) {
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        valid = false;
                        $field.addClass('error');
                    }
                }
            });

            return valid;
        },

        // AJAX helper
        ajax: function(action, data, callback) {
            data.action = action;
            data.nonce = semigappFrontend.nonce;

            $.ajax({
                url: semigappFrontend.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    SemigApp.showMessage(semigappFrontend.i18n.error, 'error');
                }
            });
        },

        // Handle add to cart
        handleAddToCart: function(e) {
            e.preventDefault();
            var $button = $(e.currentTarget);
            var productId = $button.data('product-id');
            var quantity = $button.closest('.semigapp-product-form').find('.quantity-input').val() || 1;

            $button.prop('disabled', true).text(semigappFrontend.i18n.loading);

            this.ajax('semigapp_cart_action', {
                cart_action: 'add',
                product_id: productId,
                quantity: quantity
            }, function(response) {
                $button.prop('disabled', false).text(semigappFrontend.i18n.addedToCart);

                if (response.success) {
                    SemigApp.updateCartCount(response.data.count);
                    SemigApp.showMessage(semigappFrontend.i18n.addedToCart, 'success');

                    setTimeout(function() {
                        $button.text($button.data('original-text') || 'Add to Cart');
                    }, 2000);
                } else {
                    SemigApp.showMessage(response.data.message || semigappFrontend.i18n.error, 'error');
                }
            });
        },

        // Handle quantity change
        handleQuantityChange: function(e) {
            var $input = $(e.currentTarget);
            var cartKey = $input.data('cart-key');
            var quantity = parseInt($input.val(), 10);

            if (quantity < 1) {
                quantity = 1;
                $input.val(1);
            }

            this.ajax('semigapp_cart_action', {
                cart_action: 'update',
                cart_key: cartKey,
                quantity: quantity
            }, function(response) {
                if (response.success) {
                    SemigApp.updateCartDisplay(response.data);
                }
            });
        },

        // Handle remove from cart
        handleRemoveFromCart: function(e) {
            e.preventDefault();

            if (!confirm(semigappFrontend.i18n.removeConfirm)) {
                return;
            }

            var $link = $(e.currentTarget);
            var cartKey = $link.data('cart-key');

            this.ajax('semigapp_cart_action', {
                cart_action: 'remove',
                cart_key: cartKey
            }, function(response) {
                if (response.success) {
                    $link.closest('tr, .cart-item').fadeOut(300, function() {
                        $(this).remove();
                        SemigApp.updateCartDisplay(response.data);
                    });
                }
            });
        },

        // Update cart count
        updateCartCount: function(count) {
            if (typeof count === 'undefined') {
                count = window.semigappCartCount || 0;
            }
            $('.semigapp-cart-count').text(count);
        },

        // Update cart display
        updateCartDisplay: function(data) {
            this.updateCartCount(data.count);

            if (data.totals) {
                $('.semigapp-cart-subtotal').text(data.totals.subtotal_formatted);
                $('.semigapp-cart-tax').text(data.totals.tax_total_formatted);
                $('.semigapp-cart-shipping').text(data.totals.shipping_formatted);
                $('.semigapp-cart-total').text(data.totals.total_formatted);
            }

            if (data.count === 0) {
                location.reload();
            }
        },

        // Handle newsletter submit
        handleNewsletterSubmit: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $button = $form.find('button[type="submit"]');
            var $message = $form.find('.semigapp-newsletter-message');

            if (!this.validateForm($form)) {
                return;
            }

            $button.prop('disabled', true).text(semigappFrontend.i18n.loading);
            $message.removeClass('success error').hide();

            var data = {
                email: $form.find('[name="email"]').val(),
                first_name: $form.find('[name="first_name"]').val(),
                last_name: $form.find('[name="last_name"]').val(),
                list_id: $form.find('[name="list_id"]').val()
            };

            this.ajax('semigapp_newsletter_subscribe', data, function(response) {
                $button.prop('disabled', false).text($button.data('text') || 'Subscribe');

                if (response.success) {
                    $message.addClass('success').text(response.data.message).fadeIn();
                    $form[0].reset();
                } else {
                    $message.addClass('error').text(response.data.message).fadeIn();
                }
            });
        },

        // Handle event registration
        handleEventRegistration: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $button = $form.find('button[type="submit"]');

            if (!this.validateForm($form)) {
                return;
            }

            $button.prop('disabled', true).text(semigappFrontend.i18n.loading);

            var formData = $form.serializeArray();
            var data = {};
            formData.forEach(function(item) {
                data[item.name] = item.value;
            });

            this.ajax('semigapp_event_register', data, function(response) {
                $button.prop('disabled', false);

                if (response.success) {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        SemigApp.showMessage(response.data.message, 'success');
                        $form[0].reset();
                    }
                } else {
                    SemigApp.showMessage(response.data.message || semigappFrontend.i18n.error, 'error');
                }
            });
        },

        // Handle task status change
        handleTaskStatusChange: function(e) {
            var $select = $(e.currentTarget);
            var taskId = $select.data('task-id');
            var status = $select.val();

            this.ajax('semigapp_task_action', {
                task_action: 'update_status',
                task_id: taskId,
                status: status
            }, function(response) {
                if (response.success) {
                    SemigApp.showMessage('Status updated', 'success');
                }
            });
        },

        // Show message
        showMessage: function(message, type) {
            var $message = $('<div class="semigapp-toast semigapp-toast-' + type + '">' + message + '</div>');

            $('body').append($message);

            setTimeout(function() {
                $message.addClass('show');
            }, 10);

            setTimeout(function() {
                $message.removeClass('show');
                setTimeout(function() {
                    $message.remove();
                }, 300);
            }, 3000);
        },

        // Format price
        formatPrice: function(price) {
            var currency = semigappFrontend.currency;
            var formatted = parseFloat(price).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

            if (currency.position === 'before') {
                return currency.symbol + formatted;
            }
            return formatted + ' ' + currency.symbol;
        }
    };

    // Calendar Module
    SemigApp.Calendar = {
        currentYear: new Date().getFullYear(),
        currentMonth: new Date().getMonth() + 1,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.semigapp-calendar-prev', this.prevMonth.bind(this));
            $(document).on('click', '.semigapp-calendar-next', this.nextMonth.bind(this));
        },

        prevMonth: function() {
            this.currentMonth--;
            if (this.currentMonth < 1) {
                this.currentMonth = 12;
                this.currentYear--;
            }
            this.loadMonth();
        },

        nextMonth: function() {
            this.currentMonth++;
            if (this.currentMonth > 12) {
                this.currentMonth = 1;
                this.currentYear++;
            }
            this.loadMonth();
        },

        loadMonth: function() {
            var $calendar = $('.semigapp-calendar');
            $calendar.addClass('loading');

            SemigApp.ajax('semigapp_event_action', {
                event_action: 'get_calendar',
                year: this.currentYear,
                month: this.currentMonth
            }, function(response) {
                $calendar.removeClass('loading');
                if (response.success) {
                    SemigApp.Calendar.renderCalendar(response.data.events);
                }
            });
        },

        renderCalendar: function(events) {
            // Calendar rendering logic
        }
    };

    // Task Board Module (Kanban)
    SemigApp.TaskBoard = {
        init: function() {
            if (typeof $.fn.sortable !== 'undefined') {
                this.initSortable();
            }
        },

        initSortable: function() {
            $('.semigapp-task-list').sortable({
                connectWith: '.semigapp-task-list',
                handle: '.semigapp-task-card',
                placeholder: 'semigapp-task-placeholder',
                update: function(event, ui) {
                    var $card = ui.item;
                    var taskId = $card.data('task-id');
                    var newStatus = $card.closest('.semigapp-task-column').data('status');

                    SemigApp.ajax('semigapp_task_action', {
                        task_action: 'update_status',
                        task_id: taskId,
                        status: newStatus
                    });
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SemigApp.init();

        if ($('.semigapp-calendar').length) {
            SemigApp.Calendar.init();
        }

        if ($('.semigapp-task-board').length) {
            SemigApp.TaskBoard.init();
        }
    });

    // Add toast styles dynamically
    var toastStyles = `
        .semigapp-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            z-index: 99999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .semigapp-toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .semigapp-toast-success { background-color: #10b981; }
        .semigapp-toast-error { background-color: #ef4444; }
        .semigapp-toast-warning { background-color: #f59e0b; }
    `;
    $('<style>').text(toastStyles).appendTo('head');

})(jQuery);
