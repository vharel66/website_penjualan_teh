<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if user is admin - admins should not access cart
if (isAdmin()) {
    $_SESSION['error'] = 'Admin tidak dapat mengakses keranjang belanja!';
    redirect('index.php');
}

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request!';
        redirect('cart.php');
    }
    
    switch ($action) {
        case 'update':
            $cart_id = cleanInput($_POST['cart_id']);
            $quantity = cleanInput($_POST['quantity']);
            
            // Check stock availability
            $stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.stok FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
            $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $cart_item = $result->fetch_assoc();
                if ($quantity <= $cart_item['stok']) {
                    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $update_stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
                    $update_stmt->execute();
                    $_SESSION['success'] = 'Keranjang berhasil diupdate!';
                } else {
                    $_SESSION['error'] = 'Stok tidak mencukupi!';
                }
            }
            break;
            
        case 'remove':
            $cart_id = cleanInput($_POST['cart_id']);
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['success'] = 'Produk berhasil dihapus dari keranjang!';
            break;
            
        case 'clear':
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['success'] = 'Keranjang berhasil dikosongkan!';
            break;
    }
    
    redirect('cart.php');
}

// Get cart items
$cart_items = [];
$total_price = 0;
$stmt = $conn->prepare("
    SELECT c.*, p.nama_produk, p.harga, p.gambar, p.stok 
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
    <title>Keranjang Belanja - Teh Shop</title>
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
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
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
        .cart-item {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-input {
            width: 70px;
            text-align: center;
        }
        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
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
    <?php if (isset($_SESSION['success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cart Content -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h3 class="mb-4 text-center">Keranjang Belanja</h3>
                
                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                            <h4>Keranjang Belanja Kosong</h4>
                            <p class="text-muted">Belum ada produk di keranjang Anda.</p>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Lanjut Belanja
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="assets/images/<?php echo $item['gambar']; ?>" class="product-image" alt="<?php echo $item['nama_produk']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <h5><?php echo $item['nama_produk']; ?></h5>
                                    <p class="text-muted mb-0"><?php echo formatRupiah($item['harga']); ?></p>
                                    <?php if ($item['stok'] < 10): ?>
                                        <small class="text-warning">Stok tersisa: <?php echo $item['stok']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <form method="POST" action="" class="d-flex align-items-center">
                                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-secondary" onclick="decreaseQuantity(this)">-</button>
                                            <input type="number" name="quantity" class="form-control quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stok']; ?>" onchange="this.form.submit()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="increaseQuantity(this)">+</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-2 text-end">
                                    <h5><?php echo formatRupiah($item['harga'] * $item['quantity']); ?></h5>
                                </div>
                                <div class="col-md-1 text-end">
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-4 text-center">
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Kosongkan keranjang?')">
                                <i class="fas fa-trash"></i> Kosongkan Keranjang
                            </button>
                        </form>
                        <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-arrow-left"></i> Lanjut Belanja
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($cart_items)): ?>
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h5 class="mb-4">Ringkasan Belanja</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?php echo formatRupiah($total_price); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkos Kirim:</span>
                            <span>Gratis</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <h5>Total:</h5>
                            <h5><?php echo formatRupiah($total_price); ?></h5>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-checkout btn-success w-100">
                            <i class="fas fa-credit-card"></i> Checkout
                        </a>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Pembayaran aman 100%
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function increaseQuantity(button) {
            const input = button.parentElement.querySelector('input[type="number"]');
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value);
            
            if (current < max) {
                input.value = current + 1;
                updateCart(button.getAttribute('data-product-id'), current + 1);
            }
        }
        
        function decreaseQuantity(button) {
            const input = button.parentElement.querySelector('input[type="number"]');
            const current = parseInt(input.value);
            
            if (current > 1) {
                input.value = current - 1;
                updateCart(button.getAttribute('data-product-id'), current - 1);
            }
        }
        
        function updateCart(productId, quantity) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                <input type="hidden" name="update_cart" value="1">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="${quantity}">
            `;
            document.body.appendChild(form);
            form.submit();
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
        
        function decreaseQuantity(button) {
            const input = button.nextElementSibling;
            const currentValue = parseInt(input.value);
            const minValue = parseInt(input.min);
            
            if (currentValue > minValue) {
                input.value = currentValue - 1;
                input.form.submit();
            }
        }
        
        function increaseQuantity(button) {
            const input = button.previousElementSibling;
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max);
            
            if (currentValue < maxValue) {
                input.value = currentValue + 1;
                input.form.submit();
            }
        }
    </script>
</body>
</html>
