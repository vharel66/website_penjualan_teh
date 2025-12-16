<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request!';
        redirect('register.php');
    }
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'Password minimal 6 karakter!';
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Username atau email sudah digunakan!';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Registrasi berhasil! Silakan login.';
                redirect('login.php');
            } else {
                $_SESSION['error'] = 'Registrasi gagal! Silakan coba lagi.';
            }
        }
    }
    
    redirect('register.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TEH TITIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .register-form {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #27ae60;
            box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="register-container">
                    <div class="register-header">
                        <h2><img src="assets/images/logo.jpg" alt="TEH TITIS Logo" height="60"></h2>
                        <p class="mb-0">Buat akun baru</p>
                    </div>
                    
                    <div class="register-form">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user"></i> Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-left: none;">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Minimal 6 karakter</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i> Konfirmasi Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" style="border-left: none;">
                                        <i class="fas fa-eye" id="confirmEyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus"></i> Daftar
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p>Sudah punya akun? <a href="login.php">Login sekarang</a></p>
                            <p><a href="index.php"><i class="fas fa-arrow-left"></i> Kembali ke beranda</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordField = document.getElementById('confirm_password');
            const confirmEyeIcon = document.getElementById('confirmEyeIcon');
            
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                confirmEyeIcon.classList.remove('fa-eye');
                confirmEyeIcon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordField.type = 'password';
                confirmEyeIcon.classList.remove('fa-eye-slash');
                confirmEyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
