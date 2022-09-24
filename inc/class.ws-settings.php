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

if (!class_exists('WS_Settings')) {
    class WS_Settings
    {
        public function __construct()
        {
            add_action('admin_menu', array($this, 'settings_page'));
            add_action('admin_init', array($this, 'settings_init'));
            add_action('admin_notices', array($this, 'admin_notice'));
        }

        function settings_init()
        {
            register_setting('woocommerce-scraper', 'woocommerce-scraper_options');

            add_settings_section(
                'woocommerce-scraper_license',
                __('License', 'woocommerce-scraper'), array($this, 'add_license_section'),
                'woocommerce-scraper'
            );

            add_settings_field(
                '_ws_license_key',
                __('License Key', 'woocommerce-scraper'),
                array($this, 'add_field'),
                'woocommerce-scraper',
                'woocommerce-scraper_license',
                array(
                    'label_for' => '_ws_license_key',
                    'type' => 'text',
                    'placeholder' => '5569823414:AAEctHu8c5uhQSW_4CP7dULaPWc_orgxxx',
                    'description' => __('Enter the license key that you have bought.', 'woocommerce-scraper')
                )
            );
        }

        function add_license_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Set license key for plugin', 'woocommerce-scraper'); ?></p>
            <?php
        }

        function add_api_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Set API Key to store products', 'woocommerce-scraper'); ?></p>
            <?php
        }

        function add_field($args)
        {
            // Get the value of the setting we've registered with register_setting()
            $options = get_option('woocommerce-scraper_options');
            $args = array_merge($args, array('options' => $options));

            require WooCommerce_Scraper::instance()->plugin_directory . '/templates/settings/field.php';
        }

        function settings_page()
        {
            add_submenu_page(
                'edit.php?post_type=ws-scrapers',
                __('Settings - WooCommerce Scraper', 'woocommerce-scraper'),
                __('Settings', 'woocommerce-scraper'),
                'manage_options',
                'woocommerce-scraper-settings',
                array($this, 'render_settings_page')
            );
        }

        function render_settings_page()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            // add error/update messages

            // check if the user have submitted the settings
            // WordPress will add the "settings-updated" $_GET parameter to the url
            if (isset($_GET['settings-updated'])) {
                // add settings saved message with the class of "updated"
                add_settings_error('woocommerce-scraper_messages', 'woocommerce-scraper_messages', __('Settings Saved'), 'updated');
            }

            // show error/update messages
            settings_errors('woocommerce-scraper_messages');

            require_once WooCommerce_Scraper::instance()->plugin_directory . '/templates/settings-page.php';
        }

        function admin_notice()
        {
            $screen = get_current_screen();
            if ($screen->post_type === 'ws-scrapers') {
                $license = ws_get_license_key();

                if ((!isset($license) || strlen($license) === 0)) {
                    $class = 'notice notice-warning';
                    $message = __('You are using Woocommerce Free version. For the best experience, you should ', 'woocommerce-scraper');
                    $upgrade_message = __('Upgrade to Premium.', 'woocommerce-scraper');

                    printf('<div class="%1$s"><p>%2$s<a href="https://lienlau.com"><b>%3$s</b></a></p></div>', esc_attr($class), esc_html($message), esc_html($upgrade_message));
                } else if (ws_is_license_key_expired()) {
                    $class = 'notice notice-error';
                    $message = __('Your license key is expired. Please renew your license key for ', 'woocommerce-scraper');
                    $upgrade_message = __('Upgrading to Premium.', 'woocommerce-scraper');

                    printf('<div class="%1$s"><p>%2$s<a href="https://lienlau.com"><b>%3$s</b></a></p></div>', esc_attr($class), esc_html($message), esc_html($upgrade_message));

                    $class = 'notice notice-warning';
                    $message = __('You are using Woocommerce Free version. For the best experience, you should ', 'woocommerce-scraper');
                    $upgrade_message = __('Upgrade to Premium.', 'woocommerce-scraper');

                    printf('<div class="%1$s"><p>%2$s<a href="https://lienlau.com"><b>%3$s</b></a></p></div>', esc_attr($class), esc_html($message), esc_html($upgrade_message));
                }
            }
        }
    }
}