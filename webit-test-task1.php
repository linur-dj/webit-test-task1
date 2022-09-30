<?php
/*
Plugin Name: Webit Test Task 1
Author: Pavel Starikov
Description: Webit Test Task 1
Version: 1.0
*/

// Add custom field to user profile (Show input)
add_action('show_user_profile', 'webit_task1_user_profile_fields');
add_action('edit_user_profile', 'webit_task1_user_profile_fields');
function webit_task1_user_profile_fields($user)
{
    if (!in_array('administrator', $user->roles)) {
        return;
    }
    ?>
    <table class="form-table">
        <tr>
            <th><label for="address"><?php _e("Token"); ?></label></th>
            <td>
                <input type="text" name="webit_task1_token" id="webit_task1_token"
                       value="<?php echo esc_attr(get_the_author_meta('webit_task1_token', $user->ID)); ?>"
                       class="regular-text"/><br/>
                <span class="description"><?php _e("Please enter token"); ?></span>
            </td>
        </tr>
        <tr>
    </table>
<?php }

// Add custom field to user profile (Update)
add_action('personal_options_update', 'webit_task1_user_profile_fields_update');
add_action('edit_user_profile_update', 'webit_task1_user_profile_fields_update');
function webit_task1_user_profile_fields_update($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    $user_meta = get_userdata($user_id);
    if (!in_array('administrator', $user_meta->roles)) {
        return;
    }
    update_user_meta($user_id, 'webit_task1_token', $_POST['webit_task1_token']);
}