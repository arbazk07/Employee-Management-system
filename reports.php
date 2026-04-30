<?php
// reports.php — Analytics, aggregates, joins, subqueries
session_start();
require_once 'config.php';
requireLogin();

$pdo = getDB();

// ── Filter: min hours threshold ──────────────────────────
$min_hours = max(0, (float)($_GET['min_hours'] ?? 0));
$dept_filter = (int)($_GET['dept_id'] ?? 0);

// ── 1. Employees working MORE than X hours (subquery) ────
$heavy_workers = $pdo->prepare("
    SELECT e.Emp_ID, e.Name, e.Email, d.Name AS DeptName,
           SUM(w.Hours) AS TotalHours,
           COUNT(w.Proj_ID) AS ProjectCount
    FROM employee e
    JOIN works_on w    ON e.Emp_ID  = w.Emp_ID
    LEFT JOIN department d ON e.Dept_ID = d.Dept_ID
    " . ($dept_filter ? "WHERE e.Dept_ID = $dept_filter" : "") . "
    GROUP BY e.Emp_ID
    HAVING SUM(w.Hours) > ?
    ORDER BY TotalHours DESC
");
$heavy_workers->execute([$min_hours]);
$heavy_workers = $heavy_workers->fetchAll();

// ── 2. Project summary (join + aggregate) ─────────────────
$proj_summary = $pdo->query("
    SELECT p.Name AS ProjectName, d.Name AS DeptName,
           COUNT(DISTINCT w.Emp_ID)  AS TeamSize,
           COALESCE(SUM(w.Hours), 0) AS TotalHours,
           COALESCE(AVG(w.Hours), 0) AS AvgHours,
           COALESCE(MAX(w.Hours), 0) AS MaxHours,
           COALESCE(MIN(w.Hours), 0) AS MinHours
    FROM project p
    LEFT JOIN department d ON p.Dept_ID  = d.Dept_ID
    LEFT JOIN works_on w   ON p.Proj_ID  = w.Proj_ID
    GROUP BY p.Proj_ID
    ORDER BY TotalHours DESC
")->fetchAll();

// ── 3. Employees with NO project assignment (subquery) ────
$unassigned = $pdo->query("
    SELECT e.Emp_ID, e.Name, e.Email, d.Name AS DeptName
    FROM employee e
    LEFT JOIN department d ON e.Dept_ID = d.Dept_ID
    WHERE e.Emp_ID NOT IN (SELECT DISTINCT Emp_ID FROM works_on)
    ORDER BY e.Name
")->fetchAll();

// ── 4. Department productivity (group + aggregate) ────────
$dept_prod = $pdo->query("
    SELECT d.Name AS DeptName,
           COUNT(DISTINCT e.Emp_ID)  AS Employees,
           COUNT(DISTINCT p.Proj_ID) AS Projects,
           COALESCE(SUM(w.Hours), 0) AS TotalHours,
           COALESCE(AVG(w.Hours), 0) AS AvgHoursPerEmployee
    FROM department d
    LEFT JOIN employee e ON e.Dept_ID  = d.Dept_ID
    LEFT JOIN works_on w ON w.Emp_ID   = e.Emp_ID
    LEFT JOIN project p  ON p.Proj_ID  = w.Proj_ID
    GROUP BY d.Dept_ID
    ORDER BY TotalHours DESC
")->fetchAll();

// ── 5. Top performers (subquery inside ORDER) ─────────────
$top_performers = $pdo->query("
    SELECT e.Name, d.Name AS DeptName,
           SUM(w.Hours) AS TotalHours,
           COUNT(DISTINCT w.Proj_ID) AS Projects
    FROM employee e
    JOIN works_on w    ON e.Emp_ID  = w.Emp_ID
    LEFT JOIN department d ON e.Dept_ID = d.Dept_ID
    GROUP BY e.Emp_ID
    ORDER BY TotalHours DESC
    LIMIT 5
")->fetchAll();

// Chart data
$chart_dept_labels  = json_encode(array_column($dept_prod, 'DeptName'));
$chart_dept_hours   = json_encode(array_column($dept_prod, 'TotalHours'));
$chart_dept_emp     = json_encode(array_column($dept_prod, 'Employees'));

$depts = $pdo->query("SELECT Dept_ID, Name FROM department ORDER BY Name")->fetchAll();

$active_page = 'reports';
$page_title  = 'Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMS — Reports</title>
<?php include 'includes/style.php'; ?>
<style>
.report-section{margin-bottom:2.5rem;}
.report-section-title{font-size:16px;font-weight:600;color:var(--text-base);margin-bottom:.5rem;display:flex;align-items:center;gap:8px;}
.report-section-title span{font-size:12px;font-weight:400;color:var(--text-muted);}
.charts-row{display:grid;grid-template-columns:1.5fr 1fr;gap:1rem;margin-bottom:2rem;}
.chart-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);}
.chart-card-header{padding:1.25rem;border-bottom:1px solid var(--border);}
.chart-card-header h5{font-size:14px;font-weight:600;color:var(--text-base);margin:0;}
.chart-card-header p{font-size:13px;color:var(--text-muted);margin-top:4px;}
.chart-body{padding:1.25rem;}
/* filter bar */
.filter-bar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1.25rem;margin-bottom:1.5rem;display:flex;align-items:flex-end;gap:1rem;flex-wrap:wrap;}
.filter-group{display:flex;flex-direction:column;gap:6px;min-width:180px;}
.filter-group label{font-size:12px;font-weight:500;color:var(--text-muted);}
.filter-bar .btn-primary-ems{align-self:flex-end;}
/* performer cards */
.performers-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:2rem;}
.performer-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1rem;text-align:center;transition:border-color .15s;}
.performer-card:hover{border-color:var(--border-hover);}
.performer-rank{font-size:11px;font-weight:600;color:var(--text-soft);margin-bottom:8px;text-transform:uppercase;}
.performer-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;margin:0 auto 8px;}
.performer-name{font-size:13px;font-weight:600;color:var(--text-base);margin-bottom:2px;}
.performer-dept{font-size:12px;color:var(--text-muted);margin-bottom:8px;}
.performer-hrs{font-size:20px;font-weight:600;color:var(--text-base);}
.performer-hrs-lbl{font-size:11px;color:var(--text-muted);}
/* stat summary row */
.summary-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
.summary-box{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1rem 1.25rem;}
.summary-box-label{font-size:12px;font-weight:500;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;}
.summary-box-value{font-size:22px;font-weight:600;color:var(--text-base);}
.summary-box-sub{font-size:12px;color:var(--text-soft);margin-top:2px;}
/* export btn */
.export-btn{background:transparent;border:1px solid var(--border);color:var(--text-muted);border-radius:var(--radius-sm);padding:6px 12px;font-size:12px;font-weight:500;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:6px;}
.export-btn:hover{background:var(--bg-card);color:var(--text-base);border-color:var(--border-hover);}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'includes/topbar.php'; ?>
<div class="page-body">

  <div class="page-header fade-up d1" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div>
      <h2>Reports &amp; Analytics</h2>
      <p>Aggregated insights using joins, subqueries, and aggregate functions.</p>
    </div>
    <button class="export-btn" onclick="window.print()">
      <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><rect x="1" y="4" width="11" height="7" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V2a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="1.2"/><path d="M4 9h5M4 11h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Print Report
    </button>
  </div>

  <!-- Summary KPIs -->
  <?php
    $total_hrs  = array_sum(array_column($proj_summary, 'TotalHours'));
    $avg_hrs    = count($heavy_workers) ? array_sum(array_column($heavy_workers, 'TotalHours')) / count($heavy_workers) : 0;
    $max_team   = count($proj_summary)  ? max(array_column($proj_summary, 'TeamSize')) : 0;
  ?>
  <div class="summary-row fade-up d2">
    <div class="summary-box">
      <div class="summary-box-label">Total Hours Logged</div>
      <div class="summary-box-value"><?= number_format($total_hrs, 1) ?></div>
      <div class="summary-box-sub">Across all projects</div>
    </div>
    <div class="summary-box">
      <div class="summary-box-label">Unassigned Employees</div>
      <div class="summary-box-value"><?= count($unassigned) ?></div>
      <div class="summary-box-sub">No project assigned</div>
    </div>
    <div class="summary-box">
      <div class="summary-box-label">Avg Hours / Project</div>
      <div class="summary-box-value"><?= count($proj_summary) ? number_format($total_hrs / count($proj_summary), 1) : '0' ?></div>
      <div class="summary-box-sub">Across <?= count($proj_summary) ?> projects</div>
    </div>
    <div class="summary-box">
      <div class="summary-box-label">Largest Project Team</div>
      <div class="summary-box-value"><?= $max_team ?></div>
      <div class="summary-box-sub">members on one project</div>
    </div>
  </div>

  <!-- Charts -->
  <div class="charts-row fade-up d2">
    <div class="chart-card">
      <div class="chart-card-header">
        <h5>Department Productivity</h5>
        <p>Total hours logged vs employee headcount per department</p>
      </div>
      <div class="chart-body" style="height:260px;position:relative;">
        <canvas id="deptProdChart"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <div class="chart-card-header">
        <h5>Hours Distribution</h5>
        <p>Share of total hours per department</p>
      </div>
      <div class="chart-body" style="height:260px;position:relative;">
        <canvas id="hoursDistChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Top Performers -->
  <div class="report-section fade-up d3">
    <div class="report-section-title">
      🏆 Top Performers
      <span>— ranked by total hours logged</span>
    </div>
    <div class="performers-grid">
      <?php
      $rank_colors = ['av-blue','av-green','av-amber','av-rose','av-purp'];
      $rank_labels = ['1st','2nd','3rd','4th','5th'];
      foreach ($top_performers as $idx => $p):
        $ini = initials($p['Name']);
        $cls = $rank_colors[$idx];
      ?>
      <div class="performer-card">
        <div class="performer-rank"><?= $rank_labels[$idx] ?? ($idx+1).'th' ?></div>
        <div class="performer-av <?= $cls ?>"><?= $ini ?></div>
        <div class="performer-name"><?= htmlspecialchars($p['Name']) ?></div>
        <div class="performer-dept"><?= htmlspecialchars($p['DeptName'] ?? '—') ?></div>
        <div class="performer-hrs"><?= number_format($p['TotalHours'], 1) ?></div>
        <div class="performer-hrs-lbl"><?= $p['Projects'] ?> project<?= $p['Projects']!=1?'s':'' ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Filter: heavy workers -->
  <div class="report-section fade-up d3">
    <div class="report-section-title">
      ⏱ Workload Filter
      <span>— employees exceeding hour threshold (HAVING clause)</span>
    </div>
    <div class="filter-bar">
      <form method="GET" action="reports.php" style="display:contents;">
        <div class="filter-group">
          <label>Minimum Total Hours</label>
          <input type="number" name="min_hours" class="form-control" style="width:160px;"
                 value="<?= htmlspecialchars($min_hours) ?>" placeholder="e.g. 20" min="0" step="0.5">
        </div>
        <div class="filter-group">
          <label>Filter by Department</label>
          <select name="dept_id" class="form-select" style="width:180px;">
            <option value="">All Departments</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['Dept_ID'] ?>" <?= $dept_filter==$d['Dept_ID']?'selected':'' ?>>
              <?= htmlspecialchars($d['Name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-primary-ems">Apply Filter</button>
        <a href="reports.php" class="btn-ghost-ems" style="text-decoration:none;">Reset</a>
      </form>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <div>
          <h4>Employees &gt; <?= number_format($min_hours, 1) ?> Hours</h4>
          <p><?= count($heavy_workers) ?> result<?= count($heavy_workers)!=1?'s':'' ?> found</p>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table id="heavyTable" class="table" style="width:100%">
          <thead>
            <tr><th>Employee</th><th>Department</th><th>Email</th><th>Total Hours</th><th>Projects</th></tr>
          </thead>
          <tbody>
            <?php if (empty($heavy_workers)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem!important;">
              No employees found with more than <?= number_format($min_hours,1) ?> hours.
            </td></tr>
            <?php else: foreach ($heavy_workers as $w): ?>
            <tr>
              <td>
                <div class="emp-cell">
                  <div class="emp-av <?= avatarClass($w['Emp_ID']) ?>"><?= initials($w['Name']) ?></div>
                  <?= htmlspecialchars($w['Name']) ?>
                </div>
              </td>
              <td><span class="dept-pill"><?= htmlspecialchars($w['DeptName'] ?? '—') ?></span></td>
              <td style="color:var(--text-muted);"><?= htmlspecialchars($w['Email']) ?></td>
              <td>
                <span class="badge-up"><?= number_format($w['TotalHours'], 1) ?> hrs</span>
              </td>
              <td style="color:var(--text-muted);"><?= $w['ProjectCount'] ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Project Summary -->
  <div class="report-section fade-up d4">
    <div class="report-section-title">
      📊 Project Summary
      <span>— aggregates per project (SUM, AVG, MAX, MIN)</span>
    </div>
    <div class="table-card">
      <div class="table-card-header">
        <div><h4>Project Metrics</h4><p>Hours breakdown per project using aggregate functions</p></div>
      </div>
      <div id="dt-controls3" class="dt-toolbar"></div>
      <div style="overflow-x:auto;">
        <table id="projSummaryTable" class="table" style="width:100%">
          <thead>
            <tr><th>Project</th><th>Department</th><th>Team</th><th>Total Hrs</th><th>Avg Hrs</th><th>Max Hrs</th><th>Min Hrs</th></tr>
          </thead>
          <tbody>
            <?php foreach ($proj_summary as $ps): ?>
            <tr>
              <td style="font-weight:500;"><?= htmlspecialchars($ps['ProjectName']) ?></td>
              <td><span class="dept-pill"><?= htmlspecialchars($ps['DeptName'] ?? '—') ?></span></td>
              <td style="color:var(--text-muted);"><?= $ps['TeamSize'] ?> members</td>
              <td><span class="badge-up"><?= number_format($ps['TotalHours'], 1) ?></span></td>
              <td style="color:var(--text-muted);"><?= number_format($ps['AvgHours'], 1) ?></td>
              <td style="color:var(--text-muted);"><?= number_format($ps['MaxHours'], 1) ?></td>
              <td style="color:var(--text-muted);"><?= number_format($ps['MinHours'], 1) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div id="dt-bottom3" class="dt-bottom"></div>
    </div>
  </div>

  <!-- Unassigned Employees -->
  <?php if (!empty($unassigned)): ?>
  <div class="report-section fade-up d5">
    <div class="report-section-title">
      ⚠️ Unassigned Employees
      <span>— no works_on record (NOT IN subquery)</span>
    </div>
    <div class="table-card">
      <div class="table-card-header">
        <div><h4>Employees Without Projects</h4><p>These staff members have no project assignments</p></div>
      </div>
      <div style="overflow-x:auto;">
        <table class="table" style="width:100%">
          <thead><tr><th>Employee</th><th>Department</th><th>Email</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($unassigned as $u): ?>
            <tr>
              <td>
                <div class="emp-cell">
                  <div class="emp-av <?= avatarClass($u['Emp_ID']) ?>"><?= initials($u['Name']) ?></div>
                  <?= htmlspecialchars($u['Name']) ?>
                </div>
              </td>
              <td><span class="dept-pill"><?= htmlspecialchars($u['DeptName'] ?? '—') ?></span></td>
              <td style="color:var(--text-muted);"><?= htmlspecialchars($u['Email']) ?></td>
              <td><a href="projects.php" class="action-btn btn-primary-action">Assign to Project</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Dept Productivity Table -->
  <div class="report-section fade-up d5">
    <div class="report-section-title">
      🏢 Department Productivity
      <span>— multi-table join with GROUP BY</span>
    </div>
    <div class="table-card">
      <div class="table-card-header">
        <div><h4>Department Stats</h4><p>Productivity metrics per division</p></div>
      </div>
      <div style="overflow-x:auto;">
        <table class="table" style="width:100%">
          <thead><tr><th>Department</th><th>Employees</th><th>Projects</th><th>Total Hours</th><th>Avg Hrs / Member</th></tr></thead>
          <tbody>
            <?php foreach ($dept_prod as $dp): ?>
            <tr>
              <td style="font-weight:500;"><?= htmlspecialchars($dp['DeptName']) ?></td>
              <td style="color:var(--text-muted);"><?= $dp['Employees'] ?></td>
              <td style="color:var(--text-muted);"><?= $dp['Projects'] ?></td>
              <td><span class="badge-up"><?= number_format($dp['TotalHours'], 1) ?> hrs</span></td>
              <td style="color:var(--text-muted);"><?= number_format($dp['AvgHoursPerEmployee'], 1) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div></div>

<?php include 'includes/scripts.php'; ?>
<script>
Chart.defaults.color = '#94A3B8';
Chart.defaults.font.family = "'Inter', sans-serif";

/* Grouped bar: total hours + headcount per dept */
new Chart(document.getElementById('deptProdChart'), {
  type: 'bar',
  data: {
    labels: <?= $chart_dept_labels ?>,
    datasets: [
      {
        label: 'Total Hours',
        data: <?= $chart_dept_hours ?>,
        backgroundColor: 'rgba(59,130,246,.75)',
        borderRadius: 4,
        borderSkipped: false,
        yAxisID: 'y',
        maxBarThickness: 32
      },
      {
        label: 'Employees',
        data: <?= $chart_dept_emp ?>,
        backgroundColor: 'rgba(16,185,129,.6)',
        borderRadius: 4,
        borderSkipped: false,
        yAxisID: 'y1',
        maxBarThickness: 32
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { padding: 16, boxWidth: 10, usePointStyle: true } }, tooltip: { ...entTip, mode:'index', intersect:false } },
    scales: {
      x: { grid:{display:false}, border:{display:false} },
      y: { beginAtZero:true, grid:{color:'#334155'}, border:{display:false}, title:{display:true,text:'Hours',color:'#64748B',font:{size:11}} },
      y1:{ beginAtZero:true, position:'right', grid:{display:false}, border:{display:false}, ticks:{stepSize:1}, title:{display:true,text:'Headcount',color:'#64748B',font:{size:11}} }
    }
  }
});

/* Doughnut: hours distribution */
new Chart(document.getElementById('hoursDistChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $chart_dept_labels ?>,
    datasets: [{
      data: <?= $chart_dept_hours ?>,
      backgroundColor: ['#3B82F6','#10B981','#F59E0B','#8B5CF6'],
      borderWidth: 2, borderColor: '#1E293B', hoverOffset: 5
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false, cutout: '70%',
    plugins: {
      legend: { position:'bottom', labels:{padding:16,boxWidth:8,usePointStyle:true,font:{size:12}} },
      tooltip: { ...entTip, callbacks:{ label: c=>` ${c.parsed} hrs` } }
    }
  }
});

/* DataTables */
$(function(){
  $('#heavyTable').DataTable({
    paging:false, searching:false, info:false,
    order:[[3,'desc']],
    columnDefs:[{type:'num',targets:3}]
  });
  $('#projSummaryTable').DataTable({
    dom:'<"dt-top-inner"lf>rt<"dt-bottom-inner"ip>',
    pageLength:10,
    order:[[3,'desc']],
    language:{search:'',searchPlaceholder:'Filter projects...',lengthMenu:'Show _MENU_',paginate:{previous:'Prev',next:'Next'}},
    initComplete:function(){
      $('.dt-top-inner').appendTo('#dt-controls3').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
      $('.dataTables_filter input').css({width:'200px'});
      $('.dt-bottom-inner').appendTo('#dt-bottom3').css({display:'flex',alignItems:'center',justifyContent:'space-between',width:'100%'});
    }
  });
});
</script>
</body>
</html>
