<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(isset($_POST['login'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    $user = $result->fetch_assoc();

    if($user && password_verify($password, $user['password'])){
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $conn->query("INSERT INTO login_logs (user_id, ip_address) VALUES ({$user['id']}, '$ip')");
        header("Location: ../dashboard/maindashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MOBA TROPZ</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        /* FULL CSS FIX */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top, #0f172a, #020617);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .logo {
            text-align: center;
            font-family: 'Rajdhani', sans-serif;
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 30px;
            color: #f0b429;
        }
        .logo span { color: #38bdf8; }
        
        .card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .header h1 { font-size: 24px; margin: 0; color: #fff; text-align: center; }
        .header p { font-size: 14px; color: #94a3b8; text-align: center; margin: 10px 0 30px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 14px; margin-bottom: 8px; color: #cbd5e1; }
        
        input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(2, 6, 23, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box; /* IMPORTANTE: Para hindi lumampas ang input */
            transition: 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .error-msg {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.2);
            color: #f87171;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
        }
        .footer-link { text-align: center; margin-top: 25px; font-size: 14px; color: #94a3b8; }
        .footer-link a { color: #38bdf8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">MOBA <span>TROPZ</span></div>
    
    <div class="card">
        <div class="header">
            <h1>Welcome Back</h1>
            <p>Login to manage your tournament</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login" class="btn">Login to Account</button>
        </form>

        <div class="footer-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

</body>
</html>