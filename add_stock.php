<?php
header('Content-Type: application/json');
include 'config_db.php';
$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['user_id'] ?? 0);
$business = $data['business'] ?? '';
$item_name = $data['item_name'] ?? '';
if (!$user_id || !$business || !$item_name) die(json_encode(['success'=>false,'error'=>'Missing fields']));
$stmt = $conn->prepare("INSERT INTO stock_items (user_id,business,item_name) VALUES (?,?,?)");
$stmt->bind_param("iss",$user_id,$business,$item_name);
if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
else echo json_encode(['success'=>false,'error'=>$conn->error]);
$stmt->close(); $conn->close();
