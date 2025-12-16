<style>
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
</style>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="assets/images/logo.jpg" alt="TEH TITIS Logo" height="50"> TEH TITIS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (!isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Beranda</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link active" href="products.php">Produk</a>
                </li>
                <?php if (!isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">Tentang</a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn() && !isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Keranjang
                        <?php 
                        // Get cart count
                        $cart_count = 0;
                        $user_id = $_SESSION['user_id'];
                        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $cart_count = $row['total'] ?? 0;
                        if ($cart_count > 0) echo "<span class='badge bg-danger ms-1'>$cart_count</span>";
                        ?>
                    </a>
                </li>
                <?php elseif (!isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Keranjang
                        <?php 
                        // Get cart count
                        $cart_count = 0;
                        $session_id = session_id();
                        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
                        $stmt->bind_param("s", $session_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $cart_count = $row['total'] ?? 0;
                        if ($cart_count > 0) echo "<span class='badge bg-danger ms-1'>$cart_count</span>";
                        ?>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Panel</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/products.php">Kelola Produk</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/orders.php">Lihat Pesanan</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/users.php">Kelola User</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#" onclick="showAccountModal()">Akun</a></li>
                            <li><a class="dropdown-item" href="orders.php">Pesanan Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus"></i> Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
