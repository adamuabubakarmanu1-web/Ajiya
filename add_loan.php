<?php
header('Content-Type: application/json');
include 'config_db.php';
$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['user_id'] ?? 0);
$business = $data['business'] ?? '';
$name = $data['name'] ?? '';
$items = $data['items'] ?? '';
$quantity = intval($data['quantity'] ?? 1);
$total_amount = floatval($data['total_amount'] ?? 0);

if (!$user_id || !$business || !$name || !$items || !$total_amount) die(json_encode(['success'=>false,'error'=>'Missing fields']));
$stmt = $conn->prepare("INSERT INTO loan_records (user_id,business,name,items,quantity,total_amount) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("issiid",$user_id,$business,$name,$items,$quantity,$total_amount);
if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
else echo json_encode(['success'=>false,'error'=>$conn->error]);
$stmt->close(); $conn->close();
