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

// Rest api
// Token: test_token
// status: completed / processing
add_action('rest_api_init', 'webit_task1_rest_api');
function webit_task1_rest_api()
{
    register_rest_route('test-api/v1', '/orders/', [
        'methods' => 'POST',
        'permission_callback' => function (WP_REST_Request $Request) {
            // Check Content-Type: application/json
            $content_type = $Request->get_headers()["content_type"][0];
            if ($content_type != 'application/json') {
                echo json_encode(array('error' => 'Bad Content Type'));
                exit();
            }
            $token = $Request->get_headers()["token"][0];
            if (!$token) {
                echo json_encode(array('error' => 'Token is required'));
                exit();
            }
            return true;
        },
        'callback' => function (WP_REST_Request $Request) {
            // Check Token & Get User Id
            $token = $Request->get_headers()['Token'][0];
            $users = get_users(array(
                'meta_key' => 'webit_task1_token',
                'meta_value' => $token
            ));
            if (!$users) {
                return new WP_Error('invalid_token', 'Invalid Token', ['status' => 401]);
            }
            $user_id = $users[0]->data->ID;

            // Get Status & Check Valid Status
            $status = $Request->get_param('status');
            if($status) {
                $status = 'wc-' . $status;
                $all_order_statuses = wc_get_order_statuses();
                $status_valid = 0;
                foreach ($all_order_statuses as $a => $b) {
                    if ($status == $a) {
                        $status_valid = 1;
                    }
                }
                if ($status_valid == 0) {
                    return new WP_Error('invalid_status', 'Invalid Status', ['status' => 400]);
                }
            }

            // Get Orders
            $return = [];
            $args = array('limit' => -1, 'customer' => $user_id);
            if ($status) {
                $args['status'] = $status;
            }
            $customer_orders = wc_get_orders($args);
            foreach ($customer_orders as $order) {
                // Add order info to final array
                $id = $order->ID;
                $sum = $order->total;
                $name = $order->data['billing']["first_name"] . ' ' . $order->data['billing']["last_name"];
                $return[] = array('id' => $id, 'sum' => $sum, 'name' => $name);
            }

            // Return
            return $return;
        },
        'args' => [
            'status' => [
                'description' => 'status',
                'type' => 'string',
            ],
        ]
    ]);
}