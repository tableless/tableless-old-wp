<?php
require_once("cleantalk.class.php");
require_once("JSON.php");


/**
 * Get ct_get_checkjs_value 
 * @return string
 */
function ct_get_checkjs_value_plugin($random_key = false) {
    
    $ct_data=get_option("cleantalk_data");

    if ($random_key) {
        $keys = $ct_data['js_keys'];
        $keys_checksum = md5(json_encode($keys));
        
        $key = null;
        $latest_key_time = 0;
        foreach ($keys as $k => $t) {

            // Removing key if it's to old
            if (time() - $t > $ct_data['js_keys_store_days'] * 86400) {
                unset($keys[$k]);
                continue;
            }

            if ($t > $latest_key_time) {
                $latest_key_time = $t;
                $key = $k;
            }
        }
        
        // Get new key if the latest key is too old
        if (time() - $latest_key_time > $ct_data['js_key_lifetime']) {
            $key = rand();
            $keys[$key] = time();
        }
        
        if (md5(json_encode($keys)) != $keys_checksum) {
            $ct_data['js_keys'] = $keys;
            update_option('cleantalk_data', $ct_data);
        }
    } else {
        $key = md5($ct_options['apikey'] . '+' . get_option('admin_email'));
    }

    return $key; 
}

/**
 * Validates JavaScript anti-spam test
 *
 */
function js_test_plugin($field_name = 'ct_checkjs', $data = null, $random_key = false) {
    
    $ct_data=get_option("cleantalk_data");

    $checkjs = null;
    $js_post_value = null;
    
    if (!$data)
        return $checkjs;

    if (isset($data[$field_name])) {
	    $js_post_value = $data[$field_name];
            if (isset($keys[$js_post_value])) {

        //
        // Random key check
        //
        if ($random_key) {
            
            $keys = $ct_data['js_keys'];
                $checkjs = 1;
            } else {
                $checkjs = 0; 
            }
        } else {
            $ct_challenge = ct_get_checkjs_value();
            
            if(preg_match("/$ct_challenge/", $js_post_value)) {
                $checkjs = 1;
            } else {
                $checkjs = 0; 
            }
        }
        

    }

    return $checkjs;
}

/**
 * Check messages for external plugins
 * @return array with checking result;
 */

function ct_test_message($nickname, $email, $ip, $text){
	$checkjs = js_test_plugin('ct_checkjs', $_COOKIE, true);
  
    $post_info['comment_type'] = 'feedback_plugin_check';
    $post_info = json_encode($post_info);
    
    $ct_base_call_result = ct_base_call(array(
        'message' => $text,
        'example' => null,
        'sender_email' => $email,
        'sender_nickname' => $nickname,
        'post_info' => $post_info,
	    'sender_info' => get_sender_info(),
        'checkjs' => $checkjs
    ));
    
    $ct_result = $ct_base_call_result['ct_result'];
    
    $result=Array(
        'allow' => $ct_result->allow,
        'comment' => $ct_result->comment,
    );
    return $result;
}



?>