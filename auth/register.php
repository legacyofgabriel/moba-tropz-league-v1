<?php
include("../config/db.php");

if(isset($_POST['register'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Security: Check kung existing na ang user
    $check = $conn->query("SELECT id FROM users WHERE username='$username'");
    if($check->num_rows > 0){
        $error = "Username is already taken!";
    } else {
        $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'admin')");
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
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #020617;
            background-image: 
                linear-gradient(rgba(2, 6, 23, 0.7), rgba(2, 6, 23, 0.8)),
                url('https://images6.alphacoders.com/105/1059438.jpg'); /* Land of Dawn Scenery */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        /* MLBB Overlay Effect */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                repeating-linear-gradient(0deg, rgba(0,0,0,0.15) 0px, transparent 1px, transparent 2px),
                repeating-linear-gradient(90deg, rgba(56, 189, 248, 0.02) 0px, transparent 1px, transparent 40px);
            background-size: 100% 3px, 40px 100%;
            pointer-events: none;
            z-index: 0;
        }
        .register-container {
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
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
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
            box-sizing: border-box; /* Pinaka-importante para sa alignment */
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

        .msg {
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid;
        }

        .footer-link { text-align: center; margin-top: 25px; font-size: 14px; color: #94a3b8; }
        .footer-link a { color: #38bdf8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="register-container">
    <div class="logo">MOBA <span>TROPZ</span></div>
    
    <div class="card">
        <div class="header">
            <h1>Create Account</h1>
            <p>Join the league management system</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="msg" style="color: #f87171; background: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.2);"><?= $error ?></div>
        <?php endif; ?>

        <?php if(isset($success)): ?>
            <div class="msg" style="color: #4ade80; background: rgba(74, 222, 128, 0.1); border-color: rgba(74, 222, 128, 0.2);"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Choose Username</label>
                <input type="text" name="username" placeholder="Pick a username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create password" required>
            </div>

            <button type="submit" name="register" class="btn">Register Admin</button>
        </form>

        <div class="footer-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

</body>
</html>