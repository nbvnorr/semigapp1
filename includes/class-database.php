<?php
/**
 * Database Handler
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Database
 *
 * Handles database operations
 */
class Database {

    /**
     * Instance
     *
     * @var Database
     */
    private static $instance = null;

    /**
     * Table prefix
     *
     * @var string
     */
    private $prefix;

    /**
     * Get instance
     *
     * @return Database
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'semigapp_';
    }

    /**
     * Get table name
     *
     * @param string $table Table name without prefix.
     * @return string Full table name.
     */
    public function get_table($table) {
        return $this->prefix . $table;
    }

    /**
     * Insert a record
     *
     * @param string $table Table name.
     * @param array  $data  Data to insert.
     * @param array  $format Data format.
     * @return int|false Inserted ID or false on failure.
     */
    public function insert($table, $data, $format = null) {
        global $wpdb;

        $result = $wpdb->insert($this->get_table($table), $data, $format);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update a record
     *
     * @param string $table Table name.
     * @param array  $data  Data to update.
     * @param array  $where Where conditions.
     * @param array  $format Data format.
     * @param array  $where_format Where format.
     * @return int|false Number of rows updated or false on failure.
     */
    public function update($table, $data, $where, $format = null, $where_format = null) {
        global $wpdb;

        return $wpdb->update($this->get_table($table), $data, $where, $format, $where_format);
    }

    /**
     * Delete a record
     *
     * @param string $table Table name.
     * @param array  $where Where conditions.
     * @param array  $where_format Where format.
     * @return int|false Number of rows deleted or false on failure.
     */
    public function delete($table, $where, $where_format = null) {
        global $wpdb;

        return $wpdb->delete($this->get_table($table), $where, $where_format);
    }

    /**
     * Get a single row
     *
     * @param string $table   Table name.
     * @param array  $where   Where conditions.
     * @param string $output  Output type.
     * @return object|array|null Result or null.
     */
    public function get_row($table, $where = array(), $output = OBJECT) {
        global $wpdb;

        $sql = "SELECT * FROM " . $this->get_table($table);

        if (!empty($where)) {
            $conditions = array();
            $values = array();

            foreach ($where as $column => $value) {
                if (is_null($value)) {
                    $conditions[] = "`$column` IS NULL";
                } else {
                    $conditions[] = "`$column` = %s";
                    $values[] = $value;
                }
            }

            $sql .= " WHERE " . implode(' AND ', $conditions);

            if (!empty($values)) {
                $sql = $wpdb->prepare($sql, $values);
            }
        }

        return $wpdb->get_row($sql, $output);
    }

    /**
     * Get multiple rows
     *
     * @param string $table   Table name.
     * @param array  $args    Query arguments.
     * @param string $output  Output type.
     * @return array Results.
     */
    public function get_results($table, $args = array(), $output = OBJECT) {
        global $wpdb;

        $defaults = array(
            'where' => array(),
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
            'search' => '',
            'search_columns' => array(),
        );

        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM " . $this->get_table($table);
        $values = array();

        // Where conditions
        $conditions = array();

        if (!empty($args['where'])) {
            foreach ($args['where'] as $column => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $conditions[] = "`$column` IN ($placeholders)";
                    $values = array_merge($values, $value);
                } elseif (is_null($value)) {
                    $conditions[] = "`$column` IS NULL";
                } else {
                    $conditions[] = "`$column` = %s";
                    $values[] = $value;
                }
            }
        }

        // Search
        if (!empty($args['search']) && !empty($args['search_columns'])) {
            $search_conditions = array();
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';

            foreach ($args['search_columns'] as $column) {
                $search_conditions[] = "`$column` LIKE %s";
                $values[] = $search_term;
            }

            if (!empty($search_conditions)) {
                $conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Order
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if ($orderby) {
            $sql .= " ORDER BY $orderby";
        }

        // Limit
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, $output);
    }

    /**
     * Count rows
     *
     * @param string $table Table name.
     * @param array  $where Where conditions.
     * @return int Count.
     */
    public function count($table, $where = array()) {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM " . $this->get_table($table);
        $values = array();

        if (!empty($where)) {
            $conditions = array();

            foreach ($where as $column => $value) {
                if (is_null($value)) {
                    $conditions[] = "`$column` IS NULL";
                } else {
                    $conditions[] = "`$column` = %s";
                    $values[] = $value;
                }
            }

            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Run a raw query
     *
     * @param string $sql SQL query.
     * @return mixed Query result.
     */
    public function query($sql) {
        global $wpdb;
        return $wpdb->query($sql);
    }

    /**
     * Get last insert ID
     *
     * @return int Last insert ID.
     */
    public function get_insert_id() {
        global $wpdb;
        return $wpdb->insert_id;
    }

    /**
     * Get last error
     *
     * @return string Last error message.
     */
    public function get_last_error() {
        global $wpdb;
        return $wpdb->last_error;
    }

    /**
     * Begin transaction
     */
    public function begin_transaction() {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    }

    /**
     * Commit transaction
     */
    public function commit() {
        global $wpdb;
        $wpdb->query('COMMIT');
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        global $wpdb;
        $wpdb->query('ROLLBACK');
    }
}
