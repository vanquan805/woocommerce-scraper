<?php
/**
 * Created by PhpStorm.
 * User: quannv27
 * Date: 12/09/2022
 * Time: 20:25
 * To change this template use File | Settings | File Templates.
 */

if (!defined('ABSPATH')) {
    exit();
}

require_once WooCommerce_Scraper::instance()->plugin_directory . '/libs/simple_html_dom.php';

if (!class_exists('WS_Scraper_Post')) {
    class WS_Scraper_Post
    {
        public function __construct()
        {
            add_action('init', array($this, 'add_scraper_post'));
            add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
            add_action('save_post_ws-scrapers', array($this, 'save_post'));
            add_action('trashed_post', array($this, 'trashed_post'));
            add_action('untrash_post', array($this, 'untrash_post'));
            add_action('delete_post', array($this, 'delete_post'));
            add_action('before_delete_post', array($this, 'delete_post'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

            add_action('woocommerce_scraper', array($this, 'woocommerce_scraper'));
            register_activation_hook(WooCommerce_Scraper::instance()->file, array($this, 'activation'));
            register_deactivation_hook(WooCommerce_Scraper::instance()->file, array($this, 'deactivation'));
        }

        function activation()
        {
            if (!wp_next_scheduled('woocommerce_scraper')) {
                wp_schedule_event(time(), 'hourly', 'woocommerce_scraper');
            }
        }

        function deactivation()
        {
            wp_clear_scheduled_hook('woocommerce_scraper');
        }

        function woocommerce_scraper()
        {
            if (ws_is_license_key_expired()) {
                $scrapers = get_posts(array(
                    'post_type' => 'ws-scrapers'
                ));

                foreach ($scrapers as $scraper) {
                    $_ws_source_url = get_post_meta($scraper->ID, '_ws_source_url', true);
                    if (isset($_ws_source_url) && strlen($_ws_source_url) > 0) {
                        // get list product urls
                        $this->scrape_all_products($scraper, $_ws_source_url);
                    }
                }
            }
        }

        function scrape_all_products($scraper, $_ws_source_url)
        {
            if (isset($_ws_source_url) && strlen($_ws_source_url) > 0) {
                $response = wp_remote_get($_ws_source_url);
                $html = str_get_html($response['body']);

                $_wp_list_product_url_selectors = get_post_meta($scraper->ID, '_wp_list_product_url_selectors', true);
                $_ws_next_page_selector = get_post_meta($scraper->ID, '_ws_next_page_selector', true);

                $next_page_element = $html->find($_ws_next_page_selector);

                $next_page_url = isset($next_page_element) && isset($next_page_element[0]) ? $next_page_element[0]->getAttribute('href') : null;

                foreach ($html->find($_wp_list_product_url_selectors) as $product_url) {
                    $product_url = $product_url->getAttribute('href');
                    $this->scrape_single_product($scraper->ID, $product_url);
                }

                if (isset($next_page_url))
                    $this->scrape_all_products($scraper, $next_page_url);
            }
        }

        function scrape_single_product($scraper_id, $single_product_url)
        {
            try {

                $_ws_product_title_selector = get_post_meta($scraper_id, '_ws_product_title_selector', true);
                $_ws_product_image_selector = get_post_meta($scraper_id, '_ws_product_image_selector', true);
                $_ws_product_price_selector = get_post_meta($scraper_id, '_ws_product_price_selector', true);
                $_ws_product_regular_price_selector = get_post_meta($scraper_id, '_ws_product_regular_price_selector', true);
                $_ws_product_sale_price_selector = get_post_meta($scraper_id, '_ws_product_sale_price_selector', true);
                $_ws_product_short_description_selector = get_post_meta($scraper_id, '_ws_product_short_description_selector', true);
                $_ws_product_description_selector = get_post_meta($scraper_id, '_ws_product_description_selector', true);
                $_ws_category = get_post_meta($scraper_id, '_ws_category', true);
                $term = get_term_by('term_id', $_ws_category, 'product_cat');

                $args = array(
                    'post_type' => 'product',
                    'meta_query' => array(
                        array(
                            'key' => '_ws_source_url',
                            'value' => $single_product_url,
                            'compare' => '=',
                        ),
                    ),
                );

                $query = new WP_Query($args);

                if (!$query->have_posts()) {
                    $response = wp_remote_get($single_product_url);
                    $html = str_get_html($response['body']);

                    $price_element = isset($_ws_product_price_selector) && strlen($_ws_product_price_selector) > 0 ? $html->find($_ws_product_price_selector) : null;
                    $regular_price_element = isset($_ws_product_regular_price_selector) && strlen($_ws_product_regular_price_selector) > 0 ? $html->find($_ws_product_regular_price_selector) : null;
                    $sale_price_element = isset($_ws_product_sale_price_selector) && strlen($_ws_product_sale_price_selector) > 0 ? $html->find($_ws_product_sale_price_selector) : null;
                    $product_image_element = isset($_ws_product_image_selector) && strlen($_ws_product_image_selector) > 0 ? $html->find($_ws_product_image_selector) : null;
                    $short_description_element = isset($_ws_product_short_description_selector) && strlen($_ws_product_short_description_selector) > 0 ? $html->find($_ws_product_short_description_selector) : null;
                    $description_element = isset($_ws_product_description_selector) && strlen($_ws_product_description_selector) > 0 ? $html->find($_ws_product_description_selector) : null;

                    if (isset($price_element) && isset($price_element[0]))
                        preg_match('/\d+\.?\d+/', $price_element[0]->innertext, $price);

                    if (isset($regular_price_element) && isset($regular_price_element[0]))
                        preg_match('/\d+\.?\d+/', $regular_price_element[0]->innertext, $regular_price);

                    if (isset($sale_price_element) && isset($sale_price_element[0]))
                        preg_match('/\d+\.?\d+/', $sale_price_element[0]->innertext, $sale_price);

                    $new_product = new WC_Product_Simple();
                    $new_product->set_name($html->find($_ws_product_title_selector)[0]->innertext);

                    $new_product->set_price(isset($price) && isset($price[0]) ? str_replace('.', ',', $price[0]) : null);
                    $new_product->set_regular_price(isset($regular_price) && isset($regular_price[0]) ? str_replace('.', ',', $regular_price[0]) : null);
                    $new_product->set_sale_price(isset($sale_price) && isset($sale_price[0]) ? str_replace('.', ',', $sale_price[0]) : null);

                    $new_product->set_category_ids(array(isset($term) && isset($term->term_id) ? $term->term_id : null));

                    if (isset($short_description_element) && isset($short_description_element[0]))
                        $new_product->set_short_description($short_description_element[0]->innertext);

                    if (isset($description_element) && isset($description_element[0]))
                        $new_product->set_description($description_element[0]->innertext);

                    if (isset($product_image_element) && isset($product_image_element[0]))
                        $new_product->set_image_id(ws_upload_from_url($product_image_element[0]->getAttribute('src')));

                    $new_product->save();
                    $product_id = $new_product->get_id();

                    if (isset($product_id)) {
                        update_post_meta($product_id, '_ws_source_url', $single_product_url);
                    }
                }
            } catch (Exception $exception) {

            }
        }

        function add_scraper_post()
        {
            $labels = array(
                'name' => esc_html__('WooScrapers', 'woocommerce-scraper'),
                'singular_name' => esc_html__('Scrapers', 'woocommerce-scraper'),
                'menu_name' => esc_html__('WooScrapers', 'woocommerce-scraper'),
                'name_admin_bar' => esc_html__('Scrapers', 'woocommerce-scraper'),
                'add_new' => esc_html__('New', 'woocommerce-scraper'),
                'add_new_item' => esc_html__('New Scraper', 'woocommerce-scraper'),
                'edit_item' => esc_html__('Edit Scraper', 'woocommerce-scraper'),
                'all_items' => esc_html__('All', 'woocommerce-scraper')
            );

            $scraper = array(
                'labels' => $labels,
                'show_ui' => true,
                'public' => false,
                'has_archive' => false,
                'supports' => array('title')
            );

            register_post_type('ws-scrapers', $scraper);
        }

        function add_meta_boxes()
        {
            add_meta_box('ws-scraper-info', esc_html__('Scraper Config', 'woocommerce-scraper'), array($this, 'render_meta_box'), 'ws-scrapers', 'advanced', 'high');
        }

        function render_meta_box($post)
        {
            wp_nonce_field('ws_scraper_box', 'ws_scraper_box_nonce');
            include WooCommerce_Scraper::instance()->plugin_directory . 'templates/scraper-metabox.php';
        }

        function save_post($post_id)
        {

            $fields = [
                '_ws_category',
                '_ws_source_url',
                '_wp_list_product_url_selectors',
                '_ws_product_title_selector',
                '_ws_product_sku_selector',
                '_ws_product_image_selector',
                '_ws_product_gallery_selectors',
                '_ws_product_price_selector',
                '_ws_product_regular_price_selector',
                '_ws_product_sale_price_selector',
                '_ws_product_short_description_selector',
                '_ws_product_description_selector',
                '_ws_next_page_selector',
                '_ws_attributes',
                '_ws_currency_rate'
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST)) {
                    if ($field !== '_ws_attributes')
                        update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                    else {
                        $attributes = array_filter($_POST[$field], function ($attribute) {
                            return isset($attribute['name']) && strlen(trim($attribute['name'])) > 0 && isset($attribute['option_selector']) && strlen(trim($attribute['option_selector'])) > 0;
                        });

                        $attributes = array_map(function ($attribute) {
                            return array(
                                'name' => sanitize_text_field($attribute['name']),
                                'option_selector' => sanitize_text_field($attribute['option_selector']),
                            );
                        }, $attributes);

                        update_post_meta($post_id, $field, $attributes);
                    }
                }
            }

            ws_api_create_or_update_scraper($post_id);
        }

        function trashed_post($post_id)
        {
            $post_type = get_post_type($post_id);
            if ($post_type === 'ws-scrapers') {
                ws_api_trashed_scraper($post_id);
            }
        }

        function untrash_post($post_id)
        {
            $post_type = get_post_type($post_id);
            if ($post_type === 'ws-scrapers') {
                ws_api_untrash_scraper($post_id);
            }
        }

        function delete_post($post_id)
        {
            $post_type = get_post_type($post_id);
            if ($post_type === 'ws-scrapers') {
                ws_api_delete_scraper($post_id);
            }
        }

        function enqueue_scripts($hook)
        {
            $screen = get_current_screen();
            if ($screen->post_type === 'ws-scrapers' && ($hook === 'post-new.php' || $hook === 'post.php')) {
                wp_dequeue_script( 'autosave' );

                wp_enqueue_style('ws-scraper.css', WooCommerce_Scraper::instance()->plugin_directory_uri . '/assets/css/main.css', null, WooCommerce_Scraper::instance()->version, 'all');
                wp_enqueue_script('ws-scraper.js', WooCommerce_Scraper::instance()->plugin_directory_uri . '/assets/js/main.js', array('jquery'), WooCommerce_Scraper::instance()->version, true);
                $fields = apply_filters('ws-metabox-attribute-template-fields',
                    array(
                        array(
                            'label' => esc_html__('Attribute Name', 'woocommerce-scraper'),
                            'name' => "_ws_attributes[{index}][name]",
                            'type' => 'text',
                            'value' => '',
                            'placeholder' => 'Color'
                        ),
                        array(
                            'label' => esc_html__('Option Selector', 'woocommerce-scraper'),
                            'name' => "_ws_attributes[{index}][option_selector]",
                            'type' => 'text',
                            'value' => '',
                            'placeholder' => '.selector option'
                        )
                    ));

                wp_localize_script('ws-scraper.js', 'woocommerce_scraper',
                    array(
                        'attribute_form_template' => $this->get_template('templates/meta-boxes/metabox.php', $fields)
                    )
                );
            }
        }

        function get_template($template, $fields)
        {
            ob_start();
            include WooCommerce_Scraper::$instance->plugin_directory . $template;
            $content = ob_get_contents();
            ob_clean();

            return $content;
        }
    }
}