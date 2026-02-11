<!-- PHP logic removed: server-side includes, session handling, and DB queries were stripped -->
<!-- All server-side variables were replaced with static placeholders or handled client-side -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | Calaca City Global College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #1a5fb4;
            --secondary: #2ec27e;
            --accent: #e5a50a;
            --dark: #1c1c1c;
            --light: #f6f5f4;
            --sidebar: #2d2d2d;
            --text: #333333;
            --card-bg: #ffffff;
            --border: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--sidebar);
            color: white;
            padding: 25px 0;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }

        .logo-area {
            display: flex;
            align-items: center;
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 25px;
        }

        .logo-area img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 15px;
            background: white;
            padding: 5px;
        }

        .logo-text h2 {
            font-size: 16px;
            line-height: 1.3;
            color: white;
        }

        .user-profile {
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }

        .avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }

        .user-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: white;
        }

        .user-info span {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }

        .status-badge {
            color: var(--secondary);
            font-weight: bold;
            font-size: 11px;
            margin-top: 3px;
            display: inline-block;
        }

        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            padding: 0 25px 10px;
            margin-bottom: 10px;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }

        .nav-links li {
            margin-bottom: 2px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.05);
            color: white;
            border-left-color: var(--primary);
        }

        .nav-links a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--secondary);
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .logout-link {
            color: #ff6b6b !important;
        }

        .logout-link:hover {
            background: rgba(255,107,107,0.1) !important;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border);
        }

        .header-title h1 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .header-title p {
            color: #666;
            font-size: 15px;
            max-width: 600px;
        }

        .time-widget {
            background: var(--primary);
            color: white;
            padding: 20px;
            border-radius: 12px;
            min-width: 200px;
            text-align: center;
        }

        .time-widget h2 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .time-widget p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), #3584e4);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .welcome-banner h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .welcome-banner p {
            font-size: 15px;
            opacity: 0.9;
            max-width: 600px;
        }

        .banner-icon {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 80px;
            opacity: 0.2;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
        }

        .stat-card h3 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
        }

        /* Controls Grid */
        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .control-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
            color: white;
        }

        .icon-box.orange {
            background: var(--accent);
        }

        .card-header h3 {
            font-size: 20px;
            color: var(--dark);
        }

        .card-desc {
            color: #666;
            margin-bottom: 25px;
            font-size: 15px;
            line-height: 1.6;
        }

        .action-list {
            list-style: none;
            margin-bottom: 25px;
        }

        .action-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .action-list li:last-child {
            border-bottom: none;
        }

        .action-list li i {
            margin-right: 12px;
            color: var(--primary);
            width: 20px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #0d52a1;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(26, 95, 180, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .controls-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                margin-bottom: 20px;
            }
            .main-content {
                margin-left: 0;
            }
            header {
                flex-direction: column;
                gap: 20px;
            }
            .time-widget {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .controls-grid {
                grid-template-columns: 1fr;
            }
            .control-card {
                padding: 20px;
            }
            .btn-group {
                flex-direction: column;
            }
        }

        /* Notifications Badge */
        .notification-badge {
            background: #ff4757;
            color: white;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 10px;
            font-weight: bold;
        }

        /* Announcements Section */
        .announcements-section {
            margin-top: 30px;
        }

        .announcements-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 20px;
        }

        .announcement-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .announcement-date {
            font-size: 12px;
            color: #666;
        }

        .announcement-content {
            margin-top: 8px;
            color: #555;
            font-size: 14px;
        }
    </style>
</head>
<body>
     <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <nav class="sidebar">
        <div class="logo-area">
            <img src="../assets/images/school-logo.png" alt="Logo">
            <div class="logo-text">
                <h2>Calaca City<br>Global College</h2>
            </div>
        </div>

        <div class="user-profile">
            <div class="avatar"><i class="fa-solid fa-user-shield"></i></div>
            <div class="user-info">
                <h4>Young Lord<br>System Owner</h4>
                <span>Full System Access</span><br>
                <span class="status-badge">● Online</span>
            </div>
        </div>

        <div class="nav-section-title">Main Navigation</div>
        <ul class="nav-links">
            <li><a href="#" class="active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Placeholder</a></li>
            <li><a href="#"><i class="fa-solid fa-chart-line"></i> Placeholder</a></li>
            <li><a href="#">
                <i class="fa-solid fa-bullhorn"></i> Placeholder
                
                    <span class="notification-badge">3</span>
                
            </a></li>
            <li><a href="#"><i class="fa-solid fa-book-open"></i> Placeholder</a></li>
        </ul>

        <div class="nav-section-title">System Controls</div>
        <ul class="nav-links">
            <li><a href="#"><i class="fa-solid fa-gears"></i> Placeholder</a></li>
            <li><a href="#"><i class="fa-solid fa-shield-halved"></i> Placeholder</a></li>
            <li><a href="#"><i class="fa-solid fa-clock-rotate-left"></i> Placeholder</a></li>
            <li><a href="#"><i class="fa-solid fa-headset"></i> Placeholder</a></li>
            <li><a href="#" class="logout-link"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        
        

    </main>

    <script>
        // Update live clock
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            let ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12;
            
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;
            document.getElementById('currentTime').textContent = timeString;
            
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const dateString = now.toLocaleDateString('en-US', options);
            document.getElementById('currentDateDisplay').textContent = dateString;
        }
        
        updateClock();
        setInterval(updateClock, 1000);

        // Mark notifications as read when announcements link is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const announcementsLink = document.querySelector('a[href="announcements.php"]');
            
            if (announcementsLink) {
                announcementsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show toast notification
                    showToast({
                        title: 'Opening Announcements',
                        message: 'Loading announcements panel...',
                        icon: 'fa-solid fa-bullhorn',
                        type: 'info',
                        duration: 2000
                    });
                    
                    // Send AJAX request to mark notifications as read
                    fetch('dashboard.php?mark_read=true')
                        .then(response => {
                            // Remove notification badge
                            const badge = this.querySelector('.notification-badge');
                            if (badge) {
                                badge.remove();
                            }
                            
                            // Redirect after toast
                            setTimeout(() => {
                                window.location.href = 'announcements.php';
                            }, 1500);
                        })
                        .catch(error => console.error('Error:', error));
                });
            }
        });

        // Add hover effects to cards
        document.querySelectorAll('.stat-card, .control-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
            });
        });

        // Toast Notification System
        function showToast(options) {
            const container = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${options.type || 'info'}`;
            toast.id = toastId;
            
            const icon = options.icon || 'fa-solid fa-info-circle';
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="${icon}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${options.title}</div>
                    <div class="toast-message">${options.message}</div>
                </div>
                <div class="toast-progress"></div>
            `;
            
            container.appendChild(toast);
            
            // Auto remove toast after duration
            const duration = options.duration || 3000;
            setTimeout(() => {
                removeToast(toastId);
            }, duration);
            
            // Also remove when clicked
            toast.addEventListener('click', () => {
                removeToast(toastId);
            });
            
            return toastId;
        }
        
        function removeToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('hiding');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        // Button click handlers for popover notifications
        document.addEventListener('DOMContentLoaded', function() {
            // User Management Button
            const manageUsersBtn = document.getElementById('manageUsersBtn');
            if (manageUsersBtn) {
                manageUsersBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    showToast({
                        title: 'Redirecting...',
                        message: '<div class="redirect-loading"><div class="spinner"></div> Opening User Management</div>',
                        icon: 'fa-solid fa-users-gear',
                        type: 'info',
                        duration: 2500
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'manage_users.php';
                    }, 1500);
                });
            }
            
            // Add User Button
            const addUserBtn = document.getElementById('addUserBtn');
            if (addUserBtn) {
                addUserBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    showToast({
                        title: 'Redirecting...',
                        message: '<div class="redirect-loading"><div class="spinner"></div> Opening Add User Form</div>',
                        icon: 'fa-solid fa-user-plus',
                        type: 'info',
                        duration: 2500
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'add_user.php';
                    }, 1500);
                });
            }
            
            // View Reports Button
            const viewReportsBtn = document.getElementById('viewReportsBtn');
            if (viewReportsBtn) {
                viewReportsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    showToast({
                        title: 'Loading Reports...',
                        message: '<div class="redirect-loading"><div class="spinner"></div> Generating analytics dashboard</div>',
                        icon: 'fa-solid fa-chart-line',
                        type: 'info',
                        duration: 3000
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'reports.php';
                    }, 2000);
                });
            }
            
            // Export Data Button
            const exportDataBtn = document.getElementById('exportDataBtn');
            if (exportDataBtn) {
                exportDataBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    showToast({
                        title: 'Preparing Export...',
                        message: '<div class="redirect-loading"><div class="spinner"></div> Compiling data for export</div>',
                        icon: 'fa-solid fa-download',
                        type: 'info',
                        duration: 3000
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'export_data.php';
                    }, 2000);
                });
            }
            
            // Navigation Links
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                if (link.getAttribute('href') && !link.getAttribute('href').includes('logout')) {
                    link.addEventListener('click', function(e) {
                        if (this.getAttribute('href') === 'owner_dashboard.php') return;
                        
                        e.preventDefault();
                        const pageName = this.textContent.trim();
                        
                        showToast({
                            title: 'Navigating...',
                            message: `<div class="redirect-loading"><div class="spinner"></div> Opening ${pageName}</div>`,
                            icon: this.querySelector('i').className,
                            type: 'info',
                            duration: 2500
                        });
                        
                        setTimeout(() => {
                            window.location.href = this.getAttribute('href');
                        },0);
                    });
                }
            });
        });

        // Show welcome toast on page load
        window.addEventListener('load', function() {
            setTimeout(() => {
                showToast({
                    title: 'Welcome Back, Owner!',
                    message: 'Dashboard loaded successfully. All systems operational.',
                    icon: 'fa-solid fa-user-shield',
                    type: 'success',
                    duration: 4000
                });
            }, 500);
        });

        // Test function to show different toast types (can be removed)
        function showTestToast(type) {
            const tests = {
                success: {
                    title: 'Success!',
                    message: 'Operation completed successfully.',
                    icon: 'fa-solid fa-check-circle',
                    type: 'success'
                },
                warning: {
                    title: 'Warning',
                    message: 'Please review the settings before proceeding.',
                    icon: 'fa-solid fa-exclamation-triangle',
                    type: 'warning'
                },
                error: {
                    title: 'Error',
                    message: 'Unable to process request. Please try again.',
                    icon: 'fa-solid fa-times-circle',
                    type: 'error'
                },
                info: {
                    title: 'Information',
                    message: 'System update scheduled for midnight.',
                    icon: 'fa-solid fa-info-circle',
                    type: 'info'
                }
            };
            
            showToast(tests[type] || tests.info);
        }
    </script>

</body>
</html>