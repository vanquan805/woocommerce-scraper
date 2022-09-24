<table class="form-table" role="presentation" data-index="<?php echo $index ?? 0; ?>">
    <tbody>
    <?php if (isset($fields) && count($fields) > 0) { ?>
        <?php foreach ($fields as $field) { ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo $field['name'] ?>"><?php echo $field['label'] ?></label>
                </th>
                <td>
                    <?php switch ($field['type']):
                        case 'textarea': ?>
                            <textarea name="<?php echo $field['name'] ?>" id="<?php echo $field['name'] ?>" rows="10"
                                      cols="38"
                                      placeholder="<?php echo $field['placeholder'] ?>"><?php echo $field['value'] ?></textarea>
                            <?php break; ?>

                        <?php case 'select': ?>
                            <select name="<?php echo $field['name'] ?>" id="<?php echo $field['name'] ?>"
                                <?php echo isset($field['multiple']) && $field['multiple'] === true ? 'multiple' : ''; ?>>
                                <option value=""><?php _e('Select an option') ?></option>
                                <?php if (isset($field['options']) && count($field['options']) > 0) { ?>
                                    <?php foreach ($field['options'] as $option) { ?>
                                        <option value="<?php echo $option['value'] ?>" <?php echo $option['value'] == $field['value'] ? 'selected' : '' ?>><?php echo $option['name'] ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                            <?php break; ?>
                        <?php case 'editor':
                            wp_editor($field['value'], $field['name'], $settings = array(
                                'textarea_name' => $field['name'],
                                'media_buttons' => false,
                                'drag_drop_upload' => false
                            ));
                            break; ?>

                        <?php case 'checkbox': ?>
                            <input name="<?php echo $field['name'] ?>"
                                   type="checkbox"
                                   id="<?php echo $field['name'] ?>"
                                   value="1" <?php checked(1, isset($field['value']) ? $field['value'] : 0, true) ?>
                                   placeholder="<?php echo $field['placeholder'] ?>">
                            <?php break; ?>

                        <?php default: ?>
                            <input name="<?php echo $field['name'] ?>"
                                   type="<?php echo($field['type'] ? $field['type'] : 'text'); ?>"
                                   id="<?php echo $field['name'] ?>"
                                   value='<?php echo $field['value'] ?>' class="regular-text"
                                   placeholder="<?php echo $field['placeholder'] ?>">
                        <?php endswitch; ?>
                    <?php if (isset($field['description'])) { ?>
                        <p class="description"><?php echo $field['description']; ?></p>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    <?php } ?>
    </tbody>
</table>