<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

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
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        /* FULL CSS FIX */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%; max-width: 420px; padding: 20px;
        }
        .logo {
            text-align: center; font-family: 'Rajdhani', sans-serif;
            font-size: 42px; font-weight: 800; font-style: italic;
            letter-spacing: -2px; margin-bottom: 30px; color: #fff;
            text-shadow: 3px 3px 0 var(--cyan);
        }
        .logo span { color: var(--cyan); text-shadow: none; }
        
        .card {
            background: #000; border: 2px solid var(--border);
            border-radius: 0; padding: 40px 30px; position: relative;
            box-shadow: 10px 10px 0 rgba(0, 242, 255, 0.1);
        }
        .card::before {
            content: ""; position: absolute; top: -2px; left: -2px; width: 30px; height: 30px;
            border-top: 4px solid var(--cyan); border-left: 4px solid var(--cyan);
        }
        .card::after {
            content: ""; position: absolute; bottom: -2px; right: -2px; width: 30px; height: 30px;
            border-bottom: 4px solid var(--cyan); border-right: 4px solid var(--cyan);
        }
        .header h1 { font-size: 24px; margin: 0; color: #fff; text-align: center; }
        .header p { font-size: 14px; color: #94a3b8; text-align: center; margin: 10px 0 30px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 14px; margin-bottom: 8px; color: #cbd5e1; }
        
        input {
            width: 100%; padding: 12px 15px; background: #0a0a0c;
            border: 1px solid var(--border); border-radius: 0;
            color: var(--cyan); font-family: 'Space Grotesk', monospace;
            font-size: 15px; box-sizing: border-box; transition: 0.3s;
        }
        input:focus {
            outline: none; border-color: var(--cyan);
            box-shadow: 0 0 15px var(--cyan-glow);
        }

        .btn {
            width: 100%;
            padding: 16px; background: var(--cyan);
            border: none; border-radius: 0;
            color: #000; font-size: 16px; font-weight: 900;
            text-transform: uppercase; letter-spacing: 2px;
            cursor: pointer; margin-top: 10px; transition: 0.3s;
            clip-path: polygon(0 0, 100% 0, 100% 70%, 90% 100%, 0 100%);
        }
        .btn:hover { background: #fff; transform: scale(1.02); }

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