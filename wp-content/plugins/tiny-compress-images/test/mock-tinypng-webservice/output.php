<?php
ob_start();

require_once 'common.php';

if (preg_match('#output/.+[.](png|jpg)$#', $_SERVER['REQUEST_URI'], $match)) {
    $file = str_replace('/', '-', $match[0]);
    $ext = $match[1];
    $mime = $match[1] == 'jpg' ? 'image/jpeg' : "image/$ext";
} else {
    $file = null;
}
$headers = [];

$api_key = get_api_key();
if (!is_null($api_key)) {
    $data = get_json_body();
    $resize = $data->resize;
    if ($resize->method) {
        $file = "output-resized.$ext";
    }
}

if (strpos($api_key, 'GATEWAYTIMEOUT') !== false) {
    echo mock_service_unavailable_response();
} else if (strpos($api_key, 'INVALID') !== false) {
    header('HTTP/1.1 400 Bad Request');
    header("Content-Type: application/json; charset=utf-8");

    $response = array(
        "error" => "JSON validation error",
        "message" => "Metadata key 'author' not supported"
    );
    echo json_encode($response);
} else if ($file && file_exists($file)) {
    list($width, $height) = getimagesize($file);
    header("Content-Type: $mime");
    header('Content-Disposition: attachment');
    header('Image-Width: ' . $width);
    header('Image-Height: ' . $height);
    header('Content-Length: ' . filesize($file));
    foreach ($headers as $name => $value) {
        header("$name: $value");
    }
    readfile($file);
} else {
    header("HTTP/1.1 404 Not Found");
}

ob_end_flush();
