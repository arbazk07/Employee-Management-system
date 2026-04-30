<?php
// projects.php
session_start();
require_once 'config.php';
requireLogin();

$pdo = getDB();

// ── POST: Add / Edit ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $action  = $_POST['action'] ?? '';
    $name    = trim($_POST['name'] ?? '');
    $number  = (int)($_POST['number'] ?? 0);
    $dept_id = $_POST['dept_id'] ?: null;

    if ($name) {
        if ($action === 'add') {
            $pdo->prepare("INSERT INTO project (Name, Number, Dept_ID) VALUES (?,?,?)")
                ->execute([$name, $number ?: null, $dept_id]);
            setFlash('success', "Project <strong>$name</strong> created.");
        } elseif ($action === 'edit') {
            $proj_id = (int)($_POST['proj_id'] ?? 0);
            $pdo->prepare("UPDATE project SET Name=?, Number=?, Dept_ID=? WHERE Proj_ID=?")
                ->execute([$name, $number ?: null, $dept_id, $proj_id]);
            setFlash('success', "Project <strong>$name</strong> updated.");
        }
    }
    redirect('projects.php');
}

// ── AJAX delete ─────────────────────────────────────────
if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    header('Content-Type: application/json');
    $id = (int)$_GET['delete'];
    if (isset($_POST['confirm'])) {
        try {
            $pdo->prepare("DELETE FROM project WHERE Proj_ID=?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Delete failed.']);
        }
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'No confirmation.']);
    exit;
}

// ── Fetch projects with totals ───────────────────────────
$projects = $pdo->query("
    SELECT p.Proj_ID, p.Name, p.Number, d.Name AS DeptName, d.Dept_ID,
           COALESCE(SUM(w.Hours), 0)  AS TotalHours,
           COUNT(DISTINCT w.Emp_ID)   AS TeamSize
    FROM project p
    LEFT JOIN department d ON p.Dept_ID = d.Dept_ID
    LEFT JOIN works_on w   ON p.Proj_ID = w.Proj_ID
    GROUP BY p.Proj_ID
    ORDER BY p.Proj_ID ASC
")->fetchAll();

$depts    = $pdo->query("SELECT Dept_ID, Name FROM department ORDER BY Name")->fetchAll();
$max_hrs  = max(array_column($projects, 'TotalHours') ?: [1]);

$flash       = getFlash();
$active_page = 'projects';
$page_title  = 'Projects';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMS — Projects</title>
<?php include 'includes/style.php'; ?>
<style>
.projects-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;margin-bottom:2rem;}
.proj-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1.25rem;transition:border-color .15s;position:relative;}
.proj-card:hover{border-color:var(--border-hover);}
.proj-card-actions{position:absolute;top:1rem;right:1rem;display:flex;gap:6px;}
.proj-num{font-size:12px;font-weight:500;color:var(--text-muted);margin-bottom:4px;}
.proj-name{font-size:15px;font-weight:600;color:var(--text-base);margin-bottom:4px;padding-right:80px;}
.proj-dept{font-size:13px;color:var(--text-soft);margin-bottom:16px;}
.proj-stats{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:12px;}
.proj-stat-box{background:var(--bg-page);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 10px;}
.proj-stat-val{font-size:18px;font-weight:600;color:var(--text-base);}
.proj-stat-lbl{font-size:11px;color:var(--text-muted);margin-top:1px;}
.prog-bar{height:6px;background:var(--bg-page);border-radius:3px;overflow:hidden;margin-top:4px;}
.prog-fill{height:100%;border-radius:3px;}
/* Assign Modal table */
.assign-table{width:100%;border-collapse:collapse;font-size:13px;}
.assign-table th,.assign-table td{padding:8px 12px;border-bottom:1px solid var(--border);color:var(--text-muted);}
.assign-table th{color:var(--text-soft);font-weight:500;font-size:11px;text-transform:uppercase;background:var(--bg-page);}
.assign-table tr:last-child td{border-bottom:none;}
/* Modal */
.ems-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s;}
.ems-modal-overlay.open{opacity:1;pointer-events:all;}
.ems-modal{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);width:100%;max-width:480px;max-height:90vh;overflow-y:auto;transform:translateY(20px);transition:transform .2s;}
.ems-modal-overlay.open .ems-modal{transform:translateY(0);}
.modal-header{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-header h5{font-size:16px;font-weight:600;color:var(--text-base);margin:0;}
.modal-close{background:none;border:none;color:var(--text-soft);cursor:pointer;font-size:20px;line-height:1;padding:2px 6px;border-radius:4px;}
.modal-close:hover{background:var(--bg-page);color:var(--text-base);}
.modal-body{padding:1.5rem;}
.modal-footer{padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;}
.form-group{margin-bottom:1rem;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;}
.add-btn{background:var(--accent-blue);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 16px;font-size:13px;font-weight:500;cursor:pointer;transition:background .15s;display:flex;align-items:center;gap:6px;}
.add-btn:hover{background:#2563EB;}
.team-badge{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--accent-blue);padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;}
.team-badge:hover{background:rgba(59,130,246,.15);}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'includes/topbar.php'; ?>
<div class="page-body">

  <div class="page-header fade-up d1" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div><h2>Projects</h2><p>Track active projects, hours, and assigned staff.</p></div>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <button class="add-btn" onclick="openProjModal('add')">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      New Project
    </button>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
  <div class="<?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?> fade-up"><?= $flash['msg'] ?></div>
  <?php endif; ?>

  <!-- Project Cards -->
  <?php $proj_colors = ['var(--accent-blue)', 'var(--accent-amber)', 'var(--accent-emerald)', 'var(--accent-purple)']; ?>
  <div class="projects-grid fade-up d2">
    <?php foreach ($projects as $i => $p):
      $pct = $max_hrs > 0 ? round(($p['TotalHours'] / $max_hrs) * 100) : 0;
      $col = $proj_colors[$i % 4];
    ?>
    <div class="proj-card">
      <?php if ($_SESSION['role'] === 'admin'): ?>
      <div class="proj-card-actions">
        <button class="action-btn btn-primary-action" onclick="openProjModal('edit', <?= htmlspecialchars(json_encode($p)) ?>)">Edit</button>
        <button class="action-btn btn-danger-action" onclick="deleteProject(<?= $p['Proj_ID'] ?>, '<?= addslashes($p['Name']) ?>')">Del</button>
      </div>
      <?php endif; ?>
      <div class="proj-num">PRJ-<?= str_pad($p['Number'] ?? $p['Proj_ID'], 3, '0', STR_PAD_LEFT) ?></div>
      <div class="proj-name"><?= htmlspecialchars($p['Name']) ?></div>
      <div class="proj-dept"><?= htmlspecialchars($p['DeptName'] ?? '—') ?></div>
      <div class="proj-stats">
        <div class="proj-stat-box">
          <div class="proj-stat-val"><?= number_format($p['TotalHours'], 1) ?></div>
          <div class="proj-stat-lbl">Total Hours</div>
        </div>
        <div class="proj-stat-box">
          <div class="proj-stat-val"><?= $p['TeamSize'] ?></div>
          <div class="proj-stat-lbl">Team Members</div>
        </div>
      </div>
      <div style="font-size:12px;color:var(--text-soft);margin-bottom:4px;"><?= $pct ?>% of max workload</div>
      <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div></div>
      <div style="margin-top:10px;">
        <span class="team-badge" onclick="showTeam(<?= $p['Proj_ID'] ?>, '<?= addslashes($p['Name']) ?>')">👥 View Team</span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Projects Table -->
  <div class="table-card fade-up d3">
    <div class="table-card-header">
      <div><h4>Projects Overview</h4><p>Sortable directory of all projects</p></div>
    </div>
    <div id="dt-controls" class="dt-toolbar"></div>
    <div style="overflow-x:auto;">
      <table id="projTable" class="table" style="width:100%">
        <thead>
          <tr><th>#</th><th>Project</th><th>Number</th><th>Department</th><th>Hours</th><th>Team</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $i => $p): ?>
          <tr>
            <td style="color:var(--text-soft);"><?= $p['Proj_ID'] ?></td>
            <td style="font-weight:500;"><?= htmlspecialchars($p['Name']) ?></td>
            <td style="color:var(--text-muted);"><?= $p['Number'] ? 'PRJ-'.str_pad($p['Number'], 3, '0', STR_PAD_LEFT) : '—' ?></td>
            <td><span class="dept-pill"><?= htmlspecialchars($p['DeptName'] ?? '—') ?></span></td>
            <td style="color:var(--text-muted);"><span class="badge-up"><?= number_format($p['TotalHours'], 1) ?> hrs</span></td>
            <td><span class="team-badge" onclick="showTeam(<?= $p['Proj_ID'] ?>, '<?= addslashes($p['Name']) ?>')"><?= $p['TeamSize'] ?> members</span></td>
            <td>
              <?php if ($_SESSION['role'] === 'admin'): ?>
              <button class="action-btn btn-primary-action" onclick="openProjModal('edit', <?= htmlspecialchars(json_encode($p)) ?>)">Edit</button>
              <button class="action-btn btn-danger-action" onclick="deleteProject(<?= $p['Proj_ID'] ?>, '<?= addslashes($p['Name']) ?>')">Delete</button>
              <?php else: ?><span style="color:var(--text-soft);font-size:12px;">View only</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div id="dt-bottom" class="dt-bottom"></div>
  </div>

</div></div>

<!-- Add/Edit Modal -->
<div class="ems-modal-overlay" id="projModal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="ems-modal">
    <div class="modal-header">
      <h5 id="proj-modal-title">New Project</h5>
      <button class="modal-close" onclick="document.getElementById('projModal').classList.remove('open')">×</button>
    </div>
    <form method="POST" action="projects.php">
      <div class="modal-body">
        <input type="hidden" name="action" id="proj-action" value="add">
        <input type="hidden" name="proj_id" id="proj-id">
        <div class="form-group">
          <label class="form-label">Project Name *</label>
          <input type="text" name="name" id="proj-name" class="form-control" placeholder="e.g. Website Redesign" required>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Project Number</label>
            <input type="number" name="number" id="proj-number" class="form-control" placeholder="101">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Department</label>
            <select name="dept_id" id="proj-dept" class="form-select">
              <option value="">— Select —</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?= $d['Dept_ID'] ?>"><?= htmlspecialchars($d['Name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost-ems" onclick="document.getElementById('projModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn-primary-ems" id="proj-submit">Create Project</button>
      </div>
    </form>
  </div>
</div>

<!-- Team Modal -->
<div class="ems-modal-overlay" id="teamModal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="ems-modal" style="max-width:500px;">
    <div class="modal-header">
      <h5 id="team-modal-title">Project Team</h5>
      <button class="modal-close" onclick="document.getElementById('teamModal').classList.remove('open')">×</button>
    </div>
    <div class="modal-body" id="team-modal-body" style="min-height:80px;">
      <div style="color:var(--text-muted);font-size:13px;">Loading…</div>
    </div>
  </div>
</div>

<?php include 'includes/scripts.php'; ?>
<script>
$(function(){
  $('#projTable').DataTable({
    dom:'<"dt-top-inner"lf>rt<"dt-bottom-inner"ip>',
    pageLength:8,
    columnDefs:[{orderable:false,targets:6}],
    language:{search:'',searchPlaceholder:'Search projects...',lengthMenu:'Show _MENU_',paginate:{previous:'Prev',next:'Next'}},
    initComplete:function(){
      $('.dt-top-inner').appendTo('#dt-controls').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
      $('.dataTables_filter input').css({width:'220px'});
      $('.dt-bottom-inner').appendTo('#dt-bottom').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
    }
  });
});

function openProjModal(mode, data) {
  document.getElementById('projModal').classList.add('open');
  if (mode === 'add') {
    document.getElementById('proj-modal-title').textContent = 'New Project';
    document.getElementById('proj-action').value = 'add';
    document.getElementById('proj-submit').textContent = 'Create Project';
    document.getElementById('proj-name').value   = '';
    document.getElementById('proj-number').value = '';
    document.getElementById('proj-dept').value   = '';
  } else {
    document.getElementById('proj-modal-title').textContent = 'Edit Project';
    document.getElementById('proj-action').value = 'edit';
    document.getElementById('proj-submit').textContent = 'Save Changes';
    document.getElementById('proj-id').value     = data.Proj_ID;
    document.getElementById('proj-name').value   = data.Name;
    document.getElementById('proj-number').value = data.Number || '';
    document.getElementById('proj-dept').value   = data.Dept_ID || '';
  }
}

function deleteProject(id, name) {
  confirmDelete(`projects.php?delete=${id}`, name, 'Project');
}

function showTeam(projId, projName) {
  const modal = document.getElementById('teamModal');
  document.getElementById('team-modal-title').textContent = `Team — ${projName}`;
  document.getElementById('team-modal-body').innerHTML = '<div style="color:var(--text-muted);font-size:13px;">Loading…</div>';
  modal.classList.add('open');
  fetch(`get_project_team.php?proj_id=${projId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        document.getElementById('team-modal-body').innerHTML = '<p style="color:var(--text-soft);font-size:13px;">No employees assigned to this project.</p>';
        return;
      }
      let html = `<table class="assign-table"><thead><tr><th>Employee</th><th>Department</th><th>Hours</th></tr></thead><tbody>`;
      data.forEach(m => {
        html += `<tr>
          <td><div class="emp-cell"><div class="emp-av av-blue" style="font-size:10px;">${m.initials}</div>${m.Name}</div></td>
          <td style="color:var(--text-muted);">${m.DeptName||'—'}</td>
          <td><span class="badge-up">${parseFloat(m.Hours).toFixed(1)} hrs</span></td>
        </tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('team-modal-body').innerHTML = html;
    });
}
</script>
</body>
</html>
