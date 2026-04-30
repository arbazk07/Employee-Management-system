<?php
// get_project_team.php — AJAX JSON endpoint
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');
$proj_id = (int)($_GET['proj_id'] ?? 0);
if (!$proj_id) { echo '[]'; exit; }

$stmt = getDB()->prepare("
    SELECT e.Name, d.Name AS DeptName, w.Hours,
           CONCAT(UPPER(LEFT(SUBSTRING_INDEX(e.Name,' ',1),1)),
                  UPPER(LEFT(SUBSTRING_INDEX(e.Name,' ',-1),1))) AS initials
    FROM works_on w
    JOIN employee e   ON w.Emp_ID   = e.Emp_ID
    LEFT JOIN department d ON e.Dept_ID = d.Dept_ID
    WHERE w.Proj_ID = ?
    ORDER BY w.Hours DESC
");
$stmt->execute([$proj_id]);
echo json_encode($stmt->fetchAll());
