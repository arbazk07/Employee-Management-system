<?php
// dashboard.php
session_start();
require_once 'config.php';
requireLogin();

$pdo = getDB();

// ── Aggregate stats ──────────────────────────────────────
$total_emp  = $pdo->query("SELECT COUNT(*) FROM employee")->fetchColumn();
$total_dept = $pdo->query("SELECT COUNT(*) FROM department")->fetchColumn();
$total_proj = $pdo->query("SELECT COUNT(*) FROM project")->fetchColumn();
$total_hrs  = $pdo->query("SELECT COALESCE(SUM(Hours),0) FROM works_on")->fetchColumn();

// ── Dept headcount for bar chart ─────────────────────────
$dept_counts = $pdo->query("
    SELECT d.Name, COUNT(e.Emp_ID) AS cnt
    FROM department d
    LEFT JOIN employee e ON d.Dept_ID = e.Dept_ID
    GROUP BY d.Dept_ID, d.Name
    ORDER BY d.Name
")->fetchAll();

// ── Project hours for doughnut chart ─────────────────────
$proj_hours = $pdo->query("
    SELECT p.Name, COALESCE(SUM(w.Hours),0) AS hrs
    FROM project p
    LEFT JOIN works_on w ON p.Proj_ID = w.Proj_ID
    GROUP BY p.Proj_ID, p.Name
")->fetchAll();

// ── Recent employees ─────────────────────────────────────
$recent_emps = $pdo->query("
    SELECT e.Emp_ID, e.Name, e.Email, e.Address,
           d.Name AS DeptName,
           s.Name AS SupervisorName
    FROM employee e
    LEFT JOIN department d ON e.Dept_ID = d.Dept_ID
    LEFT JOIN employee s ON e.Supervisor_ID = s.Emp_ID
    ORDER BY e.Emp_ID DESC
    LIMIT 10
")->fetchAll();

// ── Active projects ───────────────────────────────────────
$projects = $pdo->query("
    SELECT p.Proj_ID, p.Name, p.Number, d.Name AS DeptName,
           COALESCE(SUM(w.Hours),0) AS TotalHours
    FROM project p
    LEFT JOIN department d ON p.Dept_ID = d.Dept_ID
    LEFT JOIN works_on w ON p.Proj_ID = w.Proj_ID
    GROUP BY p.Proj_ID
")->fetchAll();

$max_hours = max(array_column($projects, 'TotalHours') ?: [1]);

// Chart JSON
$chart_dept_labels = json_encode(array_column($dept_counts, 'Name'));
$chart_dept_data   = json_encode(array_column($dept_counts, 'cnt'));
$chart_proj_labels = json_encode(array_column($proj_hours, 'Name'));
$chart_proj_data   = json_encode(array_column($proj_hours, 'hrs'));

$active_page = 'dashboard';
$page_title  = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMS — Dashboard</title>
<?php include 'includes/style.php'; ?>
<style>
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:2rem;}
.chart-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);box-shadow:var(--shadow-sm);}
.chart-card-header{padding:1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.chart-card-header h5{font-size:14px;font-weight:600;color:var(--text-base);margin:0;}
.chart-card-header p{font-size:13px;color:var(--text-muted);margin-top:4px;}
.chart-body{padding:1.25rem;}
.projects-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
.proj-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1.25rem;box-shadow:var(--shadow-sm);transition:border-color .15s;}
.proj-card:hover{border-color:var(--border-hover);}
.proj-num{font-size:12px;font-weight:500;color:var(--text-muted);margin-bottom:4px;}
.proj-name{font-size:15px;font-weight:600;color:var(--text-base);margin-bottom:4px;}
.proj-dept{font-size:13px;color:var(--text-soft);margin-bottom:16px;}
.proj-hrs{font-size:22px;font-weight:600;color:var(--text-base);line-height:1;}
.proj-hrs-label{font-size:12px;color:var(--text-muted);margin-top:4px;}
.prog-bar{height:6px;background:var(--bg-page);border-radius:3px;overflow:hidden;margin-top:12px;}
.prog-fill{height:100%;border-radius:3px;}
.split-row{display:grid;grid-template-columns:1fr 320px;gap:1rem;margin-bottom:2rem;}
.widget-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1.25rem;}
.widget-title{font-size:14px;font-weight:600;color:var(--text-base);margin-bottom:1.25rem;}
.dept-row{display:flex;align-items:center;margin-bottom:12px;}
.dept-row:last-child{margin-bottom:0;}
.dept-label{width:70px;font-size:13px;color:var(--text-muted);flex-shrink:0;}
.dept-bar-bg{flex:1;height:6px;background:var(--bg-page);border-radius:3px;overflow:hidden;}
.dept-bar-fill{height:100%;border-radius:3px;}
.dept-n{width:24px;text-align:right;font-size:13px;font-weight:500;color:var(--text-muted);margin-left:10px;}
.activity-item{display:flex;gap:12px;margin-bottom:16px;}
.activity-item:last-child{margin-bottom:0;}
.act-dot{width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;}
.ad-blue{background:var(--accent-blue);}.ad-green{background:var(--accent-emerald);}.ad-amber{background:var(--accent-amber);}
.act-text{font-size:13px;color:var(--text-base);line-height:1.4;}
.act-time{font-size:12px;color:var(--text-soft);margin-top:2px;}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'includes/topbar.php'; ?>
<div class="page-body">

  <div class="page-header fade-up d1">
    <h2>Overview</h2>
    <p>High-level metrics and current operations.</p>
  </div>

  <!-- ── Stat Cards ── -->
  <div class="stat-grid">
    <div class="stat-card fade-up d1">
      <div class="stat-top">
        <span class="stat-label">Total Employees</span>
        <div class="stat-icon-wrap si-blue">
          <svg viewBox="0 0 17 17"><circle cx="8.5" cy="6" r="2.8"/><path d="M2 15c0-3 2.9-5 6.5-5s6.5 2 6.5 5" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
        </div>
      </div>
      <div class="stat-value stat-counter" data-target="<?= $total_emp ?>">0</div>
      <div class="stat-meta"><span class="badge-up">Active</span> staff members</div>
    </div>
    <div class="stat-card fade-up d2">
      <div class="stat-top">
        <span class="stat-label">Departments</span>
        <div class="stat-icon-wrap si-purple">
          <svg viewBox="0 0 17 17"><rect x="1" y="9" width="3" height="6" rx="1"/><rect x="6" y="6" width="3" height="9" rx="1"/><rect x="11" y="3" width="3" height="12" rx="1"/></svg>
        </div>
      </div>
      <div class="stat-value stat-counter" data-target="<?= $total_dept ?>">0</div>
      <div class="stat-meta">Active divisions</div>
    </div>
    <div class="stat-card fade-up d3">
      <div class="stat-top">
        <span class="stat-label">Active Projects</span>
        <div class="stat-icon-wrap si-amber">
          <svg viewBox="0 0 17 17"><path d="M2 4h13v2H2V4zm1 3h11l-1.5 8H4.5L3 7z"/></svg>
        </div>
      </div>
      <div class="stat-value stat-counter" data-target="<?= $total_proj ?>">0</div>
      <div class="stat-meta">Across all departments</div>
    </div>
    <div class="stat-card fade-up d4">
      <div class="stat-top">
        <span class="stat-label">Hours Logged</span>
        <div class="stat-icon-wrap si-green">
          <svg viewBox="0 0 17 17"><circle cx="8.5" cy="8.5" r="6" stroke-width="1.5" fill="none"/><path d="M8.5 5v3.5l2.5 2" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
        </div>
      </div>
      <div class="stat-value stat-counter" data-target="<?= (int)$total_hrs ?>">0</div>
      <div class="stat-meta">Current billing cycle</div>
    </div>
  </div>

  <!-- ── Charts ── -->
  <div class="charts-row fade-up d3">
    <div class="chart-card">
      <div class="chart-card-header">
        <div><h5>Headcount Distribution</h5><p>Employees per department</p></div>
      </div>
      <div class="chart-body" style="height:240px;position:relative;">
        <canvas id="deptChart"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <div class="chart-card-header">
        <div><h5>Resource Allocation</h5><p>Total hours by project</p></div>
      </div>
      <div class="chart-body" style="height:240px;position:relative;">
        <canvas id="projChart"></canvas>
      </div>
    </div>
  </div>

  <!-- ── Employee Table ── -->
  <div class="table-card fade-up d4">
    <div class="table-card-header">
      <div><h4>Employee Directory</h4><p>Comprehensive staff listings</p></div>
      <a href="employees.php" class="view-link">Manage Directory</a>
    </div>
    <div id="dt-controls" class="dt-toolbar"></div>
    <div style="overflow-x:auto;">
      <table id="empTable" class="table" style="width:100%">
        <thead>
          <tr>
            <th>Employee</th><th>Email</th><th>Department</th>
            <th>Location</th><th>Supervisor</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_emps as $emp): ?>
          <tr>
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
              <a href="employees.php?edit=<?= $emp['Emp_ID'] ?>" class="action-btn btn-primary-action">Edit</a>
              <button class="action-btn btn-danger-action" onclick="deleteEmpDash(<?= $emp['Emp_ID'] ?>, '<?= addslashes($emp['Name']) ?>')">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div id="dt-bottom" class="dt-bottom"></div>
  </div>

  <!-- ── Projects ── -->
  <div class="section-heading fade-up d5">
    <h4>Active Projects</h4>
    <a href="projects.php" class="view-link">View All</a>
  </div>
  <div class="projects-grid fade-up d5">
    <?php
    $proj_colors = ['var(--accent-blue)','var(--accent-amber)','var(--accent-emerald)','var(--accent-purple)'];
    foreach ($projects as $i => $p):
      $pct = $max_hours > 0 ? round(($p['TotalHours'] / $max_hours) * 100) : 0;
      $col = $proj_colors[$i % 4];
    ?>
    <div class="proj-card">
      <div class="proj-num">PRJ-<?= str_pad($p['Number'] ?? $p['Proj_ID'], 3, '0', STR_PAD_LEFT) ?></div>
      <div class="proj-name"><?= htmlspecialchars($p['Name']) ?></div>
      <div class="proj-dept"><?= htmlspecialchars($p['DeptName'] ?? '—') ?></div>
      <div class="proj-hrs"><?= number_format($p['TotalHours'], 1) ?></div>
      <div class="proj-hrs-label">Tracked Hours</div>
      <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Widgets Row ── -->
  <div class="split-row fade-up d6">
    <div class="widget-card">
      <div class="widget-title">Headcount by Division</div>
      <?php
      $bar_colors = ['var(--accent-blue)','var(--accent-emerald)','var(--accent-amber)','var(--accent-purple)'];
      $max_cnt = max(array_column($dept_counts,'cnt') ?: [1]);
      foreach ($dept_counts as $i => $dc):
        $bpct = round(($dc['cnt'] / $max_cnt) * 100);
      ?>
      <div class="dept-row">
        <span class="dept-label"><?= htmlspecialchars($dc['Name']) ?></span>
        <div class="dept-bar-bg"><div class="dept-bar-fill" style="width:<?= $bpct ?>%;background:<?= $bar_colors[$i%4] ?>;"></div></div>
        <span class="dept-n"><?= $dc['cnt'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="widget-card">
      <div class="widget-title">Activity Log</div>
      <div class="activity-item"><div class="act-dot ad-green"></div><div><div class="act-text">System loaded <strong><?= $total_emp ?> employees</strong></div><div class="act-time">Just now</div></div></div>
      <div class="activity-item"><div class="act-dot ad-blue"></div><div><div class="act-text"><?= $total_proj ?> active projects tracked</div><div class="act-time">Current cycle</div></div></div>
      <div class="activity-item"><div class="act-dot ad-amber"></div><div><div class="act-text"><?= number_format($total_hrs, 1) ?> total hours logged</div><div class="act-time">All time</div></div></div>
    </div>
  </div>

</div><!-- /page-body -->
</div><!-- /main-wrap -->

<?php include 'includes/scripts.php'; ?>
<script>
/* Counter animation */
document.querySelectorAll('.stat-counter').forEach(el => {
  const target = +el.getAttribute('data-target');
  const step = target / (1200 / 16);
  let current = 0;
  const update = () => {
    current += step;
    if (current < target) { el.innerText = Math.ceil(current); requestAnimationFrame(update); }
    else el.innerText = target;
  };
  setTimeout(update, 150);
});

Chart.defaults.color = '#94A3B8';
Chart.defaults.font.family = "'Inter', sans-serif";

/* Bar chart */
new Chart(document.getElementById('deptChart'), {
  type: 'bar',
  data: {
    labels: <?= $chart_dept_labels ?>,
    datasets: [{ label:'Headcount', data: <?= $chart_dept_data ?>,
      backgroundColor: ['#10B981','#3B82F6','#F59E0B','#8B5CF6'],
      borderRadius: 4, borderSkipped: false, maxBarThickness: 40
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins: { legend:{display:false}, tooltip:{...entTip} },
    scales: {
      x: { grid:{display:false}, border:{display:false} },
      y: { beginAtZero:true, grid:{color:'#334155'}, border:{display:false}, ticks:{stepSize:1} }
    },
    interaction: { mode:'index', intersect:false }
  }
});

/* Doughnut chart */
new Chart(document.getElementById('projChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $chart_proj_labels ?>,
    datasets: [{ data: <?= $chart_proj_data ?>,
      backgroundColor:['#3B82F6','#F59E0B','#10B981','#8B5CF6'],
      borderWidth:2, borderColor:'#1E293B', hoverOffset:4
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false, cutout:'75%',
    plugins: {
      legend: { position:'right', labels:{padding:20,boxWidth:8,boxHeight:8,usePointStyle:true,font:{size:12}} },
      tooltip: { ...entTip, callbacks:{ label: c=>` ${c.parsed} hours` } }
    }
  }
});

/* DataTable */
$(function(){
  $('#empTable').DataTable({
    dom: '<"dt-top-inner"lf>rt<"dt-bottom-inner"ip>',
    pageLength: 5,
    lengthMenu: [[5,8,10,-1],[5,8,10,'All']],
    columnDefs: [{ orderable:false, targets:5 }],
    language: { search:'', searchPlaceholder:'Search directory...', lengthMenu:'Show _MENU_', info:'Showing _START_ to _END_ of _TOTAL_', paginate:{previous:'Prev',next:'Next'} },
    initComplete: function() {
      $('.dt-top-inner').appendTo('#dt-controls').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
      $('.dataTables_filter input').css({width:'240px'});
      $('.dt-bottom-inner').appendTo('#dt-bottom').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
    }
  });
});

function deleteEmpDash(id, name) {
  confirmDelete(`delete_employee.php?id=${id}`, name, 'Employee');
}
</script>
</body>
</html>
