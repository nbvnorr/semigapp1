<?php
/**
 * Autoloader for SemigApp classes
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 *
 * PSR-4 compatible autoloader for the plugin
 */
class Autoloader {

    /**
     * Namespace prefix
     *
     * @var string
     */
    private static $prefix = 'SemigApp\\';

    /**
     * Base directory for the namespace
     *
     * @var string
     */
    private static $base_dir;

    /**
     * Register the autoloader
     */
    public static function register() {
        self::$base_dir = SEMIGAPP_PLUGIN_DIR . 'includes/';
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload callback
     *
     * @param string $class The fully-qualified class name.
     */
    public static function autoload($class) {
        // Check if the class uses our namespace prefix
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Convert namespace separators to directory separators
        $relative_path = strtolower(str_replace('\\', '/', $relative_class));

        // Convert class name to file name format (CamelCase to kebab-case with class- prefix)
        $parts = explode('/', $relative_path);
        $class_name = array_pop($parts);

        // Handle special naming conventions
        $file_name = self::class_to_filename($class_name);

        // Build the file path
        if (!empty($parts)) {
            $file = self::$base_dir . implode('/', $parts) . '/' . $file_name;
        } else {
            $file = self::$base_dir . $file_name;
        }

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }

    /**
     * Convert class name to file name
     *
     * @param string $class_name The class name.
     * @return string The file name.
     */
    private static function class_to_filename($class_name) {
        // Convert camelCase/PascalCase to kebab-case
        $name = preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name);
        $name = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $name);
        $name = strtolower($name);

        // Convert underscores to hyphens (for class names like Projects_Module)
        $name = str_replace('_', '-', $name);

        // Determine the prefix based on naming conventions
        if (strpos($class_name, 'interface') !== false || strpos($class_name, 'Interface') !== false) {
            return 'interface-' . str_replace('-interface', '', $name) . '.php';
        }

        if (strpos($class_name, 'trait') !== false || strpos($class_name, 'Trait') !== false) {
            return 'trait-' . str_replace('-trait', '', $name) . '.php';
        }

        return 'class-' . $name . '.php';
    }
}
