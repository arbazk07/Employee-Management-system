<?php
// delete_employee.php — AJAX DELETE endpoint
session_start();
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }

if (isset($_POST['confirm'])) {
    try {
        $pdo  = getDB();
        // Check employee exists
        $stmt = $pdo->prepare("SELECT Name FROM employee WHERE Emp_ID=?");
        $stmt->execute([$id]);
        $emp  = $stmt->fetch();
        if (!$emp) { echo json_encode(['success'=>false,'message'=>'Employee not found.']); exit; }

        // Delete (cascades to login, dependent, works_on via FK constraints)
        $pdo->prepare("DELETE FROM employee WHERE Emp_ID=?")->execute([$id]);
        echo json_encode(['success'=>true,'message'=>'Deleted.']);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'Delete failed: '.$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'No confirmation.']);
}
