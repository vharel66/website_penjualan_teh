<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = cleanInput($_POST['order_id']);
    $status = cleanInput($_POST['status']);
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request!';
        header("Location: orders.php");
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Status pesanan berhasil diupdate!';
    } else {
        $_SESSION['error'] = 'Gagal update status pesanan!';
    }
    
    header("Location: orders.php");
    exit();
}

// Get orders with filtering
$status_filter = $_GET['status'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if ($status_filter && $status_filter !== 'all') {
    $where_clause = "WHERE o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql = "
    SELECT o.*, u.username, u.email as user_email,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = [];
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
    $stmt = $conn->prepare("
        SELECT oi.*, p.nama_produk, p.gambar
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
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
    <title>Kelola Pesanan - TEH TITIS</title>
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
        
        .btn-edit {
            background: #0d6efd;
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
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: none;
        }
        
        .status-badge.pending {
            background: #ffc107;
            color: white;
        }
        
        .status-badge.processing {
            background: #0dcaf0;
            color: white;
        }
        
        .status-badge.shipped {
            background: #0d6efd;
            color: white;
        }
        
        .status-badge.delivered {
            background: #198754;
            color: white;
        }
        
        .status-badge.cancelled {
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
                            <a href="orders.php" class="nav-link active">
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
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Kelola Pesanan</h2>
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="statusFilter" onchange="filterOrders()" style="width: 200px;">
                                <option value="all">Semua Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon primary">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $total_orders = count($orders);
                                echo $total_orders; 
                                ?>
                            </div>
                            <div class="stats-label">Total Pesanan</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon success">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $pending_count = 0;
                                foreach ($orders as $order) {
                                    if ($order['status'] == 'pending') $pending_count++;
                                }
                                echo $pending_count; 
                                ?>
                            </div>
                            <div class="stats-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon info">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $processing_count = 0;
                                foreach ($orders as $order) {
                                    if ($order['status'] == 'processing' || $order['status'] == 'shipped') $processing_count++;
                                }
                                echo $processing_count; 
                                ?>
                            </div>
                            <div class="stats-label">Diproses</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon warning">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stats-number">
                                <?php 
                                $completed_count = 0;
                                foreach ($orders as $order) {
                                    if ($order['status'] == 'delivered') $completed_count++;
                                }
                                echo $completed_count; 
                                ?>
                            </div>
                            <div class="stats-label">Selesai</div>
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

                <!-- Orders Table -->
                <div class="table-card">
                    <div class="card-header">
                        <h3>Daftar Pesanan</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="order-row" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td>
                                            <div>
                                                <strong><?php echo $order['username']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $order['user_email']; ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo formatRupiah($order['total_harga']); ?></td>
                                        <td><?php echo $order['item_count']; ?> item</td>
                                        <td>
                                            <span class="status-badge <?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-modern btn-edit btn-sm" onclick="event.stopPropagation(); updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted">Belum ada pesanan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status Pesanan #<span id="statusOrderId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="order_id" id="order_id_field">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status Pesanan</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterOrders() {
            const status = document.getElementById('statusFilter').value;
            window.location.href = '?status=' + status;
        }
        
        function viewOrder(orderId) {
            window.location.href = '?view=' + orderId;
        }
        
        function updateStatus(orderId, currentStatus) {
            document.getElementById('statusOrderId').textContent = orderId;
            document.getElementById('order_id_field').value = orderId;
            document.getElementById('status').value = currentStatus;
            
            const modal = new bootstrap.Modal(document.getElementById('statusModal'));
            modal.show();
        }
        
        <?php if (isset($_GET['view']) && !empty($order_details)): ?>
        // Auto-show order details
        document.addEventListener('DOMContentLoaded', function() {
            const orderId = <?php echo $_GET['view']; ?>;
            const orderData = <?php echo json_encode($order_details); ?>;
            
            document.getElementById('orderId').textContent = orderId;
            
            let html = '<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>';
            
            <?php 
            $order_info = $conn->query("SELECT * FROM orders WHERE id = " . $_GET['view'])->fetch_assoc();
            ?>
            
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
            html += '<h5>Total: <?php echo formatRupiah($order_info['total_harga']); ?></h5>';
            
            document.getElementById('orderDetails').innerHTML = html;
            
            const modal = new bootstrap.Modal(document.getElementById('orderModal'));
            modal.show();
        });
        <?php endif; ?>
    </script>

</body>
</html>
