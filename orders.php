<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user orders
$orders = [];
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get order details for modal
$order_details = [];
if (isset($_GET['view'])) {
    $order_id = cleanInput($_GET['view']);
    
    // Verify order belongs to user
    $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $order_id, $_SESSION['user_id']);
    $check->execute();
    
    if ($check->get_result()->num_rows === 1) {
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
            $order_details[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Teh Shop</title>
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
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .order-row:hover {
            background-color: #f8f9fa;
            cursor: pointer;
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

    <!-- Orders Content -->
    <div class="container py-5">
        <h2 class="mb-4">Pesanan Saya</h2>
        
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                <h4>Belum Ada Pesanan</h4>
                <p class="text-muted">Anda belum melakukan pesanan apa pun.</p>
                <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="mb-0">Pesanan #<?php echo $order['id']; ?></h5>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                </div>
                                <span class="badge status-badge bg-<?php 
                                    echo $order['status'] == 'pending' ? 'warning' : 
                                         $order['status'] == 'processing' ? 'info' : 
                                         $order['status'] == 'shipped' ? 'primary' : 
                                         $order['status'] == 'delivered' ? 'success' : 'danger'; 
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-2">
                                <strong>Penerima:</strong> <?php echo $order['nama_penerima']; ?><br>
                                <small class="text-muted"><?php echo $order['item_count']; ?> item • Total: <?php echo formatRupiah($order['total_harga']); ?></small>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-outline-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesanan #<span id="orderId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId) {
            window.location.href = 'orders.php?view=' + orderId;
        }
        
        <?php if (isset($_GET['view']) && !empty($order_details)): ?>
        // Auto-show order details
        document.addEventListener('DOMContentLoaded', function() {
            const orderId = <?php echo $_GET['view']; ?>;
            const orderData = <?php echo json_encode($order_details); ?>;
            
            <?php 
            $order_info = $conn->query("SELECT * FROM orders WHERE id = " . $_GET['view'] . " AND user_id = " . $_SESSION['user_id'])->fetch_assoc();
            ?>
            
            document.getElementById('orderId').textContent = orderId;
            
            let html = '<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>';
            
            <?php foreach ($order_details as $item): ?>
            html += '<tr>';
            html += '<td><strong><?php echo $item['nama_produk']; ?></strong></td>';
            html += '<td><?php echo formatRupiah($item['harga']); ?></td>';
            html += '<td><?php echo $item['quantity']; ?></td>';
            html += '<td><?php echo formatRupiah($item['harga'] * $item['quantity']); ?></td>';
            html += '</tr>';
            <?php endforeach; ?>
            
            html += '</tbody></table></div>';
            
            html += '<hr>';
            html += '<h6>Informasi Pengiriman:</h6>';
            html += '<p><strong>Penerima:</strong> <?php echo $order_info['nama_penerima']; ?><br>';
            html += '<strong>Alamat:</strong> <?php echo $order_info['alamat']; ?><br>';
            html += '<strong>Telepon:</strong> <?php echo $order_info['telepon']; ?><br>';
            html += '<strong>Email:</strong> <?php echo $order_info['email']; ?></p>';
            
            <?php if ($order_info['catatan']): ?>
            html += '<p><strong>Catatan:</strong> <?php echo $order_info['catatan']; ?></p>';
            <?php endif; ?>
            
            html += '<hr>';
            html += '<div class="d-flex justify-content-between align-items-center">';
            html += '<h5>Total: <?php echo formatRupiah($order_info['total_harga']); ?></h5>';
            html += '<span class="badge bg-<?php 
                echo $order_info['status'] == 'pending' ? 'warning' : 
                     $order_info['status'] == 'processing' ? 'info' : 
                     $order_info['status'] == 'shipped' ? 'primary' : 
                     $order_info['status'] == 'delivered' ? 'success' : 'danger'; 
            ?> status-badge"><?php echo ucfirst($order_info['status']); ?></span>';
            html += '</div>';
            
            document.getElementById('orderDetails').innerHTML = html;
            
            const modal = new bootstrap.Modal(document.getElementById('orderModal'));
            modal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
