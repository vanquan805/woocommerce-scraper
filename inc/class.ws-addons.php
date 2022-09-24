<?php
/**
 * Created by PhpStorm.
 * User: quannv27
 * Date: 17/09/2022
 * Time: 11:26
 * To change this template use File | Settings | File Templates.
 */


if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WS_Addons')) {
    class WS_Addons
    {
        public function __construct()
        {
            add_action('admin_menu', array($this, 'settings_page'));
        }

        function settings_page()
        {
            add_submenu_page(
                'edit.php?post_type=ws-scrapers',
                __('Addons/Support - WooCommerce Scraper', 'woocommerce-scraper'),
                __('Addons/Support', 'woocommerce-scraper'),
                'manage_options',
                'woocommerce-scraper-addons',
                array($this, 'render_settings_page')
            );
        }

        function render_settings_page()
        {
            if (!current_user_can('manage_options')) {
                return;
            }
            require_once WooCommerce_Scraper::instance()->plugin_directory . '/templates/addons-support-page.php';
        }
    }
}