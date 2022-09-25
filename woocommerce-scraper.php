<?php
/**
 * Plugin Name: WooCommerce Scraper
 * Plugin URI: https://lienlau.com
 * Description: The best scraping plugin for WooCommerce
 * Version: 1.0.1
 * Author: Nguyen Quan
 * Author URI: https://lienlau.com
 * License: A "Slug" license name e.g. GPL2
 * Text Domain: woocommerce-scraper
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WooCommerce_Scraper')) {
    class WooCommerce_Scraper
    {
        public static $instance = null;

        public $file;
        public $slug;
        public $basename;
        public $plugin_base_uri;
        public $plugin_directory;
        public $plugin_directory_uri;
        public $version;

        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new WooCommerce_Scraper();
                self::$instance->setup_globals();

                if (!function_exists('is_plugin_active')) {
                    require_once(ABSPATH . '/wp-admin/includes/plugin.php');
                }

                if (is_plugin_active('woocommerce/woocommerce.php')) {
                    self::$instance->includes();
                    self::$instance->actions();
                }
            }

            return self::$instance;
        }

        private function setup_globals()
        {
            $this->file = __FILE__;
            $this->slug = 'woocommerce-scraper';
            $this->basename = plugin_basename($this->file);
            $this->plugin_directory = plugin_dir_path($this->file);
            $this->plugin_directory_uri = plugin_dir_url($this->file);
//            $this->plugin_base_uri = 'http://localhost/wordpress-server';
            $this->plugin_base_uri = 'https://lienlau.com';

            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . '/wp-admin/includes/plugin.php');
            }

            $plugin_data = get_plugin_data($this->file);
            $this->version = $plugin_data['Version'];
        }

        private function includes()
        {
            require_once 'inc/functions.php';
            require_once 'inc/class.ws-scrapers.php';
            require_once 'inc/class.ws-settings.php';
//            require_once 'inc/class.ws-addons.php';
            require_once 'inc/class.ws-api.php';
            require_once 'inc/class.ws-update.php';

            new WS_Scraper_Post();
            new WS_API();
            new WS_Settings();
//            new WS_Addons();
            new WS_Update();
        }

        private function actions()
        {
            add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
            add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        }

        public function plugin_action_links($links_array, $plugin_file)
        {
            if ($plugin_file !== plugin_basename(__FILE__)) {
                return $links_array;
            }

            array_unshift($links_array, '<a href="' . admin_url('edit.php?post_type=ws-scrapers&page=woocommerce-scraper-settings') . '">' . esc_html__('Settings', 'woocommerce-scraper') . '</a>');

            return $links_array;
        }

        public function plugin_row_meta($plugin_meta, $plugin_file)
        {
            if ($plugin_file !== plugin_basename(__FILE__)) {
                return $plugin_meta;
            }

            $plugin_meta[] = '<a href="https://github.com/vanquan805/woocommerce-scraper">' . esc_html__('Github', 'woocommerce-scraper') . '</a>';
            $plugin_meta[] = '<a href="https://t.me/quannv27" title="' . esc_html__('Support.', 'woocommerce-scraper') . '">' . esc_html__('Support', 'woocommerce-scraper') . '</a>';
            $plugin_meta[] = '<a href="mailto:vanquan805@gmail.com" title="' . esc_html__('Send an email to the developer.', 'woocommerce-scraper') . '">' . esc_html__('Contact', 'woocommerce-scraper') . '</a>';

            return $plugin_meta;
        }
    }

}

if (!function_exists('woocommerce_scraper')) {
    function woocommerce_scraper()
    {
        return WooCommerce_Scraper::instance();
    }

    $GLOBALS['woocommerce_scraper'] = woocommerce_scraper();
}