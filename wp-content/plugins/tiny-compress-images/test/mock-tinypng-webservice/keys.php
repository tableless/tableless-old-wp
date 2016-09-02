<?php

ob_start();

header('HTTP/1.1 202 Pending');
header("Content-Type: application/json; charset=utf-8");

echo json_encode(
	array(
		'key' => 'PENDING123',
	)
);

ob_end_flush();
