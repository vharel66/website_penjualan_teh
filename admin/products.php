<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle AJAX request for getting product data
if (isset($_GET['action']) && $_GET['action'] == 'get_product') {
    $id = cleanInput($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    exit();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request!']);
            exit();
        } else {
            $_SESSION['error'] = 'Invalid request!';
            redirect('products.php');
        }
    }
    
    switch ($action) {
        case 'add':
            $nama_produk = cleanInput($_POST['nama_produk']);
            $deskripsi = cleanInput($_POST['deskripsi']);
            $harga = cleanInput($_POST['harga']);
            $stok = cleanInput($_POST['stok']);
            $kategori = cleanInput($_POST['kategori']);
            
            // Handle file upload
            $gambar = '';
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['gambar'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                $fileType = $file['type'];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!']);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!';
                        break;
                    }
                }
                
                // Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB!']);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Ukuran file maksimal 5MB!';
                        break;
                    }
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = uniqid('product_', true) . '.' . $fileExtension;
                $uploadPath = '../assets/images/' . $uniqueFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    $gambar = $uniqueFileName;
                } else {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar!']);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Gagal mengupload gambar!';
                        break;
                    }
                }
            } elseif (!empty($_POST['gambar'])) {
                // Fallback for manual filename input
                $gambar = cleanInput($_POST['gambar']);
            }
            
            $stmt = $conn->prepare("INSERT INTO products (nama_produk, deskripsi, harga, stok, kategori, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiss", $nama_produk, $deskripsi, $harga, $stok, $kategori, $gambar);
            
            if ($stmt->execute()) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan!']);
                    exit();
                } else {
                    $_SESSION['success'] = 'Produk berhasil ditambahkan!';
                }
            } else {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk!']);
                    exit();
                } else {
                    $_SESSION['error'] = 'Gagal menambahkan produk!';
                }
            }
            break;
            
        case 'edit':
            $id = cleanInput($_POST['id']);
            $nama_produk = cleanInput($_POST['nama_produk']);
            $deskripsi = cleanInput($_POST['deskripsi']);
            $harga = cleanInput($_POST['harga']);
            $stok = cleanInput($_POST['stok']);
            $kategori = cleanInput($_POST['kategori']);
            
            // Handle file upload
            $gambar = '';
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['gambar'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                $fileType = $file['type'];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!']);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!';
                        break;
                    }
                }
                
                // Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB!']);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Ukuran file maksimal 5MB!';
                        break;
                    }
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = uniqid('product_', true) . '.' . $fileExtension;
                $uploadPath = '../assets/images/' . $uniqueFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    $gambar = $uniqueFileName;
                    
                    // Delete old image if exists
                    $stmt = $conn->prepare("SELECT gambar FROM products WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 1) {
                        $oldProduct = $result->fetch_assoc();
                        if ($oldProduct['gambar'] && file_exists('../assets/images/' . $oldProduct['gambar'])) {
                            unlink('../assets/images/' . $oldProduct['gambar']);
                        }
                    }
                } else {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar!']);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Gagal mengupload gambar!';
                        break;
                    }
                }
            } elseif (!empty($_POST['gambar'])) {
                // Keep existing image
                $gambar = cleanInput($_POST['gambar']);
            } else {
                // Get existing image from database
                $stmt = $conn->prepare("SELECT gambar FROM products WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $product = $result->fetch_assoc();
                    $gambar = $product['gambar'];
                }
            }
            
            $stmt = $conn->prepare("UPDATE products SET nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, kategori = ?, gambar = ? WHERE id = ?");
            $stmt->bind_param("ssdissi", $nama_produk, $deskripsi, $harga, $stok, $kategori, $gambar, $id);
            
            if ($stmt->execute()) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate!']);
                    exit();
                } else {
                    $_SESSION['success'] = 'Produk berhasil diupdate!';
                }
            } else {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Gagal update produk!']);
                    exit();
                } else {
                    $_SESSION['error'] = 'Gagal update produk!';
                }
            }
            break;
            
        case 'delete':
            $id = cleanInput($_POST['id']);
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if (isset($_POST['ajax'])) {
                if ($stmt->execute()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus!']);
                    exit();
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Gagal hapus produk!']);
                    exit();
                }
            } else {
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Produk berhasil dihapus!';
                } else {
                    $_SESSION['error'] = 'Gagal hapus produk!';
                }
            }
            break;
    }
    
    redirect('products.php');
}

// Get all products
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = cleanInput($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_product = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - TEH TITIS</title>
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
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
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
                            <a href="products.php" class="nav-link active">
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
                        <h2>Kelola Produk</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                            <i class="fas fa-plus"></i> Tambah Produk
                        </button>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Gambar</th>
                                            <th>Nama Produk</th>
                                            <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <img src="../assets/images/<?php echo $product['gambar']; ?>" class="product-image" alt="<?php echo $product['nama_produk']; ?>">
                                                </td>
                                                <td>
                                                    <strong><?php echo $product['nama_produk']; ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo substr($product['deskripsi'], 0, 50) . '...'; ?></small>
                                                </td>
                                                <td><?php echo $product['kategori']; ?></td>
                                                <td><?php echo formatRupiah($product['harga']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $product['stok'] < 10 ? 'danger' : 'success'; ?>">
                                                        <?php echo $product['stok']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="productForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="ajax" value="1">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_produk" class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kategori" class="form-label">Kategori</label>
                                    <select class="form-control" id="kategori" name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Teh Hijau">Teh Hijau</option>
                                        <option value="Teh Merah">Teh Merah</option>
                                        <option value="Teh Hitam">Teh Hitam</option>
                                        <option value="Teh Aroma">Teh Aroma</option>
                                        <option value="Teh Herbal">Teh Herbal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga (Rp)</label>
                                    <input type="number" class="form-control" id="harga" name="harga" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stok" class="form-label">Stok</label>
                                    <input type="number" class="form-control" id="stok" name="stok" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="gambar" class="form-label">Gambar Produk</label>
                                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*" onchange="previewImage(event)">
                                    <input type="hidden" id="gambar_name" name="gambar">
                                    <small class="text-muted">Pilih file gambar untuk produk</small>
                                    
                                    <!-- Image Preview -->
                                    <div id="imagePreview" class="mt-2" style="display: none;">
                                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        <br>
                                        <small class="text-muted">Preview gambar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(id) {
            // Load product data via AJAX
            fetch('products.php?action=get_product&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fill form with product data
                        document.getElementById('modalTitle').textContent = 'Edit Produk';
                        document.getElementById('formAction').value = 'edit';
                        document.getElementById('productId').value = data.product.id;
                        document.getElementById('nama_produk').value = data.product.nama_produk;
                        document.getElementById('kategori').value = data.product.kategori;
                        document.getElementById('deskripsi').value = data.product.deskripsi;
                        document.getElementById('harga').value = data.product.harga;
                        document.getElementById('stok').value = data.product.stok;
                        
                        // Show current image preview
                        if (data.product.gambar) {
                            const preview = document.getElementById('imagePreview');
                            const previewImg = document.getElementById('previewImg');
                            const hiddenInput = document.getElementById('gambar_name');
                            
                            previewImg.src = '../assets/images/' + data.product.gambar;
                            preview.style.display = 'block';
                            hiddenInput.value = data.product.gambar;
                        }
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('productModal'));
                        modal.show();
                    } else {
                        alert('Gagal memuat data produk: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data');
                });
        }
        
        // Handle form submission with AJAX
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                    modal.hide();
                    
                    // Reset form
                    this.reset();
                    document.getElementById('modalTitle').textContent = 'Tambah Produk';
                    document.getElementById('formAction').value = 'add';
                    document.getElementById('productId').value = '';
                    
                    // Hide image preview
                    document.getElementById('imagePreview').style.display = 'none';
                    
                    // Show success message
                    alert(data.message);
                    
                    // Reload page to show updated data
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data');
            });
        });
        
        function deleteProduct(id) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
                formData.append('action', 'delete');
                formData.append('id', id);
                formData.append('ajax', '1');
                
                fetch('products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(data.message);
                        // Reload page to show updated data
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus produk');
                });
            }
        }
        
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const hiddenInput = document.getElementById('gambar_name');
            
            if (file) {
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
                
                // Set filename to hidden input
                hiddenInput.value = file.name;
            } else {
                preview.style.display = 'none';
                hiddenInput.value = '';
            }
        }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
