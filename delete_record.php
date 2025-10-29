<?php
header('Content-Type: application/json');
include 'config_db.php';
$data = json_decode(file_get_contents('php://input'), true);
$table = $data['table'] ?? '';
$id = intval($data['id'] ?? 0);
$allowed = ['pos_records','loan_records','stock_items','notes'];
if (!in_array($table,$allowed) || !$id) die(json_encode(['success'=>false,'error'=>'Invalid request']));
// Optionally ensure record belongs to current user
// Delete
$stmt = $conn->prepare("DELETE FROM $table WHERE id=?");
$stmt->bind_param("i",$id);
if ($stmt->execute()) echo json_encode(['success'=>true]);
else echo json_encode(['success'=>false,'error'=>$conn->error]);
$stmt->close(); $conn->close();
