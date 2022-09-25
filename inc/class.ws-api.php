<?php
/**
 * Created by PhpStorm.
 * User: quannv27
 * Date: 21/09/2022
 * Time: 07:56
 * To change this template use File | Settings | File Templates.
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WS_API')) {
    class WS_API
    {

        public $namespace;
        public $rest_base;

        public function __construct()
        {
            $this->namespace = 'woocommerce-scraper/v1';
            $this->rest_base = 'products';

            add_action('rest_api_init', array($this, 'register_routes'));
        }

        public function register_routes()
        {

            register_rest_route($this->namespace, $this->rest_base . '/create', array(
                'methods' => 'POST',
                'callback' => array($this, 'create_product'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        }

        public function create_product($request): WP_REST_Response
        {
            $body = $request->get_body_params();
            $json = $request->get_json_params();

            $body = count($body) > 0 ? $body : $json;

            $product_id = $this->get_exist_product($body['source_url']);
            $new_product = $product_id !== null ? wc_get_product($product_id) : new WC_Product_Simple();

            $new_product->set_name($body['name']);
            $new_product->set_sku($body['sku']);
            $new_product->set_price($body['price']);
            $new_product->set_regular_price($body['regular_price']);
            $new_product->set_sale_price($body['sale_price']);
            $new_product->set_category_ids($body['category_ids']);
            $new_product->set_short_description($body['short_description']);
            $new_product->set_description($body['description']);
            $new_product->set_image_id(ws_upload_from_url($body['product_image_url']));

            if (isset($body['product_gallery_urls']) && count($body['product_gallery_urls']) > 0) {
                $gallery_ids = [];

                foreach ($body['product_gallery_urls'] as $gallery_url) {
                    $gallery_ids[] = ws_upload_from_url($gallery_url);
                }

                if (count($gallery_ids) > 0)
                    $new_product->set_gallery_image_ids($gallery_ids);
            }

            if (isset($body['attributes']) && count($body['attributes']) > 0) {
                $attributes = array();

                foreach ($body['attributes'] as $_attribute) {
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_name($_attribute['name']);
                    $attribute->set_options($_attribute['options']);
                    $attribute->set_position(0);
                    $attribute->set_visible(true);
                    $attribute->set_variation($_attribute['variation'] ?? false);
                    $attributes[] = $attribute;
                }

                if (count($attributes) > 0)
                    $new_product->set_attributes($attributes);
            }

            $new_product->set_status('draft');

            $new_product->save();
            $product_id = $new_product->get_id();

            if (isset($product_id)) {
                update_post_meta($product_id, '_ws_source_url', $body['source_url']);
            }

            return new WP_REST_Response(array('status' => 'success', 'message' => __('Create product successfully!', 'woocommerce-scraper')), 200);
        }

        public function check_permissions($request): bool
        {
            $license_key = ws_get_license_key();
            $query = $request->get_query_params();

            if (!isset($license_key) || strlen($license_key) === 0 || !isset($query['api_key']))
                return false;

            return $license_key === $query['api_key'];
        }

        public function get_exist_product($source_url)
        {
            $args = array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => '_ws_source_url',
                        'value' => $source_url,
                        'compare' => '=',
                    )
                ),
                'post_status' => 'any'
            );

            $products = get_posts($args);

            return count($products) > 0 ? $products[0]->ID : null;
        }
    }
}