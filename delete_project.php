<?php
// delete_project.php — AJAX DELETE endpoint
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }

if (isset($_POST['confirm'])) {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT Name FROM project WHERE Proj_ID=?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { echo json_encode(['success'=>false,'message'=>'Project not found.']); exit; }
        $pdo->prepare("DELETE FROM project WHERE Proj_ID=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'Delete failed.']);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'No confirmation.']);
}
