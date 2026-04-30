<?php
// includes/sidebar.php
// Usage: include with $active_page set to: 'dashboard','employees','departments','projects','reports'
$active_page = $active_page ?? 'dashboard';
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <div class="brand-icon">
        <svg viewBox="0 0 20 20"><path d="M10 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm-7 14c0-3.3 3.134-6 7-6s7 2.7 7 6v.5H3V16z"/></svg>
      </div>
      <div class="brand-text">
        <h1>EMS Portal</h1>
        <p>Enterprise Management</p>
      </div>
    </div>
  </div>
  <div class="sidebar-divider"></div>

  <p class="nav-section-label">Main</p>
  <ul class="sidebar-nav">
    <li>
      <a href="dashboard.php" class="nav-link-item <?= $active_page==='dashboard'?'active':'' ?>">
        <svg class="ni" viewBox="0 0 16 16"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
        Dashboard
      </a>
    </li>
    <li>
      <a href="employees.php" class="nav-link-item <?= $active_page==='employees'?'active':'' ?>">
        <svg class="ni" viewBox="0 0 16 16"><circle cx="8" cy="5.5" r="2.8"/><path d="M2 14.5c0-2.7 2.686-4.5 6-4.5s6 1.8 6 4.5" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
        Employees
      </a>
    </li>
    <li>
      <a href="departments.php" class="nav-link-item <?= $active_page==='departments'?'active':'' ?>">
        <svg class="ni" viewBox="0 0 16 16"><rect x="1" y="3" width="14" height="3" rx="1"/><rect x="1" y="8" width="14" height="2" rx="1"/><rect x="1" y="12" width="9" height="2" rx="1"/></svg>
        Departments
      </a>
    </li>
    <li>
      <a href="projects.php" class="nav-link-item <?= $active_page==='projects'?'active':'' ?>">
        <svg class="ni" viewBox="0 0 16 16"><path d="M2 2h12v2H2V2zm2 3h8l1 9H3L4 5z"/></svg>
        Projects
      </a>
    </li>
  </ul>

  <p class="nav-section-label" style="margin-top:1rem;">Analytics</p>
  <ul class="sidebar-nav">
    <li>
      <a href="reports.php" class="nav-link-item <?= $active_page==='reports'?'active':'' ?>">
        <svg class="ni" viewBox="0 0 16 16"><path d="M1 12l4-5.5 3.5 3L12 3l3 9H1z"/></svg>
        Reports
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="logout.php" class="nav-link-item" style="color:var(--text-soft);">
      <svg class="ni" viewBox="0 0 16 16"><path d="M6 2h5a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6v-2h5V4H6V2zM4 5l-3 3 3 3V9h5V7H4V5z"/></svg>
      Logout
    </a>
  </div>
</aside>
