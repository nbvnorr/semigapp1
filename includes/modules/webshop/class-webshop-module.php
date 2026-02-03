<?php
/**
 * Webshop Module
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Webshop;

use SemigApp\Modules\Base_Module;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Webshop_Module
 *
 * Handles webshop, products, orders, and Swedish payment gateways
 */
class Webshop_Module extends Base_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = 'webshop';

    /**
     * Module name
     *
     * @var string
     */
    protected $name = 'Webshop';

    /**
     * Order statuses
     *
     * @var array
     */
    private $order_statuses = array();

    /**
     * Payment gateways
     *
     * @var array
     */
    private $gateways = array();

    /**
     * Cart
     *
     * @var array
     */
    private $cart = array();

    /**
     * Cart session key (unique per visitor)
     *
     * @var string
     */
    private $cart_key = '';

    /**
     * Initialize the module
     */
    public function init() {
        $this->setup_statuses();
        $this->init_gateways();
        $this->init_cart();

        // Register hooks
        add_action('semigapp_register_shortcodes', array($this, 'register_shortcodes'));
        add_action('wp_ajax_semigapp_cart_action', array($this, 'handle_cart_ajax'));
        add_action('wp_ajax_nopriv_semigapp_cart_action', array($this, 'handle_cart_ajax'));
        add_action('wp_ajax_semigapp_checkout', array($this, 'handle_checkout'));
        add_action('wp_ajax_nopriv_semigapp_checkout', array($this, 'handle_checkout'));

        // Payment callbacks
        add_action('wp_ajax_nopriv_semigapp_payment_callback', array($this, 'handle_payment_callback'));
        add_action('wp_ajax_semigapp_payment_callback', array($this, 'handle_payment_callback'));

        // Email notifications
        add_action('semigapp_order_created', array($this, 'notify_order_created'), 10, 1);
        add_action('semigapp_order_status_changed', array($this, 'notify_order_status_changed'), 10, 3);

        // Clean up expired cart transients daily
        add_action('semigapp_daily_cleanup', array($this, 'cleanup_expired_carts'));

        // Migrate guest cart to user cart on login
        add_action('wp_login', array($this, 'migrate_cart_on_login'), 10, 2);
    }

    /**
     * Setup order statuses
     */
    private function setup_statuses() {
        $this->order_statuses = array(
            'pending' => __('Pending', 'semigapp'),
            'processing' => __('Processing', 'semigapp'),
            'on-hold' => __('On Hold', 'semigapp'),
            'shipped' => __('Shipped', 'semigapp'),
            'completed' => __('Completed', 'semigapp'),
            'cancelled' => __('Cancelled', 'semigapp'),
            'refunded' => __('Refunded', 'semigapp'),
            'failed' => __('Failed', 'semigapp'),
        );
    }

    /**
     * Initialize payment gateways
     */
    private function init_gateways() {
        $this->gateways = array(
            'klarna' => new Payment_Gateways\Klarna_Gateway($this->settings),
            'swish' => new Payment_Gateways\Swish_Gateway($this->settings),
            'stripe' => new Payment_Gateways\Stripe_Gateway($this->settings),
        );
    }

    /**
     * Initialize cart using WordPress transients (no PHP sessions)
     *
     * Uses a unique cart key stored in a cookie to identify the cart.
     * For logged-in users, uses user ID for consistent cart across devices.
     */
    private function init_cart() {
        $this->cart_key = $this->get_cart_key();
        $this->cart = $this->load_cart_from_transient();
    }

    /**
     * Get or generate a unique cart key
     *
     * For logged-in users: uses user ID
     * For guests: uses a cookie-based unique identifier
     *
     * @return string Cart key.
     */
    private function get_cart_key() {
        // For logged-in users, use their user ID
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }

        // For guests, use a cookie-based key
        $cookie_name = 'semigapp_cart_key';

        if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name])) {
            return sanitize_text_field($_COOKIE[$cookie_name]);
        }

        // Generate a new unique key for guest
        $cart_key = 'guest_' . wp_generate_password(32, false);

        // Set cookie for 30 days (only if headers not sent)
        if (!headers_sent()) {
            setcookie(
                $cookie_name,
                $cart_key,
                time() + (30 * DAY_IN_SECONDS),
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true // HttpOnly
            );
        }

        return $cart_key;
    }

    /**
     * Load cart from WordPress transient
     *
     * @return array Cart contents.
     */
    private function load_cart_from_transient() {
        $cart = get_transient('semigapp_cart_' . $this->cart_key);
        return is_array($cart) ? $cart : array();
    }

    /**
     * Save cart to WordPress transient
     *
     * Cart expires after 7 days of inactivity.
     */
    private function save_cart() {
        set_transient(
            'semigapp_cart_' . $this->cart_key,
            $this->cart,
            7 * DAY_IN_SECONDS
        );
    }

    /**
     * Migrate guest cart to user cart after login
     *
     * Called via action hook when user logs in.
     *
     * @param string  $user_login Username.
     * @param WP_User $user       User object.
     */
    public function migrate_cart_on_login($user_login, $user) {
        $cookie_name = 'semigapp_cart_key';

        if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name])) {
            $guest_key = sanitize_text_field($_COOKIE[$cookie_name]);
            $guest_cart = get_transient('semigapp_cart_' . $guest_key);

            if (!empty($guest_cart) && is_array($guest_cart)) {
                $user_key = 'user_' . $user->ID;
                $user_cart = get_transient('semigapp_cart_' . $user_key);

                // Merge guest cart into user cart (guest items take precedence for quantities)
                if (is_array($user_cart)) {
                    foreach ($guest_cart as $key => $item) {
                        $user_cart[$key] = $item;
                    }
                } else {
                    $user_cart = $guest_cart;
                }

                set_transient('semigapp_cart_' . $user_key, $user_cart, 7 * DAY_IN_SECONDS);

                // Delete guest cart
                delete_transient('semigapp_cart_' . $guest_key);
            }

            // Clear the guest cookie
            if (!headers_sent()) {
                setcookie($cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }

    /**
     * Cleanup expired cart transients (called by cron)
     *
     * WordPress handles transient expiration automatically, but this
     * helps clean up orphaned transients from the database.
     */
    public function cleanup_expired_carts() {
        global $wpdb;

        // Delete expired transients with our prefix using prepared statement
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             AND option_name NOT LIKE %s",
            $wpdb->esc_like('_transient_semigapp_cart_') . '%',
            $wpdb->esc_like('_transient_timeout_') . '%'
        ));
    }

    /**
     * Get order statuses
     *
     * @return array
     */
    public function get_order_statuses() {
        return apply_filters('semigapp_order_statuses', $this->order_statuses);
    }

    /**
     * Get available payment gateways
     *
     * @return array
     */
    public function get_available_gateways() {
        $available = array();

        foreach ($this->gateways as $id => $gateway) {
            if ($gateway->is_available()) {
                $available[$id] = $gateway;
            }
        }

        return $available;
    }

    /**
     * Get payment gateway
     *
     * @param string $gateway_id Gateway ID.
     * @return object|null Gateway or null.
     */
    public function get_gateway($gateway_id) {
        return isset($this->gateways[$gateway_id]) ? $this->gateways[$gateway_id] : null;
    }

    // =========================================================================
    // PRODUCT METHODS
    // =========================================================================

    /**
     * Create a product
     *
     * @param array $data Product data.
     * @return int|false Product ID or false.
     */
    public function create_product($data) {
        $schema = array(
            'name' => 'text',
            'slug' => 'text',
            'description' => 'html',
            'short_description' => 'textarea',
            'sku' => 'text',
            'price' => 'float',
            'sale_price' => 'float',
            'sale_start' => 'datetime',
            'sale_end' => 'datetime',
            'stock_quantity' => 'int',
            'stock_status' => 'text',
            'manage_stock' => 'bool',
            'weight' => 'float',
            'dimensions' => 'text',
            'tax_class' => 'text',
            'tax_rate' => 'float',
            'featured_image' => 'int',
            'gallery' => 'json',
            'categories' => 'json',
            'tags' => 'json',
            'attributes' => 'json',
            'downloadable' => 'bool',
            'download_files' => 'json',
            'virtual' => 'bool',
            'status' => 'text',
            'featured' => 'bool',
            'sort_order' => 'int',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // Validate required fields
        $missing = $this->validate_required($sanitized, array('name', 'price'));
        if (!empty($missing)) {
            return false;
        }

        // Generate slug
        if (empty($sanitized['slug'])) {
            $sanitized['slug'] = sanitize_title($sanitized['name']);
        }

        // Set defaults
        $sanitized['status'] = $sanitized['status'] ?? 'published';
        $sanitized['stock_status'] = $sanitized['stock_status'] ?? 'instock';
        $sanitized['tax_rate'] = $sanitized['tax_rate'] ?? 25.00; // Swedish VAT

        $product_id = $this->db->insert('products', $sanitized);

        if ($product_id) {
            $this->clear_products_cache();
            $this->log_activity('product', $product_id, 'created', 'Product created');
            do_action('semigapp_product_created', $product_id, $sanitized);
        }

        return $product_id;
    }

    /**
     * Update a product
     *
     * @param int   $product_id Product ID.
     * @param array $data       Product data.
     * @return bool True on success.
     */
    public function update_product($product_id, $data) {
        $old_product = $this->get_product($product_id);

        if (!$old_product) {
            return false;
        }

        $schema = array(
            'name' => 'text',
            'slug' => 'text',
            'description' => 'html',
            'short_description' => 'textarea',
            'sku' => 'text',
            'price' => 'float',
            'sale_price' => 'float',
            'sale_start' => 'datetime',
            'sale_end' => 'datetime',
            'stock_quantity' => 'int',
            'stock_status' => 'text',
            'manage_stock' => 'bool',
            'weight' => 'float',
            'dimensions' => 'text',
            'tax_class' => 'text',
            'tax_rate' => 'float',
            'featured_image' => 'int',
            'gallery' => 'json',
            'categories' => 'json',
            'tags' => 'json',
            'attributes' => 'json',
            'downloadable' => 'bool',
            'download_files' => 'json',
            'virtual' => 'bool',
            'status' => 'text',
            'featured' => 'bool',
            'sort_order' => 'int',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        $result = $this->db->update('products', $sanitized, array('id' => $product_id));

        if ($result !== false) {
            $this->clear_products_cache();
            $this->log_activity('product', $product_id, 'updated', 'Product updated');
            do_action('semigapp_product_updated', $product_id, $sanitized, $old_product);
        }

        return $result !== false;
    }

    /**
     * Delete a product
     *
     * @param int $product_id Product ID.
     * @return bool True on success.
     */
    public function delete_product($product_id) {
        $product = $this->get_product($product_id);

        if (!$product) {
            return false;
        }

        $result = $this->db->delete('products', array('id' => $product_id));

        if ($result) {
            $this->clear_products_cache();
            $this->log_activity('product', $product_id, 'deleted', 'Product deleted');
            do_action('semigapp_product_deleted', $product_id, $product);
        }

        return (bool) $result;
    }

    /**
     * Get a product
     *
     * @param int $product_id Product ID.
     * @return object|null Product or null.
     */
    public function get_product($product_id) {
        $product = $this->db->get_row('products', array('id' => $product_id));

        if ($product) {
            $product = $this->prepare_product($product);
        }

        return $product;
    }

    /**
     * Get product by slug
     *
     * @param string $slug Product slug.
     * @return object|null Product or null.
     */
    public function get_product_by_slug($slug) {
        $product = $this->db->get_row('products', array('slug' => $slug));

        if ($product) {
            $product = $this->prepare_product($product);
        }

        return $product;
    }

    /**
     * Prepare product data
     *
     * @param object $product Product object.
     * @return object Prepared product.
     */
    private function prepare_product($product) {
        $product->gallery = json_decode($product->gallery, true) ?: array();
        $product->categories = json_decode($product->categories, true) ?: array();
        $product->tags = json_decode($product->tags, true) ?: array();
        $product->attributes = json_decode($product->attributes, true) ?: array();
        $product->download_files = json_decode($product->download_files, true) ?: array();

        // Calculate current price
        $product->current_price = $this->get_product_price($product);
        $product->is_on_sale = $this->is_on_sale($product);
        $product->is_in_stock = $this->is_in_stock($product);

        // Format prices
        $product->price_formatted = $this->settings->format_price($product->price);
        $product->current_price_formatted = $this->settings->format_price($product->current_price);

        if ($product->sale_price) {
            $product->sale_price_formatted = $this->settings->format_price($product->sale_price);
        }

        return $product;
    }

    /**
     * Get products
     *
     * @param array $args Query arguments.
     * @return array Products.
     */
    public function get_products($args = array()) {
        $defaults = array(
            'where' => array('status' => 'published'),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);

        if (!empty($args['search'])) {
            $args['search_columns'] = array('name', 'description', 'short_description', 'sku');
        }

        // Generate cache key based on args (skip cache for searches)
        $use_cache = empty($args['search']);
        $cache_key = $use_cache ? 'semigapp_products_' . md5(serialize($args)) : '';

        if ($use_cache) {
            $products = get_transient($cache_key);
            if ($products !== false) {
                return $products;
            }
        }

        $products = $this->db->get_results('products', $args);

        foreach ($products as &$product) {
            $product = $this->prepare_product($product);
        }

        // Cache for 30 minutes (skip caching search results)
        if ($use_cache && !empty($products)) {
            set_transient($cache_key, $products, 30 * MINUTE_IN_SECONDS);
        }

        return $products;
    }

    /**
     * Clear products cache
     *
     * Called when products are created/updated/deleted.
     */
    public function clear_products_cache() {
        global $wpdb;

        // Use prepared statements for LIKE queries
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             OR option_name LIKE %s",
            $wpdb->esc_like('_transient_semigapp_products_') . '%',
            $wpdb->esc_like('_transient_timeout_semigapp_products_') . '%'
        ));
    }

    /**
     * Get product price (considering sales)
     *
     * @param object $product Product object.
     * @return float Current price.
     */
    public function get_product_price($product) {
        if ($this->is_on_sale($product)) {
            return floatval($product->sale_price);
        }

        return floatval($product->price);
    }

    /**
     * Check if product is on sale
     *
     * @param object $product Product object.
     * @return bool True if on sale.
     */
    public function is_on_sale($product) {
        if (!$product->sale_price || $product->sale_price >= $product->price) {
            return false;
        }

        $now = current_time('mysql');

        if ($product->sale_start && $product->sale_start > $now) {
            return false;
        }

        if ($product->sale_end && $product->sale_end < $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if product is in stock
     *
     * @param object $product Product object.
     * @return bool True if in stock.
     */
    public function is_in_stock($product) {
        if ($product->stock_status === 'outofstock') {
            return false;
        }

        if ($product->manage_stock && $product->stock_quantity <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Reduce stock
     *
     * @param int $product_id Product ID.
     * @param int $quantity   Quantity to reduce.
     * @return bool True on success.
     */
    public function reduce_stock($product_id, $quantity = 1) {
        $product = $this->get_product($product_id);

        if (!$product || !$product->manage_stock) {
            return false;
        }

        $new_quantity = max(0, $product->stock_quantity - $quantity);
        $stock_status = $new_quantity > 0 ? 'instock' : 'outofstock';

        return $this->update_product($product_id, array(
            'stock_quantity' => $new_quantity,
            'stock_status' => $stock_status,
        ));
    }

    // =========================================================================
    // CART METHODS
    // =========================================================================

    /**
     * Add item to cart
     *
     * @param int $product_id Product ID.
     * @param int $quantity   Quantity.
     * @return bool True on success.
     */
    public function add_to_cart($product_id, $quantity = 1) {
        $product = $this->get_product($product_id);

        if (!$product || !$product->is_in_stock) {
            return false;
        }

        $key = $this->get_cart_item_key($product_id);

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity'] += $quantity;
        } else {
            $this->cart[$key] = array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $product->current_price,
            );
        }

        // Check stock
        if ($product->manage_stock && $this->cart[$key]['quantity'] > $product->stock_quantity) {
            $this->cart[$key]['quantity'] = $product->stock_quantity;
        }

        $this->save_cart();

        return true;
    }

    /**
     * Update cart item quantity
     *
     * @param string $cart_key Cart item key.
     * @param int    $quantity New quantity.
     * @return bool True on success.
     */
    public function update_cart_item($cart_key, $quantity) {
        if (!isset($this->cart[$cart_key])) {
            return false;
        }

        if ($quantity <= 0) {
            return $this->remove_from_cart($cart_key);
        }

        $product = $this->get_product($this->cart[$cart_key]['product_id']);

        if ($product->manage_stock && $quantity > $product->stock_quantity) {
            $quantity = $product->stock_quantity;
        }

        $this->cart[$cart_key]['quantity'] = $quantity;
        $this->save_cart();

        return true;
    }

    /**
     * Remove item from cart
     *
     * @param string $cart_key Cart item key.
     * @return bool True on success.
     */
    public function remove_from_cart($cart_key) {
        if (isset($this->cart[$cart_key])) {
            unset($this->cart[$cart_key]);
            $this->save_cart();
            return true;
        }

        return false;
    }

    /**
     * Clear cart
     */
    public function clear_cart() {
        $this->cart = array();
        $this->save_cart();
    }

    /**
     * Get cart contents
     *
     * @return array Cart contents.
     */
    public function get_cart() {
        $cart = array();

        foreach ($this->cart as $key => $item) {
            $product = $this->get_product($item['product_id']);

            if ($product) {
                $cart[$key] = array(
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->current_price,
                    'subtotal' => $product->current_price * $item['quantity'],
                );
            }
        }

        return $cart;
    }

    /**
     * Get cart totals
     *
     * @return array Cart totals.
     */
    public function get_cart_totals() {
        $cart = $this->get_cart();

        $subtotal = 0;
        $tax_total = 0;
        $shipping = 0;

        foreach ($cart as $item) {
            $subtotal += $item['subtotal'];

            // Calculate tax
            if ($this->get_setting('enable_tax', true)) {
                $tax_rate = $item['product']->tax_rate / 100;
                $tax_total += $item['subtotal'] * $tax_rate;
            }
        }

        // Calculate shipping
        if ($this->get_setting('enable_shipping', true)) {
            $free_threshold = $this->get_setting('free_shipping_threshold', 500);

            if ($subtotal < $free_threshold) {
                $shipping = 49; // Standard shipping cost in SEK
            }
        }

        $total = $subtotal + $tax_total + $shipping;

        return array(
            'subtotal' => $subtotal,
            'tax_total' => $tax_total,
            'shipping' => $shipping,
            'total' => $total,
            'subtotal_formatted' => $this->settings->format_price($subtotal),
            'tax_total_formatted' => $this->settings->format_price($tax_total),
            'shipping_formatted' => $this->settings->format_price($shipping),
            'total_formatted' => $this->settings->format_price($total),
        );
    }

    /**
     * Get cart item count
     *
     * @return int Item count.
     */
    public function get_cart_count() {
        $count = 0;

        foreach ($this->cart as $item) {
            $count += $item['quantity'];
        }

        return $count;
    }

    /**
     * Get cart item key
     *
     * @param int $product_id Product ID.
     * @return string Cart key.
     */
    private function get_cart_item_key($product_id) {
        return 'product_' . $product_id;
    }

    // =========================================================================
    // ORDER METHODS
    // =========================================================================

    /**
     * Create order from cart
     *
     * @param array $data Order data.
     * @return int|false Order ID or false.
     */
    public function create_order($data) {
        $cart = $this->get_cart();

        if (empty($cart)) {
            return false;
        }

        $totals = $this->get_cart_totals();

        $schema = array(
            'user_id' => 'int',
            'payment_method' => 'text',
            'billing_address' => 'json',
            'shipping_address' => 'json',
            'customer_note' => 'textarea',
            'coupon_code' => 'text',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // Generate order number
        $order_number = $this->generate_order_number();

        $order_data = array(
            'order_number' => $order_number,
            'user_id' => $sanitized['user_id'] ?? (is_user_logged_in() ? get_current_user_id() : null),
            'status' => 'pending',
            'subtotal' => $totals['subtotal'],
            'tax_total' => $totals['tax_total'],
            'shipping_total' => $totals['shipping'],
            'discount_total' => 0,
            'total' => $totals['total'],
            'currency' => 'SEK',
            'payment_method' => $sanitized['payment_method'] ?? '',
            'payment_status' => 'pending',
            'billing_address' => $sanitized['billing_address'] ?? '',
            'shipping_address' => $sanitized['shipping_address'] ?? '',
            'customer_note' => $sanitized['customer_note'] ?? '',
            'coupon_code' => $sanitized['coupon_code'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        );

        $this->db->begin_transaction();

        try {
            $order_id = $this->db->insert('orders', $order_data);

            if (!$order_id) {
                throw new \Exception('Failed to create order');
            }

            // Create order items
            foreach ($cart as $item) {
                $item_data = array(
                    'order_id' => $order_id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'tax' => $item['subtotal'] * ($item['product']->tax_rate / 100),
                    'total' => $item['subtotal'],
                    'metadata' => wp_json_encode(array(
                        'sku' => $item['product']->sku,
                    )),
                );

                $this->db->insert('order_items', $item_data);

                // Reduce stock
                if ($item['product']->manage_stock) {
                    $this->reduce_stock($item['product']->id, $item['quantity']);
                }
            }

            $this->db->commit();

            // Clear cart
            $this->clear_cart();

            $this->log_activity('order', $order_id, 'created', 'Order created');
            do_action('semigapp_order_created', $order_id);

            return $order_id;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Generate order number
     *
     * @return string Order number.
     */
    private function generate_order_number() {
        $prefix = $this->get_setting('order_number_prefix', 'ORD-');
        $number = get_option('semigapp_last_order_number', 1000);
        $number++;
        update_option('semigapp_last_order_number', $number);

        return $prefix . $number;
    }

    /**
     * Update order status
     *
     * @param int    $order_id  Order ID.
     * @param string $status    New status.
     * @param string $note      Optional note.
     * @return bool True on success.
     */
    public function update_order_status($order_id, $status, $note = '') {
        $order = $this->get_order($order_id);

        if (!$order) {
            return false;
        }

        $old_status = $order->status;

        $update_data = array('status' => $status);

        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }

        if ($note) {
            $update_data['admin_note'] = $order->admin_note . "\n" . current_time('mysql') . ': ' . $note;
        }

        $result = $this->db->update('orders', $update_data, array('id' => $order_id));

        if ($result !== false) {
            $this->log_activity('order', $order_id, 'status_changed', "Status changed from {$old_status} to {$status}");
            do_action('semigapp_order_status_changed', $order_id, $status, $old_status);
        }

        return $result !== false;
    }

    /**
     * Update payment status
     *
     * @param int    $order_id       Order ID.
     * @param string $status         Payment status.
     * @param string $transaction_id Transaction ID.
     * @return bool True on success.
     */
    public function update_payment_status($order_id, $status, $transaction_id = '') {
        $update_data = array('payment_status' => $status);

        if ($transaction_id) {
            $update_data['transaction_id'] = $transaction_id;
        }

        return $this->db->update('orders', $update_data, array('id' => $order_id)) !== false;
    }

    /**
     * Get order
     *
     * @param int $order_id Order ID.
     * @return object|null Order or null.
     */
    public function get_order($order_id) {
        $order = $this->db->get_row('orders', array('id' => $order_id));

        if ($order) {
            $order->billing_address = json_decode($order->billing_address, true) ?: array();
            $order->shipping_address = json_decode($order->shipping_address, true) ?: array();
            $order->items = $this->get_order_items($order_id);
            $order->customer = $order->user_id ? get_userdata($order->user_id) : null;
        }

        return $order;
    }

    /**
     * Get order by order number
     *
     * @param string $order_number Order number.
     * @return object|null Order or null.
     */
    public function get_order_by_number($order_number) {
        $order = $this->db->get_row('orders', array('order_number' => $order_number));

        if ($order) {
            $order->items = $this->get_order_items($order->id);
        }

        return $order;
    }

    /**
     * Get order items
     *
     * @param int $order_id Order ID.
     * @return array Order items.
     */
    public function get_order_items($order_id) {
        $items = $this->db->get_results('order_items', array(
            'where' => array('order_id' => $order_id),
        ));

        if (empty($items)) {
            return array();
        }

        // Batch load products to avoid N+1 query
        $product_ids = array_unique(wp_list_pluck($items, 'product_id'));
        $products = $this->get_products_by_ids($product_ids);
        $products_by_id = array();
        foreach ($products as $product) {
            $products_by_id[$product->id] = $product;
        }

        foreach ($items as &$item) {
            $item->metadata = json_decode($item->metadata, true) ?: array();
            $item->product = $products_by_id[$item->product_id] ?? null;
        }

        return $items;
    }

    /**
     * Get products by IDs (batch query)
     *
     * @param array $ids Product IDs.
     * @return array Products.
     */
    private function get_products_by_ids($ids) {
        if (empty($ids)) {
            return array();
        }

        global $wpdb;
        $table = $this->db->get_table('products');
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE id IN ($placeholders)",
            $ids
        ));

        foreach ($products as &$product) {
            $product = $this->prepare_product($product);
        }

        return $products;
    }

    /**
     * Get orders
     *
     * @param array $args Query arguments.
     * @return array Orders.
     */
    public function get_orders($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $orders = $this->db->get_results('orders', $args);

        if (empty($orders)) {
            return array();
        }

        // Batch load all order items to avoid N+1 query
        $order_ids = wp_list_pluck($orders, 'id');
        $all_items = $this->get_items_for_orders($order_ids);

        // Group items by order_id
        $items_by_order = array();
        foreach ($all_items as $item) {
            if (!isset($items_by_order[$item->order_id])) {
                $items_by_order[$item->order_id] = array();
            }
            $items_by_order[$item->order_id][] = $item;
        }

        foreach ($orders as &$order) {
            $order->billing_address = json_decode($order->billing_address, true) ?: array();
            $order->shipping_address = json_decode($order->shipping_address, true) ?: array();
            $order->items = $items_by_order[$order->id] ?? array();
            $order->customer = $order->user_id ? get_userdata($order->user_id) : null;
        }

        return $orders;
    }

    /**
     * Get items for multiple orders (batch query)
     *
     * @param array $order_ids Order IDs.
     * @return array Order items with products.
     */
    private function get_items_for_orders($order_ids) {
        if (empty($order_ids)) {
            return array();
        }

        global $wpdb;
        $items_table = $this->db->get_table('order_items');
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE order_id IN ($placeholders)",
            $order_ids
        ));

        if (empty($items)) {
            return array();
        }

        // Batch load all products
        $product_ids = array_unique(wp_list_pluck($items, 'product_id'));
        $products = $this->get_products_by_ids($product_ids);
        $products_by_id = array();
        foreach ($products as $product) {
            $products_by_id[$product->id] = $product;
        }

        foreach ($items as &$item) {
            $item->metadata = json_decode($item->metadata, true) ?: array();
            $item->product = $products_by_id[$item->product_id] ?? null;
        }

        return $items;
    }

    /**
     * Get user orders
     *
     * @param int $user_id User ID.
     * @return array Orders.
     */
    public function get_user_orders($user_id) {
        return $this->get_orders(array(
            'where' => array('user_id' => $user_id),
        ));
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('semigapp_shop', array($this, 'shop_shortcode'));
        add_shortcode('semigapp_product', array($this, 'product_shortcode'));
        add_shortcode('semigapp_cart', array($this, 'cart_shortcode'));
        add_shortcode('semigapp_checkout', array($this, 'checkout_shortcode'));
        add_shortcode('semigapp_order_confirmation', array($this, 'order_confirmation_shortcode'));
        add_shortcode('semigapp_my_orders', array($this, 'my_orders_shortcode'));
    }

    /**
     * Shop shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function shop_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'columns' => 3,
            'category' => '',
            'featured' => '',
        ), $atts);

        $args = array(
            'limit' => intval($atts['limit']),
        );

        if ($atts['featured'] === 'yes') {
            $args['where']['featured'] = 1;
        }

        $products = $this->get_products($args);

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/shop/products.php';
        return ob_get_clean();
    }

    /**
     * Single product shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function product_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'slug' => '',
        ), $atts);

        if ($atts['slug']) {
            $product = $this->get_product_by_slug($atts['slug']);
        } else {
            $product = $this->get_product(intval($atts['id']));
        }

        if (!$product) {
            return '<p>' . __('Product not found.', 'semigapp') . '</p>';
        }

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/shop/single-product.php';
        return ob_get_clean();
    }

    /**
     * Cart shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function cart_shortcode($atts) {
        $cart = $this->get_cart();
        $totals = $this->get_cart_totals();

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/shop/cart.php';
        return ob_get_clean();
    }

    /**
     * Checkout shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function checkout_shortcode($atts) {
        $cart = $this->get_cart();

        if (empty($cart)) {
            return '<p>' . __('Your cart is empty.', 'semigapp') . '</p>';
        }

        $totals = $this->get_cart_totals();
        $gateways = $this->get_available_gateways();

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/shop/checkout.php';
        return ob_get_clean();
    }

    /**
     * Order confirmation shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function order_confirmation_shortcode($atts) {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = $this->get_order($order_id);

        if (!$order) {
            return '<p>' . __('Order not found.', 'semigapp') . '</p>';
        }

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/shop/order-confirmation.php';
        return ob_get_clean();
    }

    /**
     * My orders shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function my_orders_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your orders.', 'semigapp') . '</p>';
        }

        $orders = $this->get_user_orders(get_current_user_id());

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/shop/my-orders.php';
        return ob_get_clean();
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * Handle cart AJAX
     */
    public function handle_cart_ajax() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $action = isset($_POST['cart_action']) ? sanitize_text_field($_POST['cart_action']) : '';

        switch ($action) {
            case 'add':
                $result = $this->add_to_cart(intval($_POST['product_id']), intval($_POST['quantity'] ?? 1));
                break;
            case 'update':
                $result = $this->update_cart_item(sanitize_text_field($_POST['cart_key']), intval($_POST['quantity']));
                break;
            case 'remove':
                $result = $this->remove_from_cart(sanitize_text_field($_POST['cart_key']));
                break;
            case 'clear':
                $this->clear_cart();
                $result = true;
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'semigapp')));
                return;
        }

        if ($result) {
            wp_send_json_success(array(
                'cart' => $this->get_cart(),
                'totals' => $this->get_cart_totals(),
                'count' => $this->get_cart_count(),
            ));
        } else {
            wp_send_json_error(array('message' => __('Operation failed', 'semigapp')));
        }
    }

    /**
     * Handle checkout AJAX
     */
    public function handle_checkout() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $order_id = $this->create_order($_POST);

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Could not create order.', 'semigapp')));
        }

        $order = $this->get_order($order_id);
        $gateway = $this->get_gateway($order->payment_method);

        if ($gateway) {
            $payment_result = $gateway->process_payment($order);

            if ($payment_result['success']) {
                wp_send_json_success(array(
                    'order_id' => $order_id,
                    'redirect' => $payment_result['redirect'] ?? add_query_arg('order_id', $order_id, $this->settings->get_page_url('checkout')),
                ));
            } else {
                wp_send_json_error(array('message' => $payment_result['message']));
            }
        } else {
            wp_send_json_success(array(
                'order_id' => $order_id,
                'redirect' => add_query_arg('order_id', $order_id, $this->settings->get_page_url('checkout')),
            ));
        }
    }

    /**
     * Handle payment callback
     */
    public function handle_payment_callback() {
        $gateway_id = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
        $gateway = $this->get_gateway($gateway_id);

        if ($gateway) {
            $gateway->handle_callback();
        }

        wp_die('Invalid callback');
    }

    // =========================================================================
    // NOTIFICATIONS
    // =========================================================================

    /**
     * Notify order created
     *
     * @param int $order_id Order ID.
     */
    public function notify_order_created($order_id) {
        $order = $this->get_order($order_id);

        if (!$order) {
            return;
        }

        $email = $order->customer ? $order->customer->user_email : ($order->billing_address['email'] ?? '');

        if ($email) {
            $this->send_notification('order_confirmation', $email, array(
                'order_number' => $order->order_number,
                'order_total' => $this->settings->format_price($order->total),
                'first_name' => $order->customer ? $order->customer->first_name : ($order->billing_address['first_name'] ?? ''),
            ));
        }

        // Notify admin
        $this->send_notification('order_confirmation', get_option('admin_email'), array(
            'order_number' => $order->order_number,
            'order_total' => $this->settings->format_price($order->total),
            'first_name' => 'Admin',
        ));
    }

    /**
     * Notify order status changed
     *
     * @param int    $order_id   Order ID.
     * @param string $new_status New status.
     * @param string $old_status Old status.
     */
    public function notify_order_status_changed($order_id, $new_status, $old_status) {
        $order = $this->get_order($order_id);

        if (!$order) {
            return;
        }

        $email = $order->customer ? $order->customer->user_email : ($order->billing_address['email'] ?? '');

        if (!$email) {
            return;
        }

        $template = '';

        switch ($new_status) {
            case 'processing':
                $template = 'order_processing';
                break;
            case 'shipped':
                $template = 'order_shipped';
                break;
            case 'completed':
                $template = 'order_completed';
                break;
            case 'refunded':
                $template = 'order_refunded';
                break;
        }

        if ($template) {
            $this->send_notification($template, $email, array(
                'order_number' => $order->order_number,
                'first_name' => $order->customer ? $order->customer->first_name : ($order->billing_address['first_name'] ?? ''),
            ));
        }
    }
}
