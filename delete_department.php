<?php
// delete_department.php — AJAX DELETE endpoint
session_start();
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }

if (isset($_POST['confirm'])) {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT Name FROM department WHERE Dept_ID=?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { echo json_encode(['success'=>false,'message'=>'Not found.']); exit; }
        $pdo->prepare("DELETE FROM department WHERE Dept_ID=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'Cannot delete: employees still assigned to this department.']);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'No confirmation.']);
}
