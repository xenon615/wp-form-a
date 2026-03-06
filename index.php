<?php
// Plugin Name: Form-A

namespace FormA;
class Broker {
    static function bootstrap($forms) {
        $plugin_url = plugin_dir_url(__FILE__);
        $plugin_path = plugin_dir_path(__FILE__);
        wp_enqueue_script('form-a', $plugin_url . 'assets/index.js', \filemtime($plugin_path . 'assets/index.js'));
        wp_enqueue_style('form-a', $plugin_url . 'assets/index.css', \filemtime($plugin_path . 'assets/index.css'));
        // wp_enqueue_media();
        // wp_enqueue_editor();
        wp_localize_script('form-a', 'formA', [
            'remote' => [
                'nonce' => wp_create_nonce('wp_rest'),
                'url' => site_url('wp-json/form-a/all'),
            ],
            'forms' => $forms
        ]);
    }
}

// ---

function rest_handler($request) {
    $params = $request->get_params();
    if (empty($params['formSlug'])) {
        return new \WP_REST_Response(null, 403);
    }
    if (($request->get_method() == 'GET') && ($params['action'] == 'load') ) {
        $response = load($params['formSlug']);
    } else if (($request->get_method() == 'POST') && ($params['action'] == 'submit')) {

        if (!empty($request->get_body())) {  // pure json 
            $data = json_decode($request->get_body(), true);
        } else {
            $data = json_decode($params['data'], true);
        }
        
        try {
            $result = ['success' => true,'message' => '', 'payload' => null]; 
            $result = apply_filters('form-a_submit', $result, $data, $params['formSlug']);

            // $response = ['success' => true, 'message' => $result['message'], 'payload' => $result['payload']];
            $response = array_merge(['success' => true], $result);
        } catch(\Throwable $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        } 

    } else {
        return new \WP_REST_Response(null, 403);
    }
    wp_send_json($response, 200);
}

// ---

function fields_walk($fields, $data ) {
    $rdata = [];
    foreach($fields as $field) {
        if (isset($field['fields']) && is_array($field['fields']) && !empty($field['fields'])) {
            // if ($field['type'] != 'repeater') {
            if (! (($field['type'] == 'repeater') || ($field['type'] == 'table'))) {                
                if (!empty($data[$field['name']])) {
                    $rdata[$field['name']] =  fields_walk($field['fields'], $data[$field['name']]);
                }
            } else {
                if (isset($data[$field['name']]) && is_array($data[$field['name']])) {
                    foreach($data[$field['name']] as $i => $d) {
                        $rdata[$field['name']][] = fields_walk($field['fields'], $data[$field['name']][$i]);
                    }
                }
            }
        } else if (isset($data[$field['name']]) && ($data[$field['name']] !== null) ) {            
            $rdata[$field['name']] = $data[$field['name']]; 
        }
    }
    return $rdata;
}

// ---

function load($formSlug) {
    $form = apply_filters('form-a_form_load', [], $formSlug);
    if (empty($form['data'])) {
        $form['data'] = (object)[];
    } else {
        if (is_object($form['data'])) {
            $form['data'] = json_decode(\json_encode($form['data']), true);
        }
        $form['data'] = fields_walk($form['def']['fields'], $form['data']);

        if (empty($form['data'])) {
            $form['data'] = (object)[];
        }
    }
    return $form;
}

// ---

add_action('rest_api_init', function() {
    register_rest_route ('form-a', '/all', ['methods' => ['GET','POST'], 'callback' => __NAMESPACE__ . '\rest_handler']);
    // header("Access-Control-Allow-Origin: *");    // uncomment for test from  "yarn dev"
    // remove_filter( 'rest_authentication_errors', 'rest_cookie_check_errors', 100 );   // uncomment for test from  "yarn dev"
});

// ---

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if ($handle == 'form-a') {
        $tag = \str_replace('<script', '<script type="module"', $tag);
    }
    return $tag;
} , 10, 3);

// ---

add_filter('rest_authentication_errors', function($errors) {
    if (is_wp_error($errors)) {
        return $errors;
    }
    if (strpos($_SERVER['REQUEST_URI'], 'form-a') !== false ) {
        if ( ! is_user_logged_in() ) {
            return new \WP_Error('rest_not_logged_in','You are not currently logged in.',['status' => 401]);
        }
    }
    return $errors;
}, 999);

// ---

add_action('admin_enqueue_scripts', function() {
    $forms = apply_filters('form-a_need-a-form', []);
    if (!empty($forms)) {
        Broker::bootstrap($forms);
    }
});