<?php
// departments.php
require_once 'config.php';
requireLogin();

$pdo = getDB();

// ── POST: Add / Edit ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role']==='admin') {
    $action  = $_POST['action'] ?? '';
    $name    = trim($_POST['name'] ?? '');
    $mgr_id  = $_POST['manager_id'] ?: null;

    if ($name) {
        if ($action === 'add') {
            $pdo->prepare("INSERT INTO department (Name,Manager_ID,Employee_Number) VALUES (?,?,0)")->execute([$name,$mgr_id]);
            setFlash('success', "Department <strong>" . htmlspecialchars($name) . "</strong> created.");
        } elseif ($action === 'edit') {
            $dept_id = (int)($_POST['dept_id'] ?? 0);
            $pdo->prepare("UPDATE department SET Name=?,Manager_ID=? WHERE Dept_ID=?")->execute([$name,$mgr_id,$dept_id]);
            setFlash('success', "Department <strong>" . htmlspecialchars($name) . "</strong> updated.");
        }
    }
    redirect('departments.php');
}

// ── Fetch departments with stats ────────────────────────
$departments = $pdo->query("
    SELECT d.Dept_ID, d.Name, d.Employee_Number,
           e.Name AS ManagerName, e.Emp_ID AS ManagerID,
           COUNT(emp.Emp_ID) AS ActualCount
    FROM department d
    LEFT JOIN employee e ON d.Manager_ID = e.Emp_ID
    LEFT JOIN employee emp ON emp.Dept_ID = d.Dept_ID
    GROUP BY d.Dept_ID
    ORDER BY d.Name
")->fetchAll();

$all_emps = $pdo->query("SELECT Emp_ID,Name FROM employee ORDER BY Name")->fetchAll();

$flash = getFlash();
$active_page = 'departments';
$page_title  = 'Departments';
$page_sub    = 'Manage divisions and their managers.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMS — Departments</title>
<?php include 'includes/style.php'; ?>
<style>
.dept-cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:2rem;}
.dept-overview-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1.25rem;transition:border-color .15s;}
.dept-overview-card:hover{border-color:var(--border-hover);}
.dept-card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;}
.dept-icon{width:36px;height:36px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;}
.dept-card-actions{display:flex;gap:6px;}
.dept-name{font-size:16px;font-weight:600;color:var(--text-base);margin-bottom:4px;}
.dept-manager{font-size:13px;color:var(--text-soft);margin-bottom:12px;}
.dept-stat{display:flex;align-items:baseline;gap:4px;}
.dept-stat-num{font-size:24px;font-weight:600;color:var(--text-base);}
.dept-stat-label{font-size:12px;color:var(--text-muted);}
.ems-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s;}
.ems-modal-overlay.open{opacity:1;pointer-events:all;}
.ems-modal{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);width:100%;max-width:460px;transform:translateY(20px);transition:transform .2s;}
.ems-modal-overlay.open .ems-modal{transform:translateY(0);}
.modal-header{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-header h5{font-size:16px;font-weight:600;color:var(--text-base);margin:0;}
.modal-close{background:none;border:none;color:var(--text-soft);cursor:pointer;font-size:20px;line-height:1;padding:2px 6px;border-radius:4px;transition:all .15s;}
.modal-close:hover{background:var(--bg-page);color:var(--text-base);}
.modal-body{padding:1.5rem;}
.modal-footer{padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;}
.form-group{margin-bottom:1rem;}
.add-btn{background:var(--accent-blue);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 16px;font-size:13px;font-weight:500;cursor:pointer;transition:background .15s;display:flex;align-items:center;gap:6px;}
.add-btn:hover{background:#2563EB;}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'includes/topbar.php'; ?>
<div class="page-body">

  <div class="page-header fade-up d1" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div><h2>Departments</h2><p>Manage divisions and their managers.</p></div>
    <?php if ($_SESSION['role']==='admin'): ?>
    <button class="add-btn" onclick="openDeptModal('add')">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      New Department
    </button>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
  <div class="<?= $flash['type']==='success'?'flash-success':'flash-error' ?> fade-up"><?= $flash['msg'] ?></div>
  <?php endif; ?>

  <!-- Dept Cards -->
  <?php
  $icon_colors = [
    ['bg'=>'rgba(59,130,246,.12)','color'=>'#3B82F6'],
    ['bg'=>'rgba(16,185,129,.12)','color'=>'#10B981'],
    ['bg'=>'rgba(245,158,11,.12)','color'=>'#F59E0B'],
    ['bg'=>'rgba(139,92,246,.12)','color'=>'#8B5CF6'],
  ];
  ?>
  <div class="dept-cards-grid fade-up d2">
    <?php foreach ($departments as $i => $d):
      $col = $icon_colors[$i % 4];
      $initial = strtoupper($d['Name'][0]);
    ?>
    <div class="dept-overview-card">
      <div class="dept-card-top">
        <div class="dept-icon" style="background:<?= $col['bg'] ?>;color:<?= $col['color'] ?>;"><?= $initial ?></div>
        <?php if ($_SESSION['role']==='admin'): ?>
        <div class="dept-card-actions">
          <button class="action-btn btn-primary-action" onclick="openDeptModal('edit',<?= htmlspecialchars(json_encode($d)) ?>)">Edit</button>
          <button class="action-btn btn-danger-action" onclick="deleteDept(<?= $d['Dept_ID'] ?>,'<?= addslashes($d['Name']) ?>')">Delete</button>
        </div>
        <?php endif; ?>
      </div>
      <div class="dept-name"><?= htmlspecialchars($d['Name']) ?></div>
      <div class="dept-manager">Manager: <?= htmlspecialchars($d['ManagerName'] ?? 'Unassigned') ?></div>
      <div class="dept-stat">
        <span class="dept-stat-num"><?= $d['ActualCount'] ?></span>
        <span class="dept-stat-label">employees</span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Department + Employee table -->
  <div class="table-card fade-up d3">
    <div class="table-card-header">
      <div><h4>Department Overview</h4><p>Full listing with manager and headcount</p></div>
    </div>
    <div id="dt-controls2" class="dt-toolbar"></div>
    <div style="overflow-x:auto;">
      <table id="deptTable" class="table" style="width:100%">
        <thead><tr><th>#</th><th>Name</th><th>Manager</th><th>Employees</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($departments as $d): ?>
          <tr>
            <td style="color:var(--text-soft);"><?= $d['Dept_ID'] ?></td>
            <td style="font-weight:500;"><?= htmlspecialchars($d['Name']) ?></td>
            <td style="color:var(--text-muted);"><?= htmlspecialchars($d['ManagerName'] ?? '—') ?></td>
            <td><span class="badge-up"><?= $d['ActualCount'] ?></span></td>
            <td>
              <?php if ($_SESSION['role']==='admin'): ?>
              <button class="action-btn btn-primary-action" onclick="openDeptModal('edit',<?= htmlspecialchars(json_encode($d)) ?>)">Edit</button>
              <button class="action-btn btn-danger-action" onclick="deleteDept(<?= $d['Dept_ID'] ?>,'<?= addslashes($d['Name']) ?>')">Delete</button>
              <?php else: ?><span style="color:var(--text-soft);font-size:12px;">View only</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div id="dt-bottom2" class="dt-bottom"></div>
  </div>

</div></div>

<!-- Modal -->
<div class="ems-modal-overlay" id="deptModal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="ems-modal">
    <div class="modal-header">
      <h5 id="dept-modal-title">New Department</h5>
      <button class="modal-close" onclick="document.getElementById('deptModal').classList.remove('open')">×</button>
    </div>
    <form method="POST" action="departments.php">
      <div class="modal-body">
        <input type="hidden" name="action" id="dept-action" value="add">
        <input type="hidden" name="dept_id" id="dept-id">
        <div class="form-group">
          <label class="form-label">Department Name *</label>
          <input type="text" name="name" id="dept-name" class="form-control" placeholder="e.g. Engineering" required>
        </div>
        <div class="form-group">
          <label class="form-label">Manager</label>
          <select name="manager_id" id="dept-mgr" class="form-select">
            <option value="">— Select Manager —</option>
            <?php foreach ($all_emps as $e): ?>
            <option value="<?= $e['Emp_ID'] ?>"><?= htmlspecialchars($e['Name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost-ems" onclick="document.getElementById('deptModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn-primary-ems" id="dept-submit">Create</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/scripts.php'; ?>
<script>
$(function(){
  $('#deptTable').DataTable({
    dom:'<"dt-top-inner"lf>rt<"dt-bottom-inner"ip>',
    pageLength:10,
    columnDefs:[{orderable:false,targets:4}],
    language:{search:'',searchPlaceholder:'Search departments...',lengthMenu:'Show _MENU_',paginate:{previous:'Prev',next:'Next'}},
    initComplete:function(){
      $('.dt-top-inner').appendTo('#dt-controls2').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
      $('.dataTables_filter input').css({width:'200px'});
      $('.dt-bottom-inner').appendTo('#dt-bottom2').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
    }
  });
});

function openDeptModal(mode, data) {
  document.getElementById('deptModal').classList.add('open');
  if (mode==='add') {
    document.getElementById('dept-modal-title').textContent = 'New Department';
    document.getElementById('dept-action').value = 'add';
    document.getElementById('dept-submit').textContent = 'Create';
    document.getElementById('dept-name').value = '';
    document.getElementById('dept-mgr').value  = '';
  } else {
    document.getElementById('dept-modal-title').textContent = 'Edit Department';
    document.getElementById('dept-action').value = 'edit';
    document.getElementById('dept-submit').textContent = 'Save Changes';
    document.getElementById('dept-id').value   = data.Dept_ID;
    document.getElementById('dept-name').value = data.Name;
    document.getElementById('dept-mgr').value  = data.ManagerID || '';
  }
}

function deleteDept(id, name) {
  confirmDelete(`delete_department.php?id=${id}`, name, 'Department');
}
</script>
</body>
</html>
