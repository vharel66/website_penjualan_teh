<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_harga) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'];

// Recent orders
$recent_orders = [];
$result = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Low stock products
$low_stock = [];
$result = $conn->query("SELECT * FROM products WHERE stok < 10 ORDER BY stok ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TEH TITIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
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
                            <a href="dashboard.php" class="nav-link active">
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
                            <a href="users.php" class="nav-link">
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
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Admin Dashboard</h2>
                        <div class="text-muted">
                            Selamat datang, <strong><?php echo $_SESSION['username']; ?></strong>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card p-3">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-primary text-white me-3">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_products; ?></h3>
                                        <p class="text-muted mb-0">Total Produk</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card p-3">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-success text-white me-3">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_orders; ?></h3>
                                        <p class="text-muted mb-0">Total Pesanan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card p-3">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-info text-white me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $total_users; ?></h3>
                                        <p class="text-muted mb-0">Total User</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card p-3">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-warning text-white me-3">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo formatRupiah($total_revenue); ?></h3>
                                        <p class="text-muted mb-0">Total Revenue</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Orders -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> Pesanan Terbaru</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_orders)): ?>
                                        <p class="text-muted">Belum ada pesanan.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>User</th>
                                                        <th>Total</th>
                                                        <th>Status</th>
                                                        <th>Tanggal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_orders as $order): ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo $order['username']; ?></td>
                                                            <td><?php echo formatRupiah($order['total_harga']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $order['status'] == 'pending' ? 'warning' : 
                                                                         $order['status'] == 'processing' ? 'info' : 
                                                                         $order['status'] == 'shipped' ? 'primary' : 
                                                                         $order['status'] == 'delivered' ? 'success' : 'danger'; 
                                                                ?>">
                                                                    <?php echo ucfirst($order['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Low Stock Alert -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Stok Menipis</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($low_stock)): ?>
                                        <p class="text-muted">Semua produk stok aman.</p>
                                    <?php else: ?>
                                        <?php foreach ($low_stock as $product): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <strong><?php echo $product['nama_produk']; ?></strong>
                                                    <br>
                                                    <small class="text-muted">Stok: <?php echo $product['stok']; ?></small>
                                                </div>
                                                <span class="badge bg-danger">Low</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
