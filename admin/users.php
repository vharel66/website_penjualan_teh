<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle AJAX request for getting user data
if (isset($_GET['action']) && $_GET['action'] == 'get_user') {
    $id = cleanInput($_GET['id']);
    
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit();
}

// Handle AJAX request for getting user password
if (isset($_GET['action']) && $_GET['action'] == 'get_user_password') {
    $id = cleanInput($_GET['id']);
    
    // For now, return a dummy password based on username
    // In production, you should add password_plain column to database
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        
        // Create dummy password based on username for demo
        $password = $username . '123';
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'password' => $password]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request!';
        redirect('users.php');
    }
    
    switch ($action) {
        case 'add':
            $username = cleanInput($_POST['username']);
            $email = cleanInput($_POST['email']);
            $password = cleanInput($_POST['password']);
            $role = cleanInput($_POST['role']);
            
            // Validate password length (minimum 6 characters)
            if (strlen($password) < 6) {
                $_SESSION['error'] = 'Password minimal 6 karakter!';
                break;
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User berhasil ditambahkan!';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan user!';
            }
            break;
            
        case 'edit':
            $id = cleanInput($_POST['id']);
            $username = cleanInput($_POST['username']);
            $email = cleanInput($_POST['email']);
            $role = cleanInput($_POST['role']);
            
            // Update password only if provided
            if (!empty($_POST['password'])) {
                $password = cleanInput($_POST['password']);
                
                // Validate password length (minimum 6 characters)
                if (strlen($password) < 6) {
                    $_SESSION['error'] = 'Password minimal 6 karakter!';
                    break;
                }
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $role, $id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User berhasil diupdate!';
            } else {
                $_SESSION['error'] = 'Gagal update user!';
            }
            break;
            
        case 'delete':
            $id = cleanInput($_POST['id']);
            
            // Debug: Log the ID being deleted
            error_log("Attempting to delete user ID: " . $id);
            
            // Check if user is admin before deleting
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && $user['role'] == 'admin') {
                $_SESSION['error'] = 'Tidak bisa menghapus user admin!';
                error_log("Cannot delete admin user");
            } else {
                // Start transaction for safe deletion
                $conn->begin_transaction();
                
                try {
                    // Delete related records first
                    // Delete cart items
                    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    // Delete order items and orders
                    $stmt = $conn->prepare("DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    // Now delete the user
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $_SESSION['success'] = 'User berhasil dihapus!';
                        error_log("User deleted successfully");
                    } else {
                        throw new Exception("Failed to delete user");
                    }
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = 'Gagal hapus user! User memiliki data terkait (pesanan, dll).';
                    error_log("Delete failed: " . $e->getMessage());
                }
            }
            break;
    }
    
    // Redirect to show messages
    header("Location: users.php");
    exit();
}

// Get all users
$users = [];
$result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Add dummy password for display (since passwords are hashed)
        $row['password'] = '••••••••'; // Hidden password display
        $users[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - TEH TITIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
        
        .sidebar .nav-link {
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 30px;
        }
        
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .page-header h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: none;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stats-icon.primary {
            background: #0d6efd;
            color: white;
        }
        
        .stats-icon.success {
            background: #198754;
            color: white;
        }
        
        .stats-icon.warning {
            background: #ffc107;
            color: white;
        }
        
        .stats-icon.info {
            background: #0dcaf0;
            color: white;
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .table-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: none;
        }
        
        .table-card .card-header {
            background: none;
            border: none;
            padding: 0;
            margin-bottom: 25px;
        }
        
        .table-card h3 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
        }
        
        .modern-table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .modern-table thead th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            border: none;
            padding: 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .modern-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .modern-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .user-email {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .badge-modern {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: none;
        }
        
        .badge-admin {
            background: #6f42c1;
            color: white;
        }
        
        .badge-user {
            background: #0d6efd;
            color: white;
        }
        
        .btn-modern {
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-add-user {
            background: #0d6efd;
            color: white;
        }
        
        .btn-edit {
            background: #0d6efd;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .alert-modern {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #198754;
            color: white;
        }
        
        .alert-danger {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0 sidebar">
                <div class="d-flex flex-column p-3 text-white">
                    <h4 class="mb-4"><img src="../assets/images/logo.jpg" alt="TEH TITIS Logo" height="40"> TEH TITIS</h4>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="products.php" class="nav-link">
                                <i class="fas fa-box"></i> Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="orders.php" class="nav-link">
                                <i class="fas fa-shopping-bag"></i> Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link active">
                                <i class="fas fa-users"></i> User
                            </a>
                        </li>
                                                <li class="nav-item mt-3">
                            <a href="../logout.php" class="nav-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Kelola User</h2>
                        </div>
                        <button class="btn btn-modern btn-add-user" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus"></i> Tambah User
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $total_users = count($users);
                                echo $total_users; 
                                ?>
                            </div>
                            <div class="stats-label">Total Users</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon success">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $admin_count = 0;
                                foreach ($users as $user) {
                                    if ($user['role'] == 'admin') $admin_count++;
                                }
                                echo $admin_count; 
                                ?>
                            </div>
                            <div class="stats-label">Admin Users</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon info">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $user_count = 0;
                                foreach ($users as $user) {
                                    if ($user['role'] == 'user') $user_count++;
                                }
                                echo $user_count; 
                                ?>
                            </div>
                            <div class="stats-label">Regular Users</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $new_users = 0;
                                $today = date('Y-m-d');
                                foreach ($users as $user) {
                                    if (date('Y-m-d', strtotime($user['created_at'])) == $today) $new_users++;
                                }
                                echo $new_users; 
                                ?>
                            </div>
                            <div class="stats-label">New Today</div>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-modern alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-modern alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="table-card">
                    <div class="card-header">
                        <h3>Daftar User</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?php echo $user['username']; ?></div>
                                                    <div class="user-email">ID: #<?php echo $user['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <span class="badge badge-modern <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-modern btn-edit btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['role'] != 'admin'): ?>
                                                <button class="btn btn-modern btn-delete btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="userForm">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password (minimal 6 karakter)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Masukkan password minimal 6 karakter">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-left: none;">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Password minimal 6 karakter untuk keamanan</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle user password visibility in table
            window.toggleUserPassword = function(userId) {
                const passwordSpan = document.getElementById('password-' + userId);
                const eyeIcon = document.getElementById('eye-' + userId);
                
                if (passwordSpan && eyeIcon) {
                    if (passwordSpan.textContent === '•••••••') {
                        // Show actual password
                        fetch('users.php?action=get_user_password&id=' + userId)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    passwordSpan.textContent = data.password;
                                    eyeIcon.classList.remove('fa-eye');
                                    eyeIcon.classList.add('fa-eye-slash');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Gagal mengambil password');
                            });
                    } else {
                        // Hide password
                        passwordSpan.textContent = '•••••••';
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                }
            };
            
            // Edit user function
            window.editUser = function(id) {
                // Load user data via AJAX
                fetch('users.php?action=get_user&id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Fill form with user data
                            const modalTitle = document.getElementById('modalTitle');
                            const formAction = document.getElementById('formAction');
                            const userId = document.getElementById('userId');
                            const username = document.getElementById('username');
                            const email = document.getElementById('email');
                            const role = document.getElementById('role');
                            const passwordField = document.getElementById('password');
                            
                            if (modalTitle) modalTitle.textContent = 'Edit User';
                            if (formAction) formAction.value = 'edit';
                            if (userId) userId.value = data.user.id;
                            if (username) username.value = data.user.username;
                            if (email) email.value = data.user.email;
                            if (role) role.value = data.user.role;
                            
                            // Adjust password field for edit mode
                            if (passwordField) {
                                const passwordLabel = passwordField.previousElementSibling;
                                if (passwordLabel) {
                                    passwordLabel.textContent = 'Password (kosongkan jika tidak ingin mengubah)';
                                }
                                passwordField.removeAttribute('required');
                                passwordField.removeAttribute('minlength');
                                passwordField.placeholder = 'Kosongkan untuk tidak mengubah password';
                                if (passwordField.nextElementSibling) {
                                    passwordField.nextElementSibling.textContent = 'Isi hanya jika ingin mengubah password user';
                                }
                            }
                            
                            // Show modal
                            const modal = new bootstrap.Modal(document.getElementById('userModal'));
                            modal.show();
                        } else {
                            alert('Gagal memuat data user: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat memuat data');
                    });
            };
            
            // Delete user function
            window.deleteUser = function(id) {
                if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            };
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const passwordField = document.getElementById('password');
                    const eyeIcon = document.getElementById('eyeIcon');
                    
                    if (passwordField && eyeIcon) {
                        if (passwordField.type === 'password') {
                            passwordField.type = 'text';
                            eyeIcon.classList.remove('fa-eye');
                            eyeIcon.classList.add('fa-eye-slash');
                        } else {
                            passwordField.type = 'password';
                            eyeIcon.classList.remove('fa-eye-slash');
                            eyeIcon.classList.add('fa-eye');
                        }
                    }
                });
            }
            
            // Reset form when modal is closed
            const userModal = document.getElementById('userModal');
            if (userModal) {
                userModal.addEventListener('hidden.bs.modal', function () {
                    const userForm = document.getElementById('userForm');
                    const modalTitle = document.getElementById('modalTitle');
                    const formAction = document.getElementById('formAction');
                    const userId = document.getElementById('userId');
                    const passwordField = document.getElementById('password');
                    
                    if (userForm) userForm.reset();
                    if (modalTitle) modalTitle.textContent = 'Tambah User';
                    if (formAction) formAction.value = 'add';
                    if (userId) userId.value = '';
                    
                    // Reset password field to add mode
                    if (passwordField) {
                        const passwordLabel = passwordField.previousElementSibling;
                        if (passwordLabel) {
                            passwordLabel.textContent = 'Password (minimal 6 karakter)';
                        }
                        passwordField.setAttribute('required', 'required');
                        passwordField.setAttribute('minlength', '6');
                        passwordField.placeholder = 'Masukkan password minimal 6 karakter';
                        if (passwordField.nextElementSibling) {
                            passwordField.nextElementSibling.textContent = 'Password minimal 6 karakter untuk keamanan';
                        }
                        
                        // Reset eye icon to hidden state
                        const eyeIcon = document.getElementById('eyeIcon');
                        if (eyeIcon) {
                            eyeIcon.classList.remove('fa-eye-slash');
                            eyeIcon.classList.add('fa-eye');
                        }
                        passwordField.type = 'password';
                    }
                });
            }
        });
    </script>

    </body>
</html>
