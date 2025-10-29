<?php
// api/add_pos.php
header('Content-Type: application/json');
require_once __DIR__.'/../config_db.php'; // if in api folder, or adjust path
// If not using subfolder, just: include 'config_db.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) die(json_encode(['success'=>false,'error'=>'No input']));
$user_id = intval($data['user_id'] ?? 0);
$business = $data['business'] ?? '';
$mode = $data['mode'] ?? 'withdraw';
$name = $data['name'] ?? '';
$bank_name = $data['bank_name'] ?? null;
$amount = floatval($data['amount'] ?? 0);
$note = $data['note'] ?? null;

if (!$user_id || !$business || !$name || !$amount) die(json_encode(['success'=>false,'error'=>'Missing fields']));

$stmt = $conn->prepare("INSERT INTO pos_records (user_id,business,mode,name,bank_name,amount,note) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param("isssdss", $user_id, $business, $mode, $name, $bank_name, $amount, $note);
if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
else echo json_encode(['success'=>false,'error'=>$conn->error]);
$stmt->close();
$conn->close();
