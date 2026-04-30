<?php
// employees.php — Full CRUD with inline modal editing
session_start();
require_once 'config.php';
requireLogin();

$pdo   = getDB();
$flash = null;

// ── Fetch departments + employees for dropdowns ─────────
$depts = $pdo->query("SELECT Dept_ID, Name FROM department ORDER BY Name")->fetchAll();
$all_emps = $pdo->query("SELECT Emp_ID, Name FROM employee ORDER BY Name")->fetchAll();

// ── Handle POST: Add / Edit employee ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $address = trim($_POST['address'] ?? 'Unknown');
        $dept_id = $_POST['dept_id']     ?: null;
        $sup_id  = $_POST['supervisor_id'] ?: null;

        if (!$name || !$email) {
            setFlash('error', 'Name and email are required.');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO employee (Name,Email,Address,Dept_ID,Supervisor_ID) VALUES (?,?,?,?,?)");
                    $stmt->execute([$name,$email,$address,$dept_id,$sup_id]);
                    setFlash('success', "Employee <strong>$name</strong> added successfully.");
                } else {
                    $emp_id = (int)($_POST['emp_id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE employee SET Name=?,Email=?,Address=?,Dept_ID=?,Supervisor_ID=? WHERE Emp_ID=?");
                    $stmt->execute([$name,$email,$address,$dept_id,$sup_id,$emp_id]);
                    setFlash('success', "Employee <strong>$name</strong> updated successfully.");
                }
            } catch (PDOException $e) {
                setFlash('error', 'Error: Email may already be in use.');
            }
        }
        redirect('employees.php');
    }
}

// ── Fetch employee to edit if ?edit=ID ─────────────────
$edit_emp = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM employee WHERE Emp_ID=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_emp = $stmt->fetch();
}

// ── Fetch all employees with joins ──────────────────────
$employees = $pdo->query("
    SELECT e.Emp_ID, e.Name, e.Email, e.Address,
           d.Name AS DeptName, d.Dept_ID,
           s.Name AS SupervisorName, s.Emp_ID AS Supervisor_ID
    FROM employee e
    LEFT JOIN department d ON e.Dept_ID = d.Dept_ID
    LEFT JOIN employee s ON e.Supervisor_ID = s.Emp_ID
    ORDER BY e.Name ASC
")->fetchAll();

$flash   = getFlash();
$active_page = 'employees';
$page_title  = 'Employees';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMS — Employees</title>
<?php include 'includes/style.php'; ?>
<style>
/* Modal */
.ems-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s;}
.ems-modal-overlay.open{opacity:1;pointer-events:all;}
.ems-modal{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;transform:translateY(20px);transition:transform .2s;}
.ems-modal-overlay.open .ems-modal{transform:translateY(0);}
.modal-header{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-header h5{font-size:16px;font-weight:600;color:var(--text-base);margin:0;}
.modal-close{background:none;border:none;color:var(--text-soft);cursor:pointer;font-size:20px;line-height:1;padding:2px 6px;border-radius:4px;transition:all .15s;}
.modal-close:hover{background:var(--bg-page);color:var(--text-base);}
.modal-body{padding:1.5rem;}
.modal-footer{padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:flex-end;gap:10px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;}
.form-group{margin-bottom:1rem;}
.form-group:last-child{margin-bottom:0;}
/* Add button top right */
.add-btn{background:var(--accent-blue);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 16px;font-size:13px;font-weight:500;cursor:pointer;transition:background .15s;display:flex;align-items:center;gap:6px;}
.add-btn:hover{background:#2563EB;}
/* Dependents badge */
.dep-badge{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--accent-blue);padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;}
.dep-badge:hover{background:rgba(59,130,246,.15);}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'includes/topbar.php'; ?>
<div class="page-body">

  <div class="page-header fade-up d1" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div>
      <h2>Employees</h2>
      <p>Manage staff records, roles, and assignments.</p>
    </div>
    <?php if ($_SESSION['role']==='admin'): ?>
    <button class="add-btn" onclick="openModal('add')">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      Add Employee
    </button>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
  <div class="<?= $flash['type']==='success'?'flash-success':'flash-error' ?> fade-up">
    <?= $flash['msg'] ?>
  </div>
  <?php endif; ?>

  <!-- Employee Table -->
  <div class="table-card fade-up d2">
    <div class="table-card-header">
      <div><h4>Employee Directory</h4><p><?= count($employees) ?> total employees</p></div>
    </div>
    <div id="dt-controls" class="dt-toolbar"></div>
    <div style="overflow-x:auto;">
      <table id="empTable" class="table" style="width:100%">
        <thead>
          <tr>
            <th>#</th><th>Employee</th><th>Email</th><th>Department</th>
            <th>Location</th><th>Supervisor</th><th>Dependents</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $i => $emp): ?>
          <tr>
            <td style="color:var(--text-soft);"><?= $emp['Emp_ID'] ?></td>
            <td>
              <div class="emp-cell">
                <div class="emp-av <?= avatarClass($emp['Emp_ID']) ?>"><?= initials($emp['Name']) ?></div>
                <?= htmlspecialchars($emp['Name']) ?>
              </div>
            </td>
            <td style="color:var(--text-muted);"><?= htmlspecialchars($emp['Email']) ?></td>
            <td><span class="dept-pill"><?= htmlspecialchars($emp['DeptName'] ?? '—') ?></span></td>
            <td style="color:var(--text-muted);"><?= htmlspecialchars($emp['Address']) ?></td>
            <td style="color:var(--text-soft);"><?= htmlspecialchars($emp['SupervisorName'] ?? '—') ?></td>
            <td>
              <span class="dep-badge" onclick="showDependents(<?= $emp['Emp_ID'] ?>, '<?= addslashes($emp['Name']) ?>')">View</span>
            </td>
            <td>
              <?php if ($_SESSION['role']==='admin'): ?>
              <button class="action-btn btn-primary-action" onclick="openModal('edit',<?= htmlspecialchars(json_encode($emp)) ?>)">Edit</button>
              <button class="action-btn btn-danger-action" onclick="deleteEmployee(<?= $emp['Emp_ID'] ?>, '<?= addslashes($emp['Name']) ?>')">Delete</button>
              <?php else: ?>
              <span style="color:var(--text-soft);font-size:12px;">View only</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div id="dt-bottom" class="dt-bottom"></div>
  </div>

</div>
</div>

<!-- ── Add/Edit Modal ── -->
<div class="ems-modal-overlay" id="empModal" onclick="closeOnOverlay(event)">
  <div class="ems-modal">
    <div class="modal-header">
      <h5 id="modal-title">Add Employee</h5>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form method="POST" action="employees.php" id="empForm">
      <div class="modal-body">
        <input type="hidden" name="action" id="form-action" value="add">
        <input type="hidden" name="emp_id" id="form-emp-id">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" id="f-name" class="form-control" placeholder="Ali Khan" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" id="f-email" class="form-control" placeholder="ali@company.com" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" id="f-address" class="form-control" placeholder="City, Pakistan">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="dept_id" id="f-dept" class="form-select">
              <option value="">— Select —</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?= $d['Dept_ID'] ?>"><?= htmlspecialchars($d['Name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Supervisor</label>
            <select name="supervisor_id" id="f-sup" class="form-select">
              <option value="">— None —</option>
              <?php foreach ($all_emps as $e): ?>
              <option value="<?= $e['Emp_ID'] ?>"><?= htmlspecialchars($e['Name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost-ems" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-primary-ems" id="form-submit-btn">Add Employee</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Dependents Modal ── -->
<div class="ems-modal-overlay" id="depModal" onclick="closeOnOverlay(event)">
  <div class="ems-modal" style="max-width:400px;">
    <div class="modal-header">
      <h5 id="dep-modal-title">Dependents</h5>
      <button class="modal-close" onclick="document.getElementById('depModal').classList.remove('open')">×</button>
    </div>
    <div class="modal-body" id="dep-modal-body" style="min-height:80px;">
      <div style="color:var(--text-muted);font-size:13px;">Loading…</div>
    </div>
  </div>
</div>

<?php include 'includes/scripts.php'; ?>
<script>
/* ── DataTable ── */
$(function(){
  $('#empTable').DataTable({
    dom:'<"dt-top-inner"lf>rt<"dt-bottom-inner"ip>',
    pageLength:8,
    lengthMenu:[[8,15,25,-1],[8,15,25,'All']],
    columnDefs:[{orderable:false,targets:[6,7]}],
    language:{search:'',searchPlaceholder:'Search employees...',lengthMenu:'Show _MENU_',info:'Showing _START_ to _END_ of _TOTAL_',paginate:{previous:'Prev',next:'Next'}},
    initComplete:function(){
      $('.dt-top-inner').appendTo('#dt-controls').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
      $('.dataTables_filter input').css({width:'240px'});
      $('.dt-bottom-inner').appendTo('#dt-bottom').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
    }
  });
});

/* ── Modal open/close ── */
function openModal(mode, empData) {
  const modal = document.getElementById('empModal');
  if (mode === 'add') {
    document.getElementById('modal-title').textContent = 'Add Employee';
    document.getElementById('form-action').value = 'add';
    document.getElementById('form-submit-btn').textContent = 'Add Employee';
    document.getElementById('empForm').reset();
  } else {
    document.getElementById('modal-title').textContent = 'Edit Employee';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-submit-btn').textContent = 'Save Changes';
    document.getElementById('form-emp-id').value  = empData.Emp_ID;
    document.getElementById('f-name').value    = empData.Name;
    document.getElementById('f-email').value   = empData.Email;
    document.getElementById('f-address').value = empData.Address || '';
    document.getElementById('f-dept').value    = empData.Dept_ID || '';
    document.getElementById('f-sup').value     = empData.Supervisor_ID || '';
  }
  modal.classList.add('open');
}
function closeModal() { document.getElementById('empModal').classList.remove('open'); }
function closeOnOverlay(e) { if(e.target===e.currentTarget) e.currentTarget.classList.remove('open'); }

/* Auto-open edit modal if URL has ?edit= */
<?php if ($edit_emp): ?>
openModal('edit', <?= json_encode($edit_emp) ?>);
<?php endif; ?>

/* ── Delete ── */
function deleteEmployee(id, name) {
  confirmDelete(`delete_employee.php?id=${id}`, name, 'Employee');
}

/* ── Dependents ── */
function showDependents(empId, empName) {
  const modal = document.getElementById('depModal');
  document.getElementById('dep-modal-title').textContent = `Dependents — ${empName}`;
  document.getElementById('dep-modal-body').innerHTML = '<div style="color:var(--text-muted);font-size:13px;">Loading…</div>';
  modal.classList.add('open');
  fetch(`get_dependents.php?emp_id=${empId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        document.getElementById('dep-modal-body').innerHTML = '<p style="color:var(--text-soft);font-size:13px;">No dependents on record.</p>';
        return;
      }
      let html = `<table class="table" style="width:100%;font-size:13px;"><thead><tr><th>Name</th><th>Relationship</th></tr></thead><tbody>`;
      data.forEach(d => { html += `<tr><td>${d.Name}</td><td style="color:var(--text-muted);">${d.Relationship||'—'}</td></tr>`; });
      html += '</tbody></table>';
      document.getElementById('dep-modal-body').innerHTML = html;
    });
}
</script>
</body>
</html>
