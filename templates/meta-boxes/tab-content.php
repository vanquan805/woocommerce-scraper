<div class="tab-content">
    <div class="tab-item active" id="ws-scraper-metabox-category">
        <?php
        $fields = isset($post) ? ws_metabox_get_category_fields($post) : [];
        include WooCommerce_Scraper::instance()->plugin_directory . 'templates/meta-boxes/metabox.php';
        ?>
    </div>
    <div class="tab-item" id="ws-scraper-metabox-single-product">
        <?php
        $fields = isset($post) ? ws_metabox_get_single_product_fields($post) : [];
        include WooCommerce_Scraper::instance()->plugin_directory . 'templates/meta-boxes/metabox.php';
        ?>
    </div>
    <div class="tab-item" id="ws-scraper-metabox-attributes">
        <?php if (!ws_is_license_key_expired()) { ?>
            <div class="wrap-attribute-list">
                <?php
                $attribute_fields = isset($post) ? ws_metabox_get_attributes_fields($post) : [];
                if (isset($attribute_fields) && count($attribute_fields) > 0) {
                    foreach ($attribute_fields as $index => $fields) {
                        if ($index > 0) {
                            echo '<hr>';
                        }
                        include WooCommerce_Scraper::instance()->plugin_directory . 'templates/meta-boxes/metabox.php';
                    }
                }
                ?>
            </div>

            <a href="#" class="ws-add-attribute"><?php _e('Add Attribute', 'woocommerce-scraper'); ?></a>
        <?php } else {
            $class = 'notice notice-warning';
            $message = __('You are using Woocommerce Free version. For the best experience, you should ', 'woocommerce-scraper');
            $upgrade_message = __('Upgrade to Premium.', 'woocommerce-scraper');

            printf('<p>%2$s<a href="https://lienlau.com"><b>%3$s</b></a></p>', esc_attr($class), esc_html($message), esc_html($upgrade_message));
        } ?>
    </div>
</div>