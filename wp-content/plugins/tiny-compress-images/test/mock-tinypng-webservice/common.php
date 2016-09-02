<?php

define('SESSION_FILE', '/tmp/session.dat');

if (file_exists(SESSION_FILE)) {
    $session = unserialize(file_get_contents(SESSION_FILE));
} else {
    $session = array( 'Compression-Count' => 0 );
}

function save_session() {
    global $session;
    if ($session) {
        file_put_contents(SESSION_FILE, serialize($session));
    } elseif (file_exists(SESSION_FILE)) {
        unlink(SESSION_FILE);
    }
}
register_shutdown_function('save_session');

function get_api_key() {
    $request_headers = apache_request_headers();
    if (!isset($request_headers['Authorization'])) {
        return null;
    }
    $basic_auth = base64_decode(str_replace('Basic ', '', $request_headers['Authorization']));
    return next(explode(':', $basic_auth));
}

function get_json_body() {
    return json_decode(file_get_contents("php://input"));
}

function mock_invalid_response() {
    global $session;

    header('HTTP/1.1 401 Unauthorized');
    header("Content-Type: application/json; charset=utf-8");

    $response = array(
        "error" => "Unauthorized",
        "message" => "Credentials are invalid "
    );
    return json_encode($response);
}

function mock_service_unavailable_response() {
    header('HTTP/1.1 503 Service unavailable');
    return 'HTTP Error 503. The service is unavailable';
}
