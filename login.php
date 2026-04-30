<?php
// login.php
session_start();
require_once 'config.php';

// Already logged in → go to dashboard
if (isset($_SESSION['user_id'])) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT l.Login_ID, l.Emp_ID, l.Username, l.Password, l.Role,
                   e.Name AS EmployeeName
            FROM login l
            JOIN employee e ON l.Emp_ID = e.Emp_ID
            WHERE l.Username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // NOTE: passwords in your DB are plain-text ('1234').
        // For production you should use password_hash() & password_verify().
        if ($user && $user['Password'] === $password) {
            $_SESSION['user_id']   = $user['Login_ID'];
            $_SESSION['emp_id']    = $user['Emp_ID'];
            $_SESSION['username']  = $user['Username'];
            $_SESSION['user_name'] = $user['EmployeeName'];
            $_SESSION['role']      = $user['Role'];
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EMS — Sign In</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--bg-page:#0F172A;--bg-card:#1E293B;--text-base:#F8FAFC;--text-muted:#94A3B8;--text-soft:#64748B;--border:#334155;--accent-blue:#3B82F6;--radius-sm:6px;--radius-card:10px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg-page);color:var(--text-base);min-height:100vh;display:flex;align-items:center;justify-content:center;-webkit-font-smoothing:antialiased;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.login-wrap{width:100%;max-width:400px;padding:1.5rem;animation:fadeUp .45s ease-out both;}
.login-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:2rem 2rem 2rem;}
.login-logo{display:flex;align-items:center;gap:12px;margin-bottom:2rem;}
.brand-icon{width:36px;height:36px;background:var(--accent-blue);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:#fff;}
.brand-icon svg{fill:currentColor;width:20px;height:20px;}
.brand-text h1{font-size:16px;font-weight:600;color:var(--text-base);margin:0;}
.brand-text p{font-size:12px;color:var(--text-muted);margin:0;}
.login-heading h2{font-size:20px;font-weight:600;color:var(--text-base);margin:0 0 6px;}
.login-heading p{font-size:13px;color:var(--text-muted);margin:0 0 1.75rem;}
.form-label{color:var(--text-muted);font-size:13px;font-weight:500;display:block;margin-bottom:6px;}
.form-control{width:100%;background:#0F172A;border:1px solid var(--border);color:var(--text-base);border-radius:var(--radius-sm);font-size:14px;padding:9px 12px;outline:none;transition:border-color .15s;font-family:'Inter',sans-serif;}
.form-control:focus{border-color:var(--accent-blue);box-shadow:0 0 0 3px rgba(59,130,246,.1);}
.form-control::placeholder{color:var(--text-soft);}
.input-wrap{position:relative;margin-bottom:1rem;}
.toggle-pw{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-soft);cursor:pointer;padding:2px 4px;display:flex;align-items:center;transition:color .15s;}
.toggle-pw:hover{color:var(--text-muted);}
.btn-login{width:100%;background:var(--accent-blue);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px;font-size:14px;font-weight:500;cursor:pointer;transition:background .15s;margin-top:.5rem;font-family:'Inter',sans-serif;}
.btn-login:hover{background:#2563EB;}
.error-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#FCA5A5;border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;}
.demo-hint{margin-top:1.25rem;padding:12px;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:var(--radius-sm);font-size:12px;color:var(--text-soft);}
.demo-hint span{color:var(--accent-blue);font-weight:500;}
.divider{height:1px;background:var(--border);margin:1.5rem 0;}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="brand-icon">
        <svg viewBox="0 0 20 20"><path d="M10 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm-7 14c0-3.3 3.134-6 7-6s7 2.7 7 6v.5H3V16z"/></svg>
      </div>
      <div class="brand-text">
        <h1>EMS Portal</h1>
        <p>Enterprise Management System</p>
      </div>
    </div>

    <div class="login-heading">
      <h2>Welcome back</h2>
      <p>Sign in to your account to continue.</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#EF4444" stroke-width="1.5"/><path d="M7 4v3M7 9.5v.5" stroke="#EF4444" stroke-width="1.5" stroke-linecap="round"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <div>
        <label class="form-label">Username</label>
        <div class="input-wrap">
          <input type="text" name="username" class="form-control"
                 placeholder="Enter your username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autocomplete="username" required>
        </div>
      </div>
      <div>
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <input type="password" name="password" id="pw-field" class="form-control"
                 placeholder="Enter your password"
                 autocomplete="current-password" required>
          <button type="button" class="toggle-pw" onclick="togglePw()" title="Toggle visibility">
            <svg id="eye-icon" width="15" height="15" viewBox="0 0 15 15" fill="none">
              <path d="M1 7.5C2.5 4.5 5 2.5 7.5 2.5s5 2 6.5 5c-1.5 3-4 5-6.5 5S2.5 10.5 1 7.5z" stroke="currentColor" stroke-width="1.2"/>
              <circle cx="7.5" cy="7.5" r="1.8" stroke="currentColor" stroke-width="1.2"/>
            </svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="demo-hint">
      <strong style="color:var(--text-muted)">Demo credentials —</strong>
      Admin: <span>admin</span> / <span>1234</span> &nbsp;|&nbsp;
      Employee: <span>ahmed</span> / <span>1234</span>
    </div>
  </div>
</div>
<script>
function togglePw() {
  const f = document.getElementById('pw-field');
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
