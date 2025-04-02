<?php

$config = require_once('../config.php');
$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);
require_once('../api.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
    $jsonFile = $_FILES['jsonFile']['tmp_name'];
    $jsonData = file_get_contents($jsonFile);
    $laureates = json_decode($jsonData, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $laureate = new Laureate($db);
        $result = $laureate->insertMultiple($laureates);
        if ($result === 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $result]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>