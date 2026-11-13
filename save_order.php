<?php
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка чтения данных.']);
    exit;
}

$dir = __DIR__ . '/admin/data';
$file = $dir . '/orders.json';

if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$orders = [];
if (file_exists($file)) {
    $content = file_get_contents($file);
    $orders = json_decode($content, true);
    if (!is_array($orders)) $orders = [];
}

$newOrder = [
    'id' => uniqid(),
    'date' => date('d.m.Y H:i:s'),
    'name' => htmlspecialchars($data['name'] ?? ''),
    'phone' => htmlspecialchars($data['phone'] ?? ''),
    'address' => htmlspecialchars($data['address'] ?? ''),
    'status' => 'new'
];

array_unshift($orders, $newOrder);
$result = file_put_contents($file, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

if ($result === false) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка записи файла.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Заказ сохранен']);
}
?>