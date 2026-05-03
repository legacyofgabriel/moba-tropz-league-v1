<?php
include("../config/db.php");

if(isset($_POST['register'])){
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Security: Check kung existing na ang user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $check = $stmt->get_result();

    if($check->num_rows > 0){
        $error = "Username is already taken!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $success = "Registration successful! <a href='login.php' style='color:#38bdf8'>Login now</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body { 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            display: flex;
        }
        .auth-container { max-width: 420px; width: 100%; }
        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .form-group { margin-bottom: 20px; }
        .form-control {
            width: 100%;
            background: rgba(2, 6, 23, 0.5);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }
        .msg {
            display: flex; align-items: center; justify-content: center;
            padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; border: 1px solid;
        }
        .error-msg { color: #f87171; background: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.2); }
        .success-msg { color: #4ade80; background: rgba(74, 222, 128, 0.1); border-color: rgba(74, 222, 128, 0.2); }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="topbar-logo" style="text-align:center; margin-bottom:20px; font-size:36px;">MOBA <span>TROPZ</span></div>
    <div class="auth-card">
        <div class="section-head" style="flex-direction:column; align-items:center; text-align:center; margin-bottom:25px;">
            <div class="hero-label">Staff Registration</div>
            <h2 style="font-family:'Rajdhani'; color:#fff; text-transform:uppercase;">Admin Account</h2>
        </div>

        <?php if(isset($error)): ?>
            <div class="msg error-msg">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if(isset($success)): ?>
            <div class="msg success-msg">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-8.9"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="stat-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a handle" required>
            </div>
            
            <div class="form-group">
                <label class="stat-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Secure password" required>
            </div>

            <button type="submit" name="register" class="app-action primary" style="width:100%; height:48px; border:none; cursor:pointer;">Create Staff Account</button>
        </form>

        <div style="text-align:center; margin-top:25px; font-size:13px; color:var(--muted);">
            Already registered? <a href="login.php" style="color:var(--cyan); text-decoration:none; font-weight:700;">Login Now</a>
        </div>
    </div>
</div>
</body>
</html>