<?php
header('Content-Type: application/json');
include 'config_db.php';
$type = $_GET['type'] ?? 'pos';
$date = $_GET['date'] ?? '';
// Only return current user's data? For simplicity, rely on session
session_start();
if (!isset($_SESSION['email'])) { echo json_encode(['data'=>[]]); exit; }
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id,business FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc(); $user_id = $u['id'];
$stmt->close();

$rows = [];
if ($type === 'pos') {
  $sql = "SELECT id,mode,name,bank_name,amount,note,created_at FROM pos_records WHERE user_id=? ";
  if ($date) $sql .= "AND DATE(created_at)=? ";
  $sql .= "ORDER BY created_at DESC";
  if ($date) {
    $s = $conn->prepare($sql);
    $s->bind_param("is",$user_id,$date);
  } else {
    $s = $conn->prepare($sql);
    $s->bind_param("i",$user_id);
  }
} elseif ($type === 'loan') {
  $sql = "SELECT id,name,items,quantity,total_amount,created_at FROM loan_records WHERE user_id=? ";
  if ($date) $sql .= "AND DATE(created_at)=? ";
  $sql .= "ORDER BY created_at DESC";
  if ($date) {
    $s = $conn->prepare($sql);
    $s->bind_param("is",$user_id,$date);
  } else {
    $s = $conn->prepare($sql);
    $s->bind_param("i",$user_id);
  }
} elseif ($type === 'stock') {
  $sql = "SELECT id,item_name,created_at FROM stock_items WHERE user_id=? ";
  if ($date) $sql .= "AND DATE(created_at)=? ";
  $sql .= "ORDER BY created_at DESC";
  if ($date) {
    $s = $conn->prepare($sql);
    $s->bind_param("is",$user_id,$date);
  } else {
    $s = $conn->prepare($sql);
    $s->bind_param("i",$user_id);
  }
} elseif ($type === 'notes') {
  $sql = "SELECT id,text,created_at FROM notes WHERE user_id=? ORDER BY created_at DESC LIMIT 50";
  $s = $conn->prepare($sql);
  $s->bind_param("i",$user_id);
} else {
  echo json_encode(['data'=>[]]); exit;
}

$s->execute();
$result = $s->get_result();
while ($r = $result->fetch_assoc()) $rows[] = $r;
$s->close();
echo json_encode(['data'=>$rows]);
$conn->close();
