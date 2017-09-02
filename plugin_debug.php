<?php
/*
Plugin Name: Plugin Debugging Tool
Description: Attempt to automatically de-activate plugins that are causing errors. This must be installed in the mu-plugins directory.
Author: Chase C. Miller
Version: 1.0
Author URI: http://crumbls.com
*/

namespace Crumbls\Debug\Plugins;

error_reporting(E_ALL);
ini_set('display_errors', '1');

class Plugin
{
    protected static $errHandler = false;
    protected static $errLog = [];

    public function __construct()
    {
    }

    public static function getInstance()
    {
        static $instance;
        $class = get_called_class();
        if (!$instance instanceof $class) {
            $instance = new $class;

            add_action('admin_notices', [get_called_class(), 'adminNotice']);

            add_action('admin_enqueue_scripts', [get_called_class(), 'adminEnqueue'], 10, 1);

            self::$errHandler = set_error_handler([get_called_class(), 'errorHandler']);
            set_exception_handler([get_called_class(), 'errorHandler']);

            error_reporting(E_ALL);

            add_filter('option_active_plugins', [get_called_class(), 'filterActivePlugins'], PHP_INT_MAX, 1);
            add_action('shutdown', [get_called_class(), 'wordpressShutdown'], PHP_INT_MAX);
            register_shutdown_function([get_called_class(), 'systemShutdown']);
        }
        return $instance;
    }

    /**
     * Tied to filter "option_active_plugins"
     * @param array $plugins
     * @return array
     */
    public static function filterActivePlugins($plugins = [])
    {
        global $wp_object_cache;
        if (!$dis = get_option('disabled_plugins', [])) {
            return $plugins;
        }

        /**
         * Re-enable plugins when activate is clicked again.
         * This ugly and doesn't verify the user can do it, but it works for now.
         */
        if (
            is_admin()
            &&
            array_key_exists('plugin', $_REQUEST)
            &&
            array_key_exists('action', $_REQUEST)
            &&
            is_string($_REQUEST['action'])
            &&
            $_REQUEST['action'] == 'activate'
            &&
            strpos(basename($_SERVER['REQUEST_URI']), 'plugins.php') === 0
        ) {
            $dis = array_filter($dis, function ($e) {
                return strtolower($e[2]) != strtolower($_REQUEST['plugin']);
            });

            update_option('disabled_plugins', $dis, true);
        }

        // Return plugins, with disabled not activated.
        $plugins = array_diff($plugins, array_column($dis, 2));
        return $plugins;
    }

    /**
     * Handle Shutdowns
     * @return bool
     */
    public static function wordpressShutdown()
    {
        // The following plugins gave a horrible error.
        if (!self::$errLog) {
            return true;
        }
        $option = get_option('disabled_plugins', []);
        $option = array_column($option, null, 'f');
        foreach (self::$errLog as $err) {
            if (array_key_exists($err[2], $option)) {
                continue;
            }
            $option[$err[2]] = $err;
        }
        update_option('disabled_plugins', $option, true);
        return true;
    }

    /**
     * Tied to register_shutdown_function
     * Not currently used.
     */
    public static function systemShutdown()
    {
//        echo __METHOD__;
    }

    /**
     * Error handling.
     */
    public static function errorHandler($n = null, $s = null, $f = null, $ln = null)
    {
        if (is_object($n) && get_class($n) == 'Exception') {
            $s = $n->getMessage();
            $f = $n->getFile();
            $ln = $n->getLine();
            $n = $n->getCode();
        }

        if (
            strpos($f, WP_CONTENT_DIR) !== 0
            ||
            strpos($f, WP_CONTENT_DIR . '/plugins/') !== 0
        ) {
            // Send to default error handler.
            $func = self::$errHandler;
            print_r($func);
            echo $n;
            return call_user_func($func, $n, $s, $f, $ln);
        }
        $f = substr($f, strlen(WP_CONTENT_DIR . '/plugins/'));
        self::$errLog[] = [$n, $s, $f, $ln];
        return true;
    }

    /**
     * Admin Notices
     */
    public static function adminNotice()
    {
        if (!stripos(__FILE__, '/mu-plugins/') || true) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('Plugin Debugging Tool must be moved to mu-plugins to work.', __NAMESPACE__); ?></p>
            </div>
            <?php

        }
    }


    /**
     * Enqueue special CSS on our plugin page to let users know what plugins are busted.
     * @param null $hook
     */
    public static function adminEnqueue($hook = null)
    {
        if ($hook != 'plugins.php') {
            return;
        }
        if (!$option = get_option('disabled_plugins', [])) {
            return;
        }
        echo '<style>';
        foreach ($option as $e) {
            printf('.plugins tr[data-plugin="%s"] { background-color: rgba(255,0,0,0.5) !important; }', $e[2]);
        }
        echo '</style>';
    }
}

Plugin::getInstance();
