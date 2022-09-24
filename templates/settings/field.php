<?php switch ($args['type']):
    case 'textarea': ?>
        <textarea name="woocommerce-scraper_options[<?php echo esc_attr($args['label_for']); ?>]"
                  id="<?php echo $args['label_for'] ?>" rows="10"
                  cols="38"
                  placeholder="<?php echo $args['placeholder'] ?>"><?php echo isset($args['options'][$args['label_for']]) ? $args['options'][$args['label_for']] : '' ?></textarea>
        <?php break; ?>

    <?php case 'select': ?>
        <select name="woocommerce-scraper_options[<?php echo esc_attr($args['label_for']); ?>]"
                id="<?php echo $args['label_for'] ?>">
            <option value=""><?php _e('Select an option') ?></option>
            <?php if (isset($args['select_options']) && count($args['select_options']) > 0) { ?>
                <?php foreach ($args['select_options'] as $option) { ?>
                    <option value="<?php echo $option['value'] ?>" <?php selected($option['value'], isset($args['options'][$args['label_for']]) ? $args['options'][$args['label_for']] : ''); ?>><?php echo $option['name'] ?></option>
                <?php } ?>
            <?php } ?>
        </select>
        <?php break; ?>
    <?php case 'editor':
        wp_editor($args['options'][$args['label_for']], $args['label_for'], $settings = array(
            'textarea_name' => $args['label_for'],
            'media_buttons' => false,
            'drag_drop_upload' => false
        ));
        break; ?>

    <?php case 'checkbox': ?>
        <input name="woocommerce-scraper_options[<?php echo esc_attr($args['label_for']); ?>]"
               type="checkbox"
               id="<?php echo $args['label_for'] ?>"
               value="1" <?php checked(1, isset($args['options'][$args['label_for']]) ? $args['options'][$args['label_for']] : 0, true) ?>
               placeholder="<?php echo $args['placeholder'] ?>">
        <?php break; ?>

    <?php default: ?>
        <input name="woocommerce-scraper_options[<?php echo esc_attr($args['label_for']); ?>]"
               type="<?php echo($args['type'] ? $args['type'] : 'text'); ?>"
               id="<?php echo $args['label_for'] ?>"
               value="<?php echo isset($args['options'][$args['label_for']]) ? $args['options'][$args['label_for']] : '' ?>"
               class="regular-text"
               placeholder="<?php echo $args['placeholder'] ?>">
    <?php endswitch; ?>
<?php if (isset($args['description'])) { ?>
    <p class="description"><?php echo $args['description']; ?></p>
<?php } ?>