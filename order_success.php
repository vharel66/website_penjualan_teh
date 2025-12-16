<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get order details
$order_id = $_GET['order_id'] ?? 0;
$order = null;
$order_items = [];

if ($order_id) {
    $stmt = $conn->prepare("
        SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $order = $result->fetch_assoc();
        
        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, p.nama_produk, p.gambar 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $order_items[] = $row;
        }
    }
}

if (!$order) {
    $_SESSION['error'] = 'Pesanan tidak ditemukan!';
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Teh Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;\n            font-size: 1.5rem;
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
        .success-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            margin: 2rem auto;
            max-width: 800px;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: white;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        .btn-home:hover {
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
                        <a class="nav-link" href="<?php echo BASE_URL; ?>about.php">About Us</a>
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                        <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Success Content -->
    <div class="container py-5">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h2 class="mb-3">Pesanan Berhasil!</h2>
            <p class="text-muted mb-4">Terima kasih telah berbelanja produk kami. Pesanan Anda telah kami terima dan akan segera diproses.</p>
            
            <div class="alert alert-info">
                <strong>Nomor Pesanan: #<?php echo $order['id']; ?></strong><br>
                <small>Simpan nomor pesanan ini untuk tracking status pesanan Anda.</small>
            </div>
            
            <!-- Order Details -->
            <div class="order-details">
                <h5 class="mb-3">Detail Pesanan</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Tanggal Pesanan:</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Nama Penerima:</strong><br>
                        <?php echo $order['nama_penerima']; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Telepon:</strong><br>
                        <?php echo $order['telepon']; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Alamat Pengiriman:</strong><br>
                    <?php echo nl2br($order['alamat']); ?>
                </div>
                
                <hr>
                
                <h6 class="mb-3">Produk yang Dipesan:</h6>
                <?php foreach ($order_items as $item): ?>
                    <div class="product-item">
                        <img src="assets/images/<?php echo $item['gambar']; ?>" class="product-image" alt="<?php echo $item['nama_produk']; ?>">
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
                
                <div class="d-flex justify-content-between">
                    <h5>Total Pembayaran:</h5>
                    <h5><?php echo formatRupiah($order['total_harga']); ?></h5>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-home btn-primary me-2">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
                <a href="<?php echo BASE_URL; ?>orders.php" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> Lihat Pesanan Saya
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Anda akan menerima email konfirmasi di <?php echo $order['email']; ?> 
                    beserta detail pesanan dan informasi pengiriman.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
