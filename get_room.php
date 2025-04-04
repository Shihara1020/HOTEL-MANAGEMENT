<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Room ID not provided']);
    exit;
}

$roomId = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    echo json_encode(['error' => 'Room not found']);
    exit;
}

echo json_encode($room);
?>