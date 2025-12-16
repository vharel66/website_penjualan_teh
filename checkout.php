<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if cart is empty
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['count'];

if ($cart_count == 0) {
    $_SESSION['error'] = 'Keranjang belanja kosong!';
    redirect('cart.php');
}

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request!';
        redirect('checkout.php');
    }
    
    $nama_penerima = cleanInput($_POST['nama_penerima']);
    $alamat = cleanInput($_POST['alamat']);
    $telepon = cleanInput($_POST['telepon']);
    $email = cleanInput($_POST['email']);
    $catatan = cleanInput($_POST['catatan']);
    
    // Validation
    if (empty($nama_penerima) || empty($alamat) || empty($telepon) || empty($email)) {
        $_SESSION['error'] = 'Semua field wajib diisi!';
    } else {
        // Get cart items
        $cart_items = [];
        $total_harga = 0;
        $stmt = $conn->prepare("
            SELECT c.*, p.harga, p.stok 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $can_checkout = true;
        while ($row = $result->fetch_assoc()) {
            if ($row['quantity'] > $row['stok']) {
                $can_checkout = false;
                $_SESSION['error'] = 'Stok produk "' . $row['nama_produk'] . '" tidak mencukupi!';
                break;
            }
            $cart_items[] = $row;
            $total_harga += $row['harga'] * $row['quantity'];
        }
        
        if ($can_checkout) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create order
                $stmt = $conn->prepare("
                    INSERT INTO orders (user_id, total_harga, nama_penerima, alamat, telepon, email, catatan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("idsssss", $_SESSION['user_id'], $total_harga, $nama_penerima, $alamat, $telepon, $email, $catatan);
                $stmt->execute();
                $order_id = $conn->insert_id;
                
                // Insert order items and update stock
                foreach ($cart_items as $item) {
                    // Insert order item
                    $stmt = $conn->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, harga) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['harga']);
                    $stmt->execute();
                    
                    // Update product stock
                    $new_stock = $item['stok'] - $item['quantity'];
                    $stmt = $conn->prepare("UPDATE products SET stok = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_stock, $item['product_id']);
                    $stmt->execute();
                }
                
                // Clear cart
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $_SESSION['success'] = 'Pesanan berhasil dibuat! Order #' . $order_id;
                redirect('order_success.php?order_id=' . $order_id);
                
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Terjadi kesalahan saat memproses pesanan!';
            }
        }
    }
    
    redirect('checkout.php');
}

// Get cart items for display
$cart_items = [];
$total_price = 0;
$stmt = $conn->prepare("
    SELECT c.*, p.nama_produk, p.harga, p.gambar 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['harga'] * $row['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Teh Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
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
        .checkout-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-checkout {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        .btn-checkout:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: -1;
        }
        .step:last-child::after {
            display: none;
        }
        .step.active {
            color: #667eea;
        }
        .step.active::after {
            background: #667eea;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        .step.active .step-circle {
            background: #667eea;
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
                                    $user_id = $_SESSION['user_id'];
                                    $cart_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id")->fetch_assoc()['total'];
                                    echo $cart_count ? $cart_count : 0;
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

    <!-- Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Checkout Content -->
    <div class="container py-5">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step">
                <div class="step-circle">1</div>
                <small>Keranjang</small>
            </div>
            <div class="step active">
                <div class="step-circle">2</div>
                <small>Checkout</small>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <small>Selesai</small>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Shipping Information -->
                <div class="checkout-section">
                    <h4 class="mb-4">Informasi Pengiriman</h4>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_penerima" class="form-label">Nama Penerima *</label>
                                    <input type="text" class="form-control" id="nama_penerima" name="nama_penerima" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telepon" class="form-label">Telepon *</label>
                                    <input type="tel" class="form-control" id="telepon" name="telepon" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat Lengkap *</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="2" placeholder="Contoh: tolong dikirim sebelum jam 5 sore"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-checkout btn-success">
                            <i class="fas fa-credit-card"></i> Proses Pesanan
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="order-summary">
                    <h5 class="mb-4">Ringkasan Pesanan</h5>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/<?php echo $item['gambar']; ?>" class="product-image me-3" alt="<?php echo $item['nama_produk']; ?>">
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?php echo $item['nama_produk']; ?></h6>
                                <small class="text-muted"><?php echo $item['quantity']; ?> x <?php echo formatRupiah($item['harga']); ?></small>
                            </div>
                            <div>
                                <strong><?php echo formatRupiah($item['harga'] * $item['quantity']); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatRupiah($total_price); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ongkos Kirim:</span>
                        <span>Gratis</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <h5>Total:</h5>
                        <h5><?php echo formatRupiah($total_price); ?></h5>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="cart.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-arrow-left"></i> Kembali ke Keranjang
                    </a>
                </div>
            </div>
        </div>
    </div>

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
            if (confirm('Apakah Anda yakin ingin menghapus akun? Tindahan ini tidak dapat dibatalkan!')) {
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
