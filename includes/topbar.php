<?php
// includes/topbar.php
// Usage: include with $page_title set
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'High-level metrics and current operations.';
$user_name  = $_SESSION['user_name'] ?? 'Admin';
$user_initials = strtoupper(implode('', array_map(fn($w)=>$w[0], explode(' ', $user_name))));
?>
<header class="topbar fade-up">
  <div>
    <div class="topbar-title"><?= htmlspecialchars($page_title) ?></div>
    <div class="topbar-sub" id="date-display">Loading…</div>
  </div>
  <div class="topbar-actions">
    <button class="icon-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications">
      <div class="notif-pip"></div>
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <path d="M7.5 1.5a4.5 4.5 0 0 0-4.5 4.5c0 2.14-.46 3.38-1.09 4.2A.75.75 0 0 0 2.5 11.5h10a.75.75 0 0 0 .59-1.3c-.63-.82-1.09-2.06-1.09-4.2A4.5 4.5 0 0 0 7.5 1.5zm0 12a2 2 0 0 1-2-2h4a2 2 0 0 1-2 2z" fill="currentColor"/>
      </svg>
    </button>
    <div class="user-pill" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Account Settings">
      <div class="avatar"><?= $user_initials ?></div>
      <div>
        <div class="u-name"><?= htmlspecialchars($user_name) ?></div>
        <div class="u-role"><?= ucfirst($_SESSION['role'] ?? 'employee') ?></div>
      </div>
    </div>
  </div>
</header>
