<?php
// get_dependents.php — AJAX JSON endpoint
session_start();
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');
$emp_id = (int)($_GET['emp_id'] ?? 0);
if (!$emp_id) { echo '[]'; exit; }

$stmt = getDB()->prepare("SELECT Name, Relationship FROM dependent WHERE Emp_ID=? ORDER BY Name");
$stmt->execute([$emp_id]);
echo json_encode($stmt->fetchAll());
