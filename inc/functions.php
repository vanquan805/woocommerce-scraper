<?php
/**
 * Created by PhpStorm.
 * User: quannv27
 * Date: 12/09/2022
 * Time: 20:22
 * To change this template use File | Settings | File Templates.
 */

function ws_upload_from_url($url, $title = null)
{
    require_once(ABSPATH . "/wp-load.php");
    require_once(ABSPATH . "/wp-admin/includes/image.php");
    require_once(ABSPATH . "/wp-admin/includes/file.php");
    require_once(ABSPATH . "/wp-admin/includes/media.php");

    // Download url to a temp file
    $tmp = download_url($url);
    if (is_wp_error($tmp)) return false;

    // Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
    $args = array(
        'name' => basename(parse_url($url, PHP_URL_PATH)),
        'tmp_name' => $tmp,
    );

    // Do the upload
    $attachment_id = media_handle_sideload($args, 0, $title);

    // If error uploading, delete the temp file abort
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return false;
    }

    // On success, return attachment ID as an int
    return $attachment_id;
}

function ws_get_scraper($post_id)
{
    $post = get_post($post_id);

    if (!isset($post)) return null;

    return array(
        'post_title' => $post->post_title,
        '_ws_source_id' => $post->ID,
        '_ws_category' => get_post_meta($post->ID, '_ws_category', true),
        '_ws_source_url' => get_post_meta($post->ID, '_ws_source_url', true),
        '_wp_list_product_url_selectors' => get_post_meta($post->ID, '_wp_list_product_url_selectors', true),
        '_ws_product_title_selector' => get_post_meta($post->ID, '_ws_product_title_selector', true),
        '_ws_product_image_selector' => get_post_meta($post->ID, '_ws_product_image_selector', true),
        '_ws_product_regular_price_selector' => get_post_meta($post->ID, '_ws_product_regular_price_selector', true),
        '_ws_product_sale_price_selector' => get_post_meta($post->ID, '_ws_product_sale_price_selector', true),
        '_ws_product_description_selector' => get_post_meta($post->ID, '_ws_product_description_selector', true),
        '_ws_next_page_selector' => get_post_meta($post->ID, '_ws_next_page_selector', true),
        '_ws_product_short_description_selector' => get_post_meta($post->ID, '_ws_product_short_description_selector', true),
        '_ws_product_gallery_selectors' => get_post_meta($post->ID, '_ws_product_gallery_selectors', true),
        '_ws_attributes' => get_post_meta($post->ID, '_ws_attributes', true),
        '_ws_product_price_selector' => get_post_meta($post->ID, '_ws_product_price_selector', true)
    );
}


function ws_get_license_key()
{
    $options = get_option('woocommerce-scraper_options');
    return $options['_ws_license_key'] ?? null;
}

function ws_api_get_license_key_info()
{
    $license_key = ws_get_license_key();

    if (!isset($license_key) || strlen($license_key) === 0)
        return true;

    $respose = wp_remote_post(WooCommerce_Scraper::instance()->plugin_base_uri . '/index.php?rest_route=/lienlau-plugins/v1/plugins/check_license', array(
        'method' => 'POST',
        'body' => array(
            'plugin_slug' => 'woocommerce-scraper',
            'license_key' => $license_key
        )
    ));

    return json_decode(wp_remote_retrieve_body($respose));
}

function ws_is_license_key_expired(): bool
{
    $info = ws_api_get_license_key_info();
    return (isset($info) && isset($info->status) && $info->status === 'success' && isset($info->expired)) ? $info->expired : true;
}

function ws_api_create_or_update_scraper($post_id)
{
    $scraper = ws_get_scraper($post_id);

    if (!ws_is_license_key_expired() && isset($scraper)) {

        $license_key = ws_get_license_key();
        $scraper['license_key'] = $license_key;

        $respose = wp_remote_post(WooCommerce_Scraper::instance()->plugin_base_uri . '/index.php?rest_route=/woocommerce-scraper/v1/scrapers/create', array(
            'method' => 'POST',
            'body' => $scraper
        ));

        return json_decode(wp_remote_retrieve_body($respose));
    }

    return false;
}

function ws_api_trashed_scraper($post_id)
{
    $scraper = ws_get_scraper($post_id);

    if (!ws_is_license_key_expired() && isset($scraper)) {
        $license_key = ws_get_license_key();

        $respose = wp_remote_post(WooCommerce_Scraper::instance()->plugin_base_uri . '/index.php?rest_route=/woocommerce-scraper/v1/scrapers/trashed', array(
            'method' => 'POST',
            'body' => array(
                'license_key' => $license_key,
                '_ws_source_id' => $scraper['_ws_source_id']
            )
        ));

        return json_decode(wp_remote_retrieve_body($respose));
    }

    return false;
}


function ws_api_untrash_scraper($post_id)
{
    $scraper = ws_get_scraper($post_id);

    if (!ws_is_license_key_expired() && isset($scraper)) {
        $license_key = ws_get_license_key();

        $respose = wp_remote_post(WooCommerce_Scraper::instance()->plugin_base_uri . '/index.php?rest_route=/woocommerce-scraper/v1/scrapers/untrash', array(
            'method' => 'POST',
            'body' => array(
                'license_key' => $license_key,
                '_ws_source_id' => $scraper['_ws_source_id']
            )
        ));

        return json_decode(wp_remote_retrieve_body($respose));
    }

    return false;
}

function ws_api_delete_scraper($post_id)
{
    $scraper = ws_get_scraper($post_id);

    if (!ws_is_license_key_expired() && isset($scraper)) {
        $license_key = ws_get_license_key();

        $respose = wp_remote_post(WooCommerce_Scraper::instance()->plugin_base_uri . '/index.php?rest_route=/woocommerce-scraper/v1/scrapers/delete', array(
            'method' => 'POST',
            'body' => array(
                'license_key' => $license_key,
                '_ws_source_id' => $scraper['_ws_source_id']
            )
        ));

        return json_decode(wp_remote_retrieve_body($respose));
    }

    return false;
}

function ws_metabox_get_general_fields($post)
{
    $categories = get_categories(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ));

    $categories = array_map(function ($item) {
        return array(
            'value' => $item->term_id,
            'name' => $item->name
        );
    }, $categories);

    return apply_filters('ws-metabox-general-fields', array(
        '_ws_category' => array(
            'label' => esc_html__('Category', 'woocommerce-scraper'),
            'name' => '_ws_category',
            'type' => 'select',
            'value' => get_post_meta($post->ID, '_ws_category', true),
            'options' => $categories
        ),
        '_ws_source_url' => array(
            'label' => esc_html__('Source URL', 'woocommerce-scraper'),
            'name' => '_ws_source_url',
            'type' => 'url',
            'value' => get_post_meta($post->ID, '_ws_source_url', true),
            'placeholder' => 'https://saris-extensions.co.uk/shop/skylights/triple-glazed/'
        ),
        '_wp_list_product_url_selectors' => array(
            'label' => esc_html__('List Product URL Selectors', 'woocommerce-scraper'),
            'name' => '_wp_list_product_url_selectors',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_wp_list_product_url_selectors', true),
            'placeholder' => '.product-item'
        ),
        '_ws_product_title_selector' => array(
            'label' => esc_html__('Product Title Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_title_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_title_selector', true),
            'placeholder' => '.product-item'
        ),
        '_ws_product_image_selector' => array(
            'label' => esc_html__('Product Image Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_image_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_image_selector', true),
            'placeholder' => '.product-image'
        ),
        '_ws_product_gallery_selectors' => array(
            'label' => esc_html__('Product Gallery Selectors', 'woocommerce-scraper'),
            'name' => '_ws_product_gallery_selectors',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_gallery_selectors', true),
            'placeholder' => '.thumbnails .owl-stage-outer img'
        ),
        '_ws_product_price_selector' => array(
            'label' => esc_html__('Price Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_price_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_price_selector', true),
            'placeholder' => '.price'
        ),
        '_ws_product_regular_price_selector' => array(
            'label' => esc_html__('Regular Price Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_regular_price_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_regular_price_selector', true),
            'placeholder' => '.regular-price'
        ),
        '_ws_product_sale_price_selector' => array(
            'label' => esc_html__('Sale Price Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_sale_price_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_sale_price_selector', true),
            'placeholder' => '.sale-price'
        ),
        '_ws_product_short_description_selector' => array(
            'label' => esc_html__('Product Short Description Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_short_description_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_short_description_selector', true),
            'placeholder' => '.woocommerce-product-details__short-description'
        ),
        '_ws_product_description_selector' => array(
            'label' => esc_html__('Product Description Selector', 'woocommerce-scraper'),
            'name' => '_ws_product_description_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_product_description_selector', true),
            'placeholder' => '.entry-content'
        ),
        '_ws_next_page_selector' => array(
            'label' => esc_html__('Next Page Selector', 'woocommerce-scraper'),
            'name' => '_ws_next_page_selector',
            'type' => 'text',
            'value' => get_post_meta($post->ID, '_ws_next_page_selector', true),
            'placeholder' => '.next-page'
        ),
    ));
}

function ws_metabox_get_attributes_fields($post)
{
    $attributes = get_post_meta($post->ID, '_ws_attributes', true);

    $fields = array();
    if (isset($attributes) && is_array($attributes) && count($attributes) > 0) {
        $fields = array_map(function ($index, $attribute) {
            return array(
                array(
                    'label' => esc_html__('Attribute Name', 'woocommerce-scraper'),
                    'name' => "_ws_attributes[$index][name]",
                    'type' => 'text',
                    'value' => $attribute['name'],
                    'placeholder' => 'Color'
                ),
                array(
                    'label' => esc_html__('Option Selector', 'woocommerce-scraper'),
                    'name' => "_ws_attributes[$index][option_selector]",
                    'type' => 'text',
                    'value' => $attribute['option_selector'],
                    'placeholder' => '.selector option'
                )
            );
        }, array_keys($attributes), array_values($attributes));
    }

    $count = count($fields);

    $fields[] = array(
        array(
            'label' => esc_html__('Attribute Name', 'woocommerce-scraper'),
            'name' => "_ws_attributes[$count][name]",
            'type' => 'text',
            'value' => '',
            'placeholder' => 'Color'
        ),
        array(
            'label' => esc_html__('Option Selector', 'woocommerce-scraper'),
            'name' => "_ws_attributes[$count][option_selector]",
            'type' => 'text',
            'value' => '',
            'placeholder' => '.selector option'
        )
    );

    return apply_filters('ws-metabox-attributes-fields', $fields);
}