<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - TEH TITIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/images/tea-plantation.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        .about-content {
            padding: 80px 0;
            background: #f8f9fa;
        }
        .about-left {
            padding-right: 50px;
        }
        .about-right {
            padding-left: 50px;
        }
        .brand-title {
            font-size: 3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        .brand-subtitle {
            font-size: 1.5rem;
            color: #27ae60;
            margin-bottom: 2rem;
        }
        .about-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 2rem;
        }
        .feature-box {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        .feature-icon {
            font-size: 3rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }
        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }
        .image-container img {
            width: 100%;
            height: 600px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .image-container:hover img {
            transform: scale(1.05);
        }
        .tagline {
            font-size: 2rem;
            font-weight: 300;
            color: #27ae60;
            margin-bottom: 1rem;
        }
        .brand-logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .slogan {
            font-style: italic;
            color: #666;
            margin-bottom: 2rem;
        }
        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 60px 0 20px;
        }
        .footer h5 {
            color: #27ae60;
            margin-bottom: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        .footer a {
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        .footer a:hover {
            color: #27ae60;
            transform: translateX(5px);
        }
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        .footer-links i {
            margin-right: 8px;
            width: 16px;
        }
        .footer-logo {
            color: #27ae60;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .footer-desc {
            color: #bdc3c7;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .social-links {
            display: flex;
            gap: 15px;
        }
        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .social-link:hover {
            background: #27ae60;
            color: white;
            transform: translateY(-3px);
        }
        .newsletter-form .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 25px 0 0 25px;
        }
        .newsletter-form .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }
        .newsletter-form .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: #27ae60;
            box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
            color: white;
        }
        .btn-subscribe {
            background: #27ae60;
            border: none;
            border-radius: 0 25px 25px 0;
            padding: 0 15px;
            transition: all 0.3s ease;
        }
        .btn-subscribe:hover {
            background: #2ecc71;
        }
        .contact-info p {
            color: #bdc3c7;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .contact-info i {
            margin-right: 8px;
            color: #27ae60;
        }
        .footer-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 40px 0 20px;
        }
        .footer-bottom {
            padding-top: 20px;
        }
        .copyright {
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        .payment-methods {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.5rem;
        }
        .payment-title {
            color: #bdc3c7;
            font-size: 0.8rem;
            margin-right: 10px;
        }
        .payment-methods i {
            color: #bdc3c7;
            transition: color 0.3s ease;
        }
        .payment-methods i:hover {
            color: #27ae60;
        }
        .navbar {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
            transition: transform 0.3s ease-in-out;
        }
        .navbar-brand {
            color: #27ae60 !important;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .navbar-nav .nav-link {
            color: #ecf0f1 !important;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        .navbar-nav .nav-link:hover {
            color: #27ae60 !important;
            transform: translateX(5px);
        }
        .navbar-nav .nav-link.active {
            color: #27ae60 !important;
        }
        .navbar-nav .dropdown-menu {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .navbar-nav .dropdown-item {
            color: #ecf0f1;
            transition: all 0.3s ease;
        }
        .navbar-nav .dropdown-item:hover {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }
        .profile-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            height: 100%;
        }
        .password-section {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: 8px;
            height: 100%;
        }
        .section-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i {
            color: #27ae60;
        }
        .section-title.text-danger i {
            color: #dc3545;
        }
        @media (max-width: 768px) {
            .about-left, .about-right {
                padding: 20px;
            }
            .brand-title {
                font-size: 2rem;
            }
            .hero-section {
                padding: 80px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <img src="assets/images/logo.jpg" alt="TEH TITIS Logo" height="50"> TEH TITIS
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>about.php">Tentang Kami</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/products.php">Kelola Produk</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/orders.php">Lihat Pesanan</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/users.php">Kelola User</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>cart.php">
                                <i class="fas fa-shopping-cart"></i> Keranjang
                                <span class="cart-badge">
                                    <?php 
                                    if (isLoggedIn()) {
                                        $user_id = $_SESSION['user_id'];
                                        $cart_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id")->fetch_assoc()['total'];
                                        echo $cart_count ? $cart_count : 0;
                                    }
                                    ?>
                                </span>
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showAccountModal()">Akun</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>orders.php">Pesanan Saya</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- About Content -->
    <section class="about-content">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left Column -->
                <div class="col-lg-6 about-left">
                    <div class="text-center mb-4">
                        <div class="brand-title">TEH TITIS</div>
                        <h2 class="brand-subtitle">terbaik dari alam</h2>
                        
                    </div>
                    
                    <div class="image-container mb-4">
                        <img src="assets/images/about1.jpg" alt="Teh Titis Cups">
                    </div>
            
                    <div class="feature-box">
                        <div class="feature-icon">
                            <img src="assets/images/logo.jpg" alt="TEH TITIS Logo" height="350">
                        </div>
                        <h4>100% Alami</h4>
                        <p>Teh pilihan dari perkebunan terbaik dengan proses alami tanpa bahan kimia</p>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-6 about-right">
                    <h2 class="brand-title">TEH TITIS</h2>
                    <h2 class="brand-subtitle">Tradisi dalam setiap tegukan</h2>
                    
                    <p class="about-text">
                        Selamat datang di dunia Teh Titis, di mana setiap cangkir teh adalah perjalanan melalui warisan budaya yang kaya. Kami memahami bahwa teh bukan hanya minuman, tetapi merupakan simbol kehangatan, ketenangan, dan harmoni yang telah diwariskan dari generasi ke generasi.
                    </p>
            
                    <p class="about-text">
                        Di Teh Titis, kami berkomitmen untuk menghadirkan pengalaman teh yang autentik dengan memilih daun teh terbaik dari perkebunan pilihan. Setiap proses mulai dari pemilihan, pemetikan, hingga pengolahan dilakukan dengan penuh perhatian untuk menjaga kualitas dan cita rasa alami yang menjadi ciri khas Teh Titis.
                    </p>
                    
                    <p class="about-text">
                        Inovasi kami dalam menciptakan teh larut tanpa ampas menjadi bukti dedikasi kami untuk memberikan kemudahan tanpa mengorbankan kualitas. Nikmati kehangatan dan aroma khas Teh Titis dalam setiap tegukan, membawa Anda merasakan kedamaian dan kebahagiaan yang sesungguhnya.
                    </p>
                    
                    <div class="image-container">
                        <img src="assets/images/about2.jpg" alt="Teh Tradisional">
                    </div>
                    
                    <blockquote class="blockquote text-center mt-4">
                        <p class="mb-0">"Nikmati Teh Titis warisan rasa Nusantara, dari alam untuk Anda"</p>
                    </blockquote>
                </div>
            </div>
            
            <!-- Features Section -->
            <div class="row mt-5">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Dibuat dengan Cinta</h4>
                        <p>Setiap produk Teh Titis dibuat dengan penuh kasih dan perhatian pada detail</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h4>Kualitas Terjamin</h4>
                        <p>Standar kualitas tertinggi untuk memastikan kepuasan pelanggan</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h4>Berkelanjutan</h4>
                        <p>Praktik pertanian yang ramah lingkungan untuk masa depan yang lebih baik</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAccountModal() {
            // Load user data via AJAX
            fetch('get_user_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create modal dynamically with new design
                        const modalHtml = `
                            <div class="modal fade" id="accountModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Kelola Akun</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <!-- Profile Section -->
                                                <div class="col-md-6">
                                                    <div class="profile-section">
                                                        <h6 class="section-title">
                                                            <i class="fas fa-user"></i> Profile
                                                        </h6>
                                                        <form id="accountForm">
                                                            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                                            <input type="hidden" name="update_account" value="1">
                                                            
                                                            <div class="mb-3">
                                                                <label for="accountUsername" class="form-label">Username</label>
                                                                <input type="text" class="form-control" id="accountUsername" name="username" value="${data.user.username}" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="accountEmail" class="form-label">Email</label>
                                                                <input type="email" class="form-control" id="accountEmail" name="email" value="${data.user.email}" required>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <!-- Password Section -->
                                                <div class="col-md-6">
                                                    <div class="password-section">
                                                        <h6 class="section-title">
                                                            <i class="fas fa-key"></i> Password
                                                        </h6>
                                                        <p class="text-muted small">Kosongkan jika tidak ingin mengubah password</p>
                                                        
                                                        <div class="mb-3">
                                                            <label for="currentPassword" class="form-label">Password Saat Ini</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control" id="currentPassword" name="current_password">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="newPassword" class="form-label">Password Baru</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control" id="newPassword" name="new_password" minlength="6" placeholder="Minimal 6 karakter">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="confirmPassword" class="form-label">Konfirmasi Password Baru</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" minlength="6" placeholder="Ulangi password baru">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Delete Account Section -->
                                            <div class="delete-section mt-4">
                                                <h6 class="section-title text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Hapus Akun
                                                </h6>
                                                <p class="text-muted">Tindakan ini tidak dapat dibatalkan. Semua data Anda akan dihapus permanen.</p>
                                                <button class="btn btn-danger btn-sm" onclick="deleteAccount()">
                                                    <i class="fas fa-trash"></i> Hapus Akun Saya
                                                </button>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="button" class="btn btn-success" onclick="updateAccount()">
                                                <i class="fas fa-save"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Remove existing modal if any
                        const existingModal = document.getElementById('accountModal');
                        if (existingModal) {
                            existingModal.remove();
                        }
                        
                        // Add modal to body
                        document.body.insertAdjacentHTML('beforeend', modalHtml);
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('accountModal'));
                        modal.show();
                    } else {
                        alert('Gagal memuat data akun');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data');
                });
        }
        
        function updateAccount() {
            const form = document.getElementById('accountForm');
            const formData = new FormData(form);
            
            fetch('update_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Akun berhasil diperbarui!');
                    location.reload();
                } else {
                    alert(data.message || 'Gagal memperbarui akun');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memperbarui akun');
            });
        }
        
        function deleteAccount() {
            if (confirm('Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan!')) {
                if (confirm('PERINGATAN: Semua data pesanan, keranjang, dan informasi akun akan dihapus permanen. Lanjutkan?')) {
                    fetch('delete_account.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'csrf_token=<?php echo getCSRFToken(); ?>'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Akun berhasil dihapus. Anda akan diarahkan ke halaman utama.');
                            window.location.href = 'logout.php';
                        } else {
                            alert(data.message || 'Gagal menghapus akun');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus akun');
                    });
                }
            }
        }
        
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
