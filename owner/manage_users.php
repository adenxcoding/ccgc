<?php
require_once "../connect.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Check if user exists in database
if ($role === 'owner') {
    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'active' LIMIT 1");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows === 0) {
        session_destroy();
        header("Location: ../login.php?error=invalid_user");
        exit();
    }
    $check->close();
}

// User is valid, continue...
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_username = $_POST['username'];
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $new_role = $_POST['role'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $new_username, $new_password, $new_role, $new_status);
    
    if ($stmt->execute()) {
        $success_message = "User added successfully!";
    } else {
        $error_message = "Error adding user: " . $conn->error;
    }
    $stmt->close();
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $edit_id = $_POST['user_id'];
    $edit_username = $_POST['username'];
    $edit_role = $_POST['role'];
    $edit_status = $_POST['status'];
    
    // If password is provided, update it
    if (!empty($_POST['password'])) {
        $edit_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $edit_username, $edit_password, $edit_role, $edit_status, $edit_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $edit_username, $edit_role, $edit_status, $edit_id);
    }
    
    if ($stmt->execute()) {
        $success_message = "User updated successfully!";
    } else {
        $error_message = "Error updating user: " . $conn->error;
    }
    $stmt->close();
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Don't allow deleting self
    if ($delete_id == $user_id) {
        $error_message = "You cannot delete your own account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Batch Delete - FIXED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_delete'])) {
    if (isset($_POST['selected_users']) && is_array($_POST['selected_users']) && !empty($_POST['selected_users'])) {
        $selected_users = $_POST['selected_users'];
        
        // Filter out empty values and convert to integers
        $selected_users = array_filter($selected_users);
        $selected_users = array_map('intval', $selected_users);
        
        // Check if user is trying to delete themselves
        if (in_array($user_id, $selected_users)) {
            $error_message = "You cannot delete your own account!";
        } else {
            // Remove current user from selection if present
            $selected_users = array_diff($selected_users, [$user_id]);
            
            if (!empty($selected_users)) {
                // Create placeholders for the prepared statement
                $placeholders = implode(',', array_fill(0, count($selected_users), '?'));
                $types = str_repeat('i', count($selected_users));
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $stmt->bind_param($types, ...$selected_users);
                
                if ($stmt->execute()) {
                    $deleted_count = $stmt->affected_rows;
                    $success_message = "$deleted_count user(s) deleted successfully!";
                } else {
                    $error_message = "Error deleting users: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error_message = "No valid users selected for deletion!";
            }
        }
    } else {
        $error_message = "Please select users to delete!";
    }
    
    // Redirect to clear POST data and show message
    header("Location: manage_users.php?success=" . urlencode($success_message ?? '') . "&error=" . urlencode($error_message ?? ''));
    exit();
}

// Get success/error messages from URL parameters
if (isset($_GET['success']) && !empty($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Get filter parameters
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Build query with filters
$query = "SELECT * FROM users WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND username LIKE ?";
    $count_query .= " AND username LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($filter_role) && $filter_role !== 'all') {
    $query .= " AND role = ?";
    $count_query .= " AND role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

if (!empty($filter_status) && $filter_status !== 'all') {
    $query .= " AND status = ?";
    $count_query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Add sorting
$valid_sort_columns = ['id', 'username', 'role', 'status', 'created_at'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'id';
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
$query .= " ORDER BY $sort_by $sort_order";

// PAGINATION - Get total count
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Pagination variables
$records_per_page = 30;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Add limit and offset to query
$query .= " LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total counts for statistics
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$active_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'")->fetch_assoc()['total'];
$inactive_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'inactive'")->fetch_assoc()['total'];

// Get role distribution
$role_counts = [];
$role_query = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $role_query->fetch_assoc()) {
    $role_counts[$row['role']] = $row['count'];
}

// Get current date and time for display
date_default_timezone_set('Asia/Manila');
$currentTime = date("h:i:s A");
$currentDate = date("l, F j, Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Calaca City Global College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styles for user management */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-title {
            flex: 1;
        }
        
        .page-title h1 {
            color: #1a5fb4;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .page-title p {
            color: #666;
            font-size: 15px;
        }
        
        /* Stats Cards */
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1a5fb4;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Filters and Search */
        .filters-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .filters-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .filter-select, .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus, .search-input:focus {
            outline: none;
            border-color: #1a5fb4;
            background: white;
            box-shadow: 0 0 0 3px rgba(26, 95, 180, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-filter {
            padding: 10px 20px;
            background: #1a5fb4;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-filter:hover {
            background: #0d52a1;
            transform: translateY(-2px);
        }
        
        .btn-reset {
            padding: 10px 20px;
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-reset:hover {
            background: #e9ecef;
            color: #333;
        }
        
        /* Batch Actions */
        .batch-actions {
            background: #f8f9fa;
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .select-all input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .selected-count {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .btn-batch-delete {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .btn-batch-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-batch-delete:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-batch-delete:disabled:hover {
            transform: none;
        }
        
        /* Users Table */
        .users-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-add {
            padding: 10px 20px;
            background: #2ec27e;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-add:hover {
            background: #26a269;
            transform: translateY(-2px);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .users-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        
        .users-table th:hover {
            background: #e9ecef;
        }
        
        .users-table th i {
            margin-left: 5px;
            font-size: 12px;
            opacity: 0.5;
        }
        
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .users-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .users-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Checkbox column */
        .checkbox-cell {
            width: 50px;
            text-align: center;
        }
        
        .user-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        /* Status badges */
        .status-badge {
            color: var(--secondary);
            font-weight: bold;
            font-size: 11px;
            margin-top: 3px;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Role badges */
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .role-owner { background: #e3f2fd; color: #1565c0; }
        .role-admin { background: #e8f5e9; color: #2e7d32; }
        .role-faculty { background: #fff3e0; color: #ef6c00; }
        .role-student { background: #f3e5f5; color: #7b1fa2; }
        .role-registrar { background: #e0f2f1; color: #00695c; }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        /* Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #1a5fb4, #2ec27e);
            color: white;
            border-radius: 16px 16px 0 0;
        }
        
        .modal-title {
            font-size: 1.2em;
            font-weight: 700;
            margin: 0;
        }
        
        .close-modal {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1a5fb4;
            background: white;
            box-shadow: 0 0 0 3px rgba(26, 95, 180, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        
        .btn-submit {
            flex: 1;
            padding: 12px;
            background: #1a5fb4;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #0d52a1;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #e9ecef;
            color: #333;
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .pagination-info {
            font-size: 14px;
            color: #666;
        }
        
        .pagination {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            color: #333;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
        }
        
        .pagination a:hover {
            background: #1a5fb4;
            color: white;
            border-color: #1a5fb4;
        }
        
        .pagination a.active {
            background: #1a5fb4;
            color: white;
            border-color: #1a5fb4;
        }
        
        .pagination a.disabled {
            background: #f8f9fa;
            color: #999;
            cursor: not-allowed;
            border-color: #eee;
        }
        
        .pagination a.disabled:hover {
            background: #f8f9fa;
            color: #999;
            border-color: #eee;
        }
        
        /* Toast/Popover Notification Styles */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }

        .toast {
            background: linear-gradient(135deg, #1a5fb4, #2a7de1);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(100%);
            opacity: 0;
            animation: slideIn 0.3s ease forwards;
            position: relative;
            overflow: hidden;
        }

        .toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #2ec27e;
        }

        .toast.toast-success {
            background: linear-gradient(135deg, #2ec27e, #3ad68f);
        }

        .toast.toast-success::before {
            background: #1a9367;
        }

        .toast.toast-warning {
            background: linear-gradient(135deg, #e5a50a, #f0b429);
        }

        .toast.toast-warning::before {
            background: #c97c0a;
        }

        .toast.toast-error {
            background: linear-gradient(135deg, #ff4757, #ff6b81);
        }

        .toast.toast-error::before {
            background: #c44569;
        }

        .toast-icon {
            font-size: 20px;
            min-width: 24px;
            text-align: center;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .toast-message {
            font-size: 13px;
            opacity: 0.9;
            line-height: 1.4;
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
            width: 100%;
            transform-origin: left;
            animation: progressBar 3s linear forwards;
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        @keyframes progressBar {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }

        .toast.hiding {
            animation: slideOut 0.3s ease forwards;
        }

        /* Add loading animation for redirect messages */
        .redirect-loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .redirect-loading .spinner {
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Button click effects */
        .btn {
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }

        /* Operation success/failure indicators */
        .operation-success {
            color: #2ec27e;
        }

        .operation-error {
            color: #ff4757;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filters-row {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .users-table th, .users-table td {
                padding: 10px;
                font-size: 14px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-action {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
            
            .modal-content {
                width: 95%;
                margin: 20% auto;
            }
            
            .toast-container {
                max-width: 280px;
                right: 10px;
                bottom: 10px;
            }
            
            .pagination-container {
                flex-direction: column;
                text-align: center;
            }
            
            .batch-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
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
            <li><a href="owner_dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="manage_users.php" class="active"><i class="fa-solid fa-users"></i> User Management</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Analytics & Reports</a></li>
            <li><a href="announcements.php">
                <i class="fa-solid fa-bullhorn"></i> Announcements
                <span class="notification-badge">3</span>
            </a></li>
            <li><a href="courses.php"><i class="fa-solid fa-book-open"></i> Course Management</a></li>
        </ul>

        <div class="nav-section-title">System Controls</div>
        <ul class="nav-links">
            <li><a href="settings.php"><i class="fa-solid fa-gears"></i> System Settings</a></li>
            <li><a href="security.php"><i class="fa-solid fa-shield-halved"></i> Security</a></li>
            <li><a href="logs.php"><i class="fa-solid fa-clock-rotate-left"></i> Activity Log</a></li>
            <li><a href="support.php"><i class="fa-solid fa-headset"></i> Support</a></li>
            <li><a href="../index.php" class="logout-link"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        
        <header>
            <div class="header-title">
                <h1>User Management</h1>
                <p>Manage all user accounts, permissions, and access controls for the LMS system.</p>
            </div>
            <div class="time-widget">
                <h2 id="currentTime"><?= $currentTime ?></h2>
                <p id="currentDateDisplay"><?= $currentDate ?></p>
            </div>
        </header>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?= $success_message ?>
            </div>
            <script>
                // Show success toast when page loads with success message
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        showToast({
                            title: 'Success!',
                            message: '<?= addslashes($success_message) ?>',
                            icon: 'fa-solid fa-check-circle',
                            type: 'success',
                            duration: 4000
                        });
                    }, 500);
                });
            </script>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-exclamation-circle"></i> <?= $error_message ?>
            </div>
            <script>
                // Show error toast when page loads with error message
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        showToast({
                            title: 'Error!',
                            message: '<?= addslashes($error_message) ?>',
                            icon: 'fa-solid fa-exclamation-circle',
                            type: 'error',
                            duration: 4000
                        });
                    }, 500);
                });
            </script>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="user-stats">
            <div class="stat-box">
                <div class="stat-number"><?= $total_users ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $active_users ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $inactive_users ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= count($role_counts) ?></div>
                <div class="stat-label">User Roles</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters-container">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="search"><i class="fa-solid fa-search"></i> Search</label>
                        <input type="text" id="search" name="search" class="search-input" 
                               placeholder="Search by username..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="role"><i class="fa-solid fa-user-tag"></i> Role</label>
                        <select id="role" name="role" class="filter-select">
                            <option value="all">All Roles</option>
                            <option value="owner" <?= $filter_role === 'owner' ? 'selected' : '' ?>>Owner</option>
                            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="faculty" <?= $filter_role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                            <option value="student" <?= $filter_role === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="registrar" <?= $filter_role === 'registrar' ? 'selected' : '' ?>>Registrar</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status"><i class="fa-solid fa-circle-check"></i> Status</label>
                        <select id="status" name="status" class="filter-select">
                            <option value="all">All Status</option>
                            <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter" id="applyFiltersBtn">
                            <i class="fa-solid fa-filter"></i> Apply Filters
                        </button>
                        <a href="manage_users.php" class="btn-reset" id="resetFiltersBtn">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <div class="table-header">
                <h3>User Accounts (<?= $total_records ?> found)</h3>
                <div class="table-actions">
                    <button class="btn-add" onclick="openAddModal()" id="addUserBtn">
                        <i class="fa-solid fa-user-plus"></i> Add New User
                    </button>
                </div>
            </div>
            
            <!-- Batch Actions -->
            <div class="batch-actions" id="batchActions" style="display: none;">
                <div class="select-all">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                <span class="selected-count" id="selectedCount">0 users selected</span>
                <button type="button" class="btn-batch-delete" id="batchDeleteBtn" onclick="batchDeleteAction()" disabled>
                    <i class="fa-solid fa-trash"></i> Delete Selected
                </button>
            </div>
            
            <div class="table-responsive">
                <form method="POST" action="" id="batchForm">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="headerCheckbox" onchange="toggleHeaderCheckbox()">
                                </th>
                                <th onclick="sortTable('id')">
                                    ID 
                                    <?php if ($sort_by === 'id'): ?>
                                        <i class="fa-solid fa-sort-<?= strtolower($sort_order) ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-sort"></i>
                                    <?php endif; ?>
                                </th>
                                <th onclick="sortTable('username')">
                                    Username 
                                    <?php if ($sort_by === 'username'): ?>
                                        <i class="fa-solid fa-sort-<?= strtolower($sort_order) ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-sort"></i>
                                    <?php endif; ?>
                                </th>
                                <th onclick="sortTable('role')">
                                    Role 
                                    <?php if ($sort_by === 'role'): ?>
                                        <i class="fa-solid fa-sort-<?= strtolower($sort_order) ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-sort"></i>
                                    <?php endif; ?>
                                </th>
                                <th onclick="sortTable('status')">
                                    Status 
                                    <?php if ($sort_by === 'status'): ?>
                                        <i class="fa-solid fa-sort-<?= strtolower($sort_order) ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-sort"></i>
                                    <?php endif; ?>
                                </th>
                                <th onclick="sortTable('created_at')">
                                    Created At 
                                    <?php if ($sort_by === 'created_at'): ?>
                                        <i class="fa-solid fa-sort-<?= strtolower($sort_order) ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-sort"></i>
                                    <?php endif; ?>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($user = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <input type="checkbox" class="user-checkbox" name="selected_users[]" value="<?= $user['id'] ?>" 
                                                   onchange="updateSelection()" <?= $user['id'] == $user_id ? 'disabled' : '' ?>>
                                        </td>
                                        <td><?= $user['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                        <td>
                                            <span class="role-badge role-<?= $user['role'] ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-<?= $user['status'] ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn-action btn-edit" 
                                                        onclick="editUserAction(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= $user['role'] ?>', '<?= $user['status'] ?>')" 
                                                        title="Edit User">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button type="button" class="btn-action btn-delete" 
                                                        onclick="deleteUserAction(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')" 
                                                        title="Delete User" <?= $user['id'] == $user_id ? 'disabled' : '' ?>>
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                        <i class="fa-solid fa-users-slash" style="font-size: 3em; margin-bottom: 15px; opacity: 0.5;"></i>
                                        <p>No users found matching your criteria.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Hidden submit button for batch delete -->
                    <input type="hidden" name="batch_delete" value="1">
                </form>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?= min($records_per_page, $result->num_rows) ?> of <?= $total_records ?> users
                (Page <?= $current_page ?> of <?= $total_pages ?>)
            </div>
            
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="<?= generatePageLink(1) ?>" title="First Page"><i class="fa-solid fa-angles-left"></i></a>
                    <a href="<?= generatePageLink($current_page - 1) ?>" title="Previous Page"><i class="fa-solid fa-chevron-left"></i></a>
                <?php else: ?>
                    <a class="disabled"><i class="fa-solid fa-angles-left"></i></a>
                    <a class="disabled"><i class="fa-solid fa-chevron-left"></i></a>
                <?php endif; ?>
                
                <?php
                // Show page numbers
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="' . generatePageLink(1) . '">1</a>';
                    if ($start_page > 2) {
                        echo '<a class="disabled">...</a>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $current_page) {
                        echo '<a class="active">' . $i . '</a>';
                    } else {
                        echo '<a href="' . generatePageLink($i) . '">' . $i . '</a>';
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<a class="disabled">...</a>';
                    }
                    echo '<a href="' . generatePageLink($total_pages) . '">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?= generatePageLink($current_page + 1) ?>" title="Next Page"><i class="fa-solid fa-chevron-right"></i></a>
                    <a href="<?= generatePageLink($total_pages) ?>" title="Last Page"><i class="fa-solid fa-angles-right"></i></a>
                <?php else: ?>
                    <a class="disabled"><i class="fa-solid fa-chevron-right"></i></a>
                    <a class="disabled"><i class="fa-solid fa-angles-right"></i></a>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-user-plus"></i> Add New User</h3>
                <button class="close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="add_username">Username</label>
                        <input type="text" id="add_username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_password">Password</label>
                        <input type="password" id="add_password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_role">Role</label>
                        <select id="add_role" name="role" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="faculty">Faculty</option>
                            <option value="student">Student</option>
                            <option value="registrar">Registrar</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_status">Status</label>
                        <select id="add_status" name="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                        <button type="submit" name="add_user" class="btn-submit">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-user-edit"></i> Edit User</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_password">Password (Leave blank to keep current)</label>
                        <input type="password" id="edit_password" name="password" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" class="form-control" required>
                            <option value="owner">Owner</option>
                            <option value="admin">Admin</option>
                            <option value="faculty">Faculty</option>
                            <option value="student">Student</option>
                            <option value="registrar">Registrar</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" name="edit_user" class="btn-submit">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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

        // Batch selection functions
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:not(:disabled):checked');
            const batchActions = document.getElementById('batchActions');
            const selectedCount = document.getElementById('selectedCount');
            const batchDeleteBtn = document.getElementById('batchDeleteBtn');
            const headerCheckbox = document.getElementById('headerCheckbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            // Update count
            selectedCount.textContent = selectedCheckboxes.length + ' users selected';
            
            // Show/hide batch actions
            if (selectedCheckboxes.length > 0) {
                batchActions.style.display = 'flex';
                batchDeleteBtn.disabled = false;
            } else {
                batchActions.style.display = 'none';
                batchDeleteBtn.disabled = true;
            }
            
            // Update header checkbox state
            if (selectedCheckboxes.length === checkboxes.length) {
                headerCheckbox.checked = true;
                headerCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else if (selectedCheckboxes.length > 0) {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            } else {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            }
        }
        
        function toggleHeaderCheckbox() {
            const headerCheckbox = document.getElementById('headerCheckbox');
            const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = headerCheckbox.checked;
            });
            
            selectAllCheckbox.checked = headerCheckbox.checked;
            
            updateSelection();
        }
        
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            const headerCheckbox = document.getElementById('headerCheckbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            headerCheckbox.checked = selectAllCheckbox.checked;
            headerCheckbox.indeterminate = false;
            
            updateSelection();
        }
        
        // Batch delete function - FIXED
        function batchDeleteAction() {
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:not(:disabled):checked');
            const selectedCount = selectedCheckboxes.length;
            
            if (selectedCount === 0) {
                showToast({
                    title: 'No Users Selected',
                    message: 'Please select users to delete.',
                    icon: 'fa-solid fa-exclamation-circle',
                    type: 'warning',
                    duration: 3000
                });
                return;
            }
            
            // Get usernames for confirmation
            let usernames = [];
            selectedCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const username = row.querySelector('td:nth-child(3) strong').textContent;
                usernames.push(username);
            });
            
            const userList = usernames.length > 3 
                ? usernames.slice(0, 3).join(', ') + ` and ${usernames.length - 3} more...`
                : usernames.join(', ');
            
            // Show confirmation toast
            const toastId = showToast({
                title: 'Confirm Batch Delete',
                message: `Delete ${selectedCount} selected user(s)?`,
                icon: 'fa-solid fa-users-slash',
                type: 'error',
                duration: 10000
            });
            
            const toast = document.getElementById(toastId);
            const messageDiv = toast.querySelector('.toast-message');
            
            const buttonContainer = document.createElement('div');
            buttonContainer.style.marginTop = '10px';
            buttonContainer.style.display = 'flex';
            buttonContainer.style.gap = '8px';
            
            const confirmBtn = document.createElement('button');
            confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm Delete';
            confirmBtn.style.padding = '8px 16px';
            confirmBtn.style.background = '#dc3545';
            confirmBtn.style.color = 'white';
            confirmBtn.style.border = 'none';
            confirmBtn.style.borderRadius = '6px';
            confirmBtn.style.cursor = 'pointer';
            confirmBtn.style.fontSize = '12px';
            confirmBtn.style.fontWeight = '600';
            
            const cancelBtn = document.createElement('button');
            cancelBtn.innerHTML = '<i class="fa-solid fa-times"></i> Cancel';
            cancelBtn.style.padding = '8px 16px';
            cancelBtn.style.background = '#6c757d';
            cancelBtn.style.color = 'white';
            cancelBtn.style.border = 'none';
            cancelBtn.style.borderRadius = '6px';
            cancelBtn.style.cursor = 'pointer';
            cancelBtn.style.fontSize = '12px';
            cancelBtn.style.fontWeight = '600';
            
            buttonContainer.appendChild(confirmBtn);
            buttonContainer.appendChild(cancelBtn);
            messageDiv.appendChild(buttonContainer);
            
            confirmBtn.addEventListener('click', function() {
                showToast({
                    title: 'Deleting Users',
                    message: `<div class="redirect-loading"><div class="spinner"></div> Deleting ${selectedCount} user(s)...</div>`,
                    icon: 'fa-solid fa-trash',
                    type: 'error',
                    duration: 3000
                });
                
                // Submit the batch form
                setTimeout(() => {
                    document.getElementById('batchForm').submit();
                }, 1500);
                
                removeToast(toastId);
            });
            
            cancelBtn.addEventListener('click', function() {
                showToast({
                    title: 'Batch Delete Cancelled',
                    message: 'No users were deleted',
                    icon: 'fa-solid fa-ban',
                    type: 'info',
                    duration: 2000
                });
                removeToast(toastId);
            });
        }

        // Modal functions with toast notifications
        function openAddModal() {
            showToast({
                title: 'Opening Form',
                message: 'Loading user creation form...',
                icon: 'fa-solid fa-user-plus',
                type: 'info',
                duration: 2000
            });
            
            setTimeout(() => {
                document.getElementById('addModal').style.display = 'block';
            }, 300);
        }
        
        function closeAddModal() {
            showToast({
                title: 'Cancelled',
                message: 'User creation cancelled',
                icon: 'fa-solid fa-times-circle',
                type: 'warning',
                duration: 2000
            });
            
            document.getElementById('addModal').style.display = 'none';
        }
        
        function editUserAction(id, username, role, status) {
            showToast({
                title: 'Loading User Data',
                message: `Fetching details for user: ${username}`,
                icon: 'fa-solid fa-spinner',
                type: 'info',
                duration: 1500
            });
            
            setTimeout(() => {
                document.getElementById('edit_user_id').value = id;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_role').value = role;
                document.getElementById('edit_status').value = status;
                document.getElementById('editModal').style.display = 'block';
                
                showToast({
                    title: 'Ready to Edit',
                    message: `You are now editing ${username}'s profile`,
                    icon: 'fa-solid fa-user-edit',
                    type: 'info',
                    duration: 2000
                });
            }, 500);
        }
        
        function closeEditModal() {
            showToast({
                title: 'Cancelled',
                message: 'Edit operation cancelled',
                icon: 'fa-solid fa-times-circle',
                type: 'warning',
                duration: 2000
            });
            
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Delete user with confirmation toast
        function deleteUserAction(userId, username) {
            showToast({
                title: 'Confirm Delete',
                message: `Are you sure you want to delete user "${username}"?`,
                icon: 'fa-solid fa-trash',
                type: 'warning',
                duration: 4000
            });
            
            // Create confirmation buttons
            setTimeout(() => {
                const toastId = showToast({
                    title: 'Delete User',
                    message: `Click confirm to delete "${username}" permanently`,
                    icon: 'fa-solid fa-exclamation-triangle',
                    type: 'error',
                    duration: 10000
                });
                
                const toast = document.getElementById(toastId);
                const messageDiv = toast.querySelector('.toast-message');
                
                const buttonContainer = document.createElement('div');
                buttonContainer.style.marginTop = '10px';
                buttonContainer.style.display = 'flex';
                buttonContainer.style.gap = '8px';
                
                const confirmBtn = document.createElement('button');
                confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm';
                confirmBtn.style.padding = '6px 12px';
                confirmBtn.style.background = '#dc3545';
                confirmBtn.style.color = 'white';
                confirmBtn.style.border = 'none';
                confirmBtn.style.borderRadius = '6px';
                confirmBtn.style.cursor = 'pointer';
                confirmBtn.style.fontSize = '12px';
                confirmBtn.style.fontWeight = '600';
                
                const cancelBtn = document.createElement('button');
                cancelBtn.innerHTML = '<i class="fa-solid fa-times"></i> Cancel';
                cancelBtn.style.padding = '6px 12px';
                cancelBtn.style.background = '#6c757d';
                cancelBtn.style.color = 'white';
                cancelBtn.style.border = 'none';
                cancelBtn.style.borderRadius = '6px';
                cancelBtn.style.cursor = 'pointer';
                cancelBtn.style.fontSize = '12px';
                cancelBtn.style.fontWeight = '600';
                
                buttonContainer.appendChild(confirmBtn);
                buttonContainer.appendChild(cancelBtn);
                messageDiv.appendChild(buttonContainer);
                
                confirmBtn.addEventListener('click', function() {
                    showToast({
                        title: 'Deleting User',
                        message: `<div class="redirect-loading"><div class="spinner"></div> Deleting ${username}...</div>`,
                        icon: 'fa-solid fa-trash',
                        type: 'error',
                        duration: 2000
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'manage_users.php?delete=' + userId;
                    }, 1500);
                    
                    removeToast(toastId);
                });
                
                cancelBtn.addEventListener('click', function() {
                    showToast({
                        title: 'Delete Cancelled',
                        message: 'User deletion was cancelled',
                        icon: 'fa-solid fa-ban',
                        type: 'info',
                        duration: 2000
                    });
                    removeToast(toastId);
                });
            }, 500);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }
        
        // Sort table function with toast
        function sortTable(column) {
            showToast({
                title: 'Sorting',
                message: `Sorting users by ${column}...`,
                icon: 'fa-solid fa-sort',
                type: 'info',
                duration: 1500
            });
            
            setTimeout(() => {
                const urlParams = new URLSearchParams(window.location.search);
                let order = 'ASC';
                
                if (urlParams.get('sort') === column) {
                    order = urlParams.get('order') === 'ASC' ? 'DESC' : 'ASC';
                }
                
                urlParams.set('sort', column);
                urlParams.set('order', order);
                
                window.location.href = 'manage_users.php?' + urlParams.toString();
            }, 800);
        }
        
        // Apply filters with toast
        document.getElementById('applyFiltersBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const search = document.getElementById('search').value;
            const role = document.getElementById('role').value;
            const status = document.getElementById('status').value;
            
            let message = 'Applying filters';
            if (search) message += ` for "${search}"`;
            if (role !== 'all') message += ` (Role: ${role})`;
            if (status !== 'all') message += ` (Status: ${status})`;
            
            showToast({
                title: 'Filtering Users',
                message: message,
                icon: 'fa-solid fa-filter',
                type: 'info',
                duration: 2000
            });
            
            setTimeout(() => {
                this.closest('form').submit();
            }, 1200);
        });
        
        // Reset filters with toast
        document.getElementById('resetFiltersBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            showToast({
                title: 'Resetting Filters',
                message: 'Clearing all filters and searches...',
                icon: 'fa-solid fa-rotate-left',
                type: 'warning',
                duration: 2000
            });
            
            setTimeout(() => {
                window.location.href = 'manage_users.php';
            }, 1000);
        });
        
        // Form submission toasts
        document.addEventListener('DOMContentLoaded', function() {
            // Add user form submission
            const addForm = document.querySelector('#addModal form');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('add_username').value;
                    
                    showToast({
                        title: 'Creating User',
                        message: `<div class="redirect-loading"><div class="spinner"></div> Adding user "${username}" to the system...</div>`,
                        icon: 'fa-solid fa-user-plus',
                        type: 'info',
                        duration: 3000
                    });
                });
            }
            
            // Edit user form submission
            const editForm = document.querySelector('#editModal form');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('edit_username').value;
                    
                    showToast({
                        title: 'Updating User',
                        message: `<div class="redirect-loading"><div class="spinner"></div> Saving changes for "${username}"...</div>`,
                        icon: 'fa-solid fa-save',
                        type: 'info',
                        duration: 3000
                    });
                });
            }
            
            // Navigation links
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                if (link.getAttribute('href') && !link.getAttribute('href').includes('logout')) {
                    link.addEventListener('click', function(e) {
                        if (this.getAttribute('href') === 'manage_users.php') return;
                        
                        e.preventDefault();
                        const pageName = this.textContent.trim().replace(/\s+/g, ' ');
                        
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
            
            // Initialize batch selection
            updateSelection();
        });
        
        // Quick search on Enter key with toast
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                
                if (this.value.trim()) {
                    showToast({
                        title: 'Searching',
                        message: `Searching for "${this.value}"...`,
                        icon: 'fa-solid fa-search',
                        type: 'info',
                        duration: 2000
                    });
                    
                    setTimeout(() => {
                        this.form.submit();
                    }, 1200);
                }
            }
        });
        
        // Show welcome toast when page loads
        window.addEventListener('load', function() {
            setTimeout(() => {
                showToast({
                    title: 'User Management',
                    message: `Loaded <?= min($records_per_page, $result->num_rows) ?> of <?= $total_records ?> user accounts`,
                    icon: 'fa-solid fa-users',
                    type: 'success',
                    duration: 4000
                });
            }, 800);
        });
        
        // Add hover effects to table rows
        document.querySelectorAll('.users-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // Show role distribution summary
        function showRoleSummary() {
            const roleCounts = <?= json_encode($role_counts) ?>;
            let summary = 'Current user distribution: ';
            for (const [role, count] of Object.entries(roleCounts)) {
                summary += `${role}: ${count}, `;
            }
            summary = summary.slice(0, -2); // Remove trailing comma
            
            showToast({
                title: 'Role Distribution',
                message: summary,
                icon: 'fa-solid fa-chart-pie',
                type: 'info',
                duration: 5000
            });
        }
        
        // Show stats summary
        function showStatsSummary() {
            showToast({
                title: 'User Statistics',
                message: `Total: <?= $total_users ?> | Active: <?= $active_users ?> | Inactive: <?= $inactive_users ?>`,
                icon: 'fa-solid fa-chart-bar',
                type: 'info',
                duration: 5000
            });
        }
    </script>

</body>
</html>

<?php
// Helper function to generate pagination links
function generatePageLink($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'manage_users.php?' . http_build_query($params);
}
?>