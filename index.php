<?php
session_start();
include 'connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Raw password input

    // Query based on your table structure
    $sql = "SELECT id, username, password, role, status FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Check if account is active
        if($row['status'] == 'active'){
            // Verify Password (assuming DB has hashed passwords)
            // If you are using PLAIN TEXT in DB for testing, change to: if ($password == $row['password']) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                
                // ✅ Redirect already logged-in users to their dashboard
                if (isset($_SESSION["role"])) {
                    switch ($_SESSION["role"]) {
                        case "student":
                            header("Location: student/student_dashboard.php");
                            exit();
                        case "faculty":
                            header("Location: faculty/faculty_dashboard.php");
                            exit();
                        case "registrar":
                            header("Location: registrar/registrar_dashboard.php");
                            exit();
                        case "admin":
                            header("Location: admin/admin_dashboard.php");
                            exit();
                        case "owner":
                            header("Location: owner/owner_dashboard.php"); // Added owner redirect
                            exit();
                    }
                }
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Your Account is inactive or suspended. Please contact Administrator.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCGC Learning Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="assets/images/school_logo.png" type="image/x-icon">
    
    <style>
        :root {
            --primary-blue: #0066ff;
            --primary-dark: #001f3f;
            --accent-orange: #ff9900;
            --text-white: #ffffff;
            --card-bg: #ffffff;
            --glass-bg: rgba(0, 0, 0, 0.75);
        }
        html{
            scroll-behavior: smooth;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f4;
            overflow-x: hidden;
        }

        /* --- HERO LOGIN SECTION --- */
        .hero-section {
            min-height: 100vh;
            width: 100%;
            /* Replace 'campus_bg.jpg' with your background image path */
            background: linear-gradient(to right, rgba(12, 20, 39, 0.9), rgba(12, 20, 39, 0.7)), url('assets/images/school-bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        /* Left Side Content */
        .hero-content {
            flex: 1;
            color: var(--text-white);
            padding-right: 2rem;
            min-width: 300px;
        }

        .header-top {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: white; /* Placeholder for transparent logo */
            border-radius: 50%;
            margin-right: 15px;
            object-fit: contain;
        }

        .college-name h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .college-name span {
            font-size: 0.9rem;
            color: #ccc;
        }

        .badge-pill {
            display: inline-block;
            background-color: #00a8ff;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
        }

        .main-heading {
            font-family: 'Montserrat', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .main-heading span {
            color: var(--accent-orange);
        }

        .description {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 600px;
            border-left: 4px solid var(--accent-orange);
            padding-left: 15px;
        }

        .description em {
            font-style: italic;
            font-weight: 600;
            display: block;
            margin-top: 10px;
            font-size: 1.1rem;
        }

        .btn-group {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-outline {
            color: white;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Right Side Login Card */
        .login-wrapper {
            flex: 0 0 400px;
            max-width: 100%;
        }

        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        .login-logo {
            width: 80px;
            margin-bottom: 1rem;
        }

        .login-card h2 {
            color: #333;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 0.5rem;
        }

        .login-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.2rem;
            text-align: left;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #eee;
            background: #f4f6f9;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-blue);
            background: white;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            left: auto !important; 
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #0066ff;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #0052cc;
        }

        .error-msg {
            color: red;
            font-size: 0.8rem;
            margin-bottom: 10px;
            display: block;
        }

        .date-display {
            position: absolute;
            top: 20px;
            right: 30px;
            text-align: right;
            color: white;
            font-size: 0.8rem;
        }

        /* --- ACADEMIC PROGRAMS SECTION --- */
        .programs-section {
            background-color: #0a101f; /* Dark background matching image 2 */
            padding: 4rem 2rem;
            color: white;
            text-align: center;
        }

        .section-title h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .section-title p {
            color: #ccc;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title p::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, #00c6ff, #0072ff);
            margin: 10px auto 0;
        }

        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .program-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            height: 300px;
            transition: transform 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            cursor: pointer;
        }

        .program-card:hover {
            transform: translateY(-5px);
        }

        .card-bg {
            height: 100%;
            width: 100%;
            object-fit: cover;
            /* Placeholder styling */
            background-color: #ddd; 
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .program-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.5rem;
        }

        .tag {
            align-self: flex-start;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: white;
        }

        .tag-red { background: #d32f2f; }
        .tag-yellow { background: #fbc02d; color: black; }
        .tag-blue { background: #1976d2; }
        .tag-green { background: #388e3c; }

        .program-info {
            text-align: left;
            color: white;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
        }

        .program-info h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .program-info small {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 992px) {
            .hero-section {
                flex-direction: column;
                padding-top: 80px;
            }
            .hero-content {
                text-align: center;
                margin-bottom: 3rem;
                padding-right: 0;
            }
            .main-heading {
                font-size: 2.5rem;
            }
            .description {
                margin: 0 auto 2rem;
                border-left: none;
                border-top: 4px solid var(--accent-orange);
                padding-top: 15px;
                padding-left: 0;
            }
            .btn-group {
                justify-content: center;
            }
            .date-display {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <div class="date-display">
            <h2 id="clock-time" style="font-weight: 700; font-size: 1.2rem;">00:00 AM</h2>
            <p id="clock-date" style="font-size: 0.9rem; opacity: 0.8;">Monday, December 8, 2025</p>
        </div>

        <div class="container">
            <div class="hero-content">
                <div class="header-top">
                    <img src="assets/images/school_logo.png" alt="CCGC Logo" class="logo">
                    <div class="college-name">
                        <h3>Calaca City<br>Global College</h3>
                        <span>Learning Management System</span>
                    </div>
                </div>

                <span class="badge-pill">LMS Portal</span>

                <h1 class="main-heading">
                    Experience the <br>
                    <span>Power of <br>Education</span>
                </h1>

                <div class="description">
                    CCGC is a locally-funded public local college under the supervision of the City Government of Calaca.
                    <br>
                    <em>"Learn Together! Live Great!"</em>
                </div>

                <div class="btn-group">
                    <a href="#programs" class="btn btn-outline">Academic Programs</a>
                    <a href="#" class="btn btn-outline">About Us</a>
                </div>
            </div>

            <div class="login-wrapper">
                <div class="login-card">
                    <img src="assets/images/school_logo.png" alt="Logo" class="login-logo">
                    <h2>Portal Login</h2>
                    <p>Sign in to your account</p>

                    <?php if($error): ?>
                        <span class="error-msg"><?php echo $error; ?></span>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="input-group">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="username" placeholder="admin123" required>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="passwordField" placeholder="........" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                        </div>

                        <button type="submit" class="btn-login">SIGN IN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="programs" class="programs-section">
        <div class="section-title">
            <h2>Academic Programs</h2>
            <p>Shaping Future Leaders</p>
        </div>

        <div class="programs-grid">
            <div class="program-card">
                <div class="card-bg">
                    <img src="assets/images/cs_logo.png" alt="CS" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="program-overlay">
                    <span class="tag tag-red">Bachelor of Science in Computer Science</span>
                    <div class="program-info">
                        <h4>BS. Computer Science</h4>
                        <small>Developing future innovators in technology and software development.</small>
                    </div>
                </div>
            </div>

            <div class="program-card">
                <div class="card-bg">
                     <img src="assets/images/cs_logo.png" alt="Entrep" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="program-overlay">
                    <span class="tag tag-yellow">Bachelor of Science in Entrepreneurship</span>
                    <div class="program-info">
                        <h4>BS. Entrepreneurship</h4>
                        <small>Empowering students to become innovative business leaders and entrepreneurs.</small>
                    </div>
                </div>
            </div>

            <div class="program-card">
                <div class="card-bg">
                     <img src="assets/images/cs_logo.png" alt="PolSci" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="program-overlay">
                    <span class="tag tag-blue">Bachelor of Arts in Political Science</span>
                    <div class="program-info">
                        <h4>BA. Political Science</h4>
                        <small>Preparing students for careers in government, law, and public policy.</small>
                    </div>
                </div>
            </div>

            <div class="program-card">
                <div class="card-bg">
                     <img src="assets/images/cs_logo.png" alt="PubAd" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="program-overlay">
                    <span class="tag tag-green">Bachelor of Public Administration</span>
                    <div class="program-info">
                        <h4>B. Public Administration</h4>
                        <small>Developing servant-leaders for efficient public service.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        function togglePassword() {
            var x = document.getElementById("passwordField");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }

        // Live Clock (Optional but matches the screenshot 02:03 AM)
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            minutes = minutes < 10 ? '0' + minutes : minutes;
            const strTime = hours + ':' + minutes + ' ' + ampm;
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const strDate = now.toLocaleDateString('en-US', options);

            document.getElementById('clock-time').innerText = strTime;
            document.getElementById('clock-date').innerText = strDate;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>