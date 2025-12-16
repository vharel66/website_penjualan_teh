<?php
require_once 'config.php';

// Get all products for display
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle cart operations
if (isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    $product_id = cleanInput($_POST['product_id']);
    $quantity = cleanInput($_POST['quantity']);
    
    // Check if product exists and has enough stock
    $product_check = $conn->query("SELECT stok FROM products WHERE id = $product_id");
    if ($product_check && $product_check->num_rows > 0) {
        $product = $product_check->fetch_assoc();
        if ($product['stok'] >= $quantity) {
            // Add to cart
            $user_id = $_SESSION['user_id'];
            $check_cart = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");
            
            if ($check_cart && $check_cart->num_rows > 0) {
                // Update quantity if already in cart
                $cart_item = $check_cart->fetch_assoc();
                $new_quantity = $cart_item['quantity'] + $quantity;
                $conn->query("UPDATE cart SET quantity = $new_quantity WHERE id = {$cart_item['id']}");
            } else {
                // Insert new item to cart
                $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
            }
            
            $_SESSION['success'] = "Produk berhasil ditambahkan ke keranjang!";
        } else {
            $_SESSION['error'] = "Stok tidak mencukupi!";
        }
    }
    
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - TEH TITIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: url('assets/images/background2.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #27ae60;
        }
        .modal-header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .account-form {
            padding: 1rem 0;
        }
        .password-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .delete-section {
            border: 2px dashed #dc3545;
            padding: 1.5rem;
            border-radius: 8px;
            background: #fff5f5;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .input-group {
            margin-bottom: 0.5rem;
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
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
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
        .navbar {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
            transition: transform 0.3s ease-in-out;
        }
        .navbar-brand {
            color: #27ae60 !important;
            font-weight: bold;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-brand img {
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        .navbar-brand:hover img {
            transform: scale(1.1);
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
                        <a class="nav-link" href="<?php echo BASE_URL; ?>about.php">Tentang Kami</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Nikmati Keaslian Alam dalam Setiap Seduhan Dari Produk TEH TITIS</h1>
            <p class="lead mb-4">Dibuat dari daun teh pilihan yang tumbuh subur di dataran tinggi Nusantara</p>
        </div>
    </section>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="container">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="container">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Products Section -->
    <section id="products" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Produk Kami</h2>
            
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card">
                            <img src="assets/images/<?php echo $product['gambar']; ?>" class="card-img-top product-image" alt="<?php echo $product['nama_produk']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $product['nama_produk']; ?></h5>
                                <p class="card-text"><?php echo substr($product['deskripsi'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="price-tag"><?php echo formatRupiah($product['harga']); ?></span>
                                    <small class="text-muted">Stok: <?php echo $product['stok']; ?></small>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-success btn-sm" onclick="showProductDetail(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-info-circle"></i> Detail
                                    </button>
                                    <?php if ($product['stok'] > 0): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stok']; ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-success btn-sm">
                                                    <i class="fas fa-cart-plus"></i> Tambah
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm w-100" disabled>
                                            <i class="fas fa-times"></i> Stok Habis
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="text-center">
                    <p class="text-muted">Belum ada produk tersedia.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="productDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="detailImage" src="" class="img-fluid rounded" alt="Produk">
                        </div>
                        <div class="col-md-6">
                            <h4 id="detailName"></h4>
                            <p class="text-muted" id="detailCategory"></p>
                            <h3 class="text-success" id="detailPrice"></h3>
                            <p><small class="text-muted">Stok: <span id="detailStock"></span></small></p>
                            <hr>
                            <h6>Deskripsi:</h6>
                            <p id="detailDescription"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-success" id="addToCartFromDetail">
                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Product data for detail modal
        const products = <?php echo json_encode($products); ?>;
        
        function showProductDetail(productId) {
            const product = products.find(p => p.id == productId);
            
            if (product) {
                document.getElementById('detailImage').src = 'assets/images/' + product.gambar;
                document.getElementById('detailName').textContent = product.nama_produk;
                document.getElementById('detailCategory').textContent = product.kategori;
                document.getElementById('detailPrice').textContent = formatRupiah(product.harga);
                document.getElementById('detailStock').textContent = product.stok;
                document.getElementById('detailDescription').textContent = product.deskripsi;
                
                // Update add to cart button
                const addToCartBtn = document.getElementById('addToCartFromDetail');
                if (product.stok > 0) {
                    addToCartBtn.disabled = false;
                    addToCartBtn.onclick = function() {
                        addToCart(product.id);
                    };
                } else {
                    addToCartBtn.disabled = true;
                    addToCartBtn.textContent = 'Stok Habis';
                }
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
                modal.show();
            }
        }
        
        function addToCart(productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="quantity" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function formatRupiah(amount) {
            return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
        }
        
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

        // Hide/show navbar on scroll
        let lastScrollTop = 0;
        let scrollTimeout;
        const navbar = document.querySelector('.navbar');
        
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                navbar.style.transform = 'translateY(-100%)';
                navbar.style.transition = 'transform 0.3s ease-in-out';
                clearTimeout(scrollTimeout);
            } else {
                // Scrolling up
                navbar.style.transform = 'translateY(0)';
                navbar.style.transition = 'transform 0.3s ease-in-out';
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            
            // Hide navbar after scrolling stops
            scrollTimeout = setTimeout(function() {
                if (scrollTop > 200) {
                    navbar.style.transform = 'translateY(-100%)';
                }
            }, 3000);
        });

        // Show navbar when mouse is near top of screen
        document.addEventListener('mousemove', function(e) {
            if (e.clientY < 50) {
                navbar.style.transform = 'translateY(0)';
                clearTimeout(scrollTimeout);
            }
        });
    </script>
</body>
</html>
