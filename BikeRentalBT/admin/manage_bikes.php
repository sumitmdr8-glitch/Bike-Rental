<?php
include '../config.php';
if(!isset($_SESSION['admin'])){
	header('Location: admin_login.php');
	exit;
}

// helper for upload
function saveImage($file){
	$allowed = ['image/jpeg','image/png','image/webp'];
	if(!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
	if(!in_array($file['type'], $allowed)) return null;
	$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
	$name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
	$destDir = __DIR__ . '/../assets/images/';
	if(!is_dir($destDir)) mkdir($destDir, 0755, true);
	$dest = $destDir . $name;
	if(move_uploaded_file($file['tmp_name'], $dest)){
		return 'assets/images/' . $name;
	}
	return null;
}

// Add bike
if(isset($_POST['add_bike'])){
	$brand = trim($_POST['brand']);
	$description = trim($_POST['description']);
	$price = (int)$_POST['price'];
	if($price < 0){
		echo "<script>alert('Price cannot be negative'); window.location='manage_bikes.php';</script>";
		exit;
	}
	$img = saveImage($_FILES['image']);
	$stmt = mysqli_prepare($conn, "INSERT INTO bikes (brand, description, price, image) VALUES (?,?,?,?)");
	mysqli_stmt_bind_param($stmt, 'ssis', $brand, $description, $price, $img);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);

	// Append to SQL file for data persistence
	$sql_file = __DIR__ . '/../sql/bikerentalbt.sql';
	$insert_sql = "INSERT INTO bikes (brand, description, price, image) VALUES ('" . mysqli_real_escape_string($conn, $brand) . "','" . mysqli_real_escape_string($conn, $description) . "'," . $price . ",'" . mysqli_real_escape_string($conn, $img) . "');\n";
	file_put_contents($sql_file, $insert_sql, FILE_APPEND);

	header('Location: manage_bikes.php');
	exit;
}

// Edit bike
if(isset($_POST['edit_bike'])){
	$id = (int)$_POST['id'];
	$brand = trim($_POST['brand']);
	$description = trim($_POST['description']);
	$price = (int)$_POST['price'];
	if($price < 0){
		echo "<script>alert('Price cannot be negative'); window.location='manage_bikes.php?edit=" . $id . "';</script>";
		exit;
	}
	$img = null;
	if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
		$img = saveImage($_FILES['image']);
	}
	if($img){
		$stmt = mysqli_prepare($conn, "UPDATE bikes SET brand=?, description=?, price=?, image=? WHERE id=?");
		mysqli_stmt_bind_param($stmt, 'ssisi', $brand, $description, $price, $img, $id);
	} else {
		$stmt = mysqli_prepare($conn, "UPDATE bikes SET brand=?, description=?, price=? WHERE id=?");
		mysqli_stmt_bind_param($stmt, 'ssii', $brand, $description, $price, $id);
	}
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);

	// Append to SQL file
	$sql_file = __DIR__ . '/../sql/bikerentalbt.sql';
	if($img){
		$update_sql = "UPDATE bikes SET brand='" . mysqli_real_escape_string($conn, $brand) . "', description='" . mysqli_real_escape_string($conn, $description) . "', price=" . $price . ", image='" . mysqli_real_escape_string($conn, $img) . "' WHERE id=" . $id . ";\n";
	} else {
		$update_sql = "UPDATE bikes SET brand='" . mysqli_real_escape_string($conn, $brand) . "', description='" . mysqli_real_escape_string($conn, $description) . "', price=" . $price . " WHERE id=" . $id . ";\n";
	}
	file_put_contents($sql_file, $update_sql, FILE_APPEND);

	header('Location: manage_bikes.php');
	exit;
}

// Delete bike
if(isset($_GET['delete'])){
	$id = (int)$_GET['delete'];
	// optionally delete image file
	$r = mysqli_prepare($conn, "SELECT image FROM bikes WHERE id = ?");
	mysqli_stmt_bind_param($r, 'i', $id);
	mysqli_stmt_execute($r);
	mysqli_stmt_bind_result($r, $img_path);
	if(mysqli_stmt_fetch($r)){
		$img_file = __DIR__ . '/../' . $img_path;
		if($img_path && file_exists($img_file)){
			@unlink($img_file);
		}
	}
	mysqli_stmt_close($r);
	$stmt = mysqli_prepare($conn, "DELETE FROM bikes WHERE id = ?");
	mysqli_stmt_bind_param($stmt, 'i', $id);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);

	// Append to SQL file
	$sql_file = __DIR__ . '/../sql/bikerentalbt.sql';
	$delete_sql = "DELETE FROM bikes WHERE id = " . $id . ";\n";
	file_put_contents($sql_file, $delete_sql, FILE_APPEND);

	header('Location: manage_bikes.php');
	exit;
}

// fetch for edit
$editBike = null;
if(isset($_GET['edit'])){
	$id = (int)$_GET['edit'];
	$res = mysqli_prepare($conn, "SELECT * FROM bikes WHERE id = ?");
	mysqli_stmt_bind_param($res, 'i', $id);
	mysqli_stmt_execute($res);
	$editBike = mysqli_fetch_assoc(mysqli_stmt_get_result($res));
	mysqli_stmt_close($res);
}

$res = mysqli_query($conn, "SELECT * FROM bikes ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="stylesheet" href="../assets/css/style.css">
	<style>
		table{width:100%;border-collapse:collapse}
		th,td{padding:8px;border:1px solid #ddd;text-align:left}
		img.thumb{width:120px;height:70px;object-fit:cover;border-radius:6px}
		form.inline{display:flex;gap:8px;align-items:center}
		.form-row{margin:10px 0}
	</style>
</head>
<body>

<div class="admin-container">
  <aside class="sidebar">
    <h3>Admin Panel</h3>
    <nav>
      <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
      <a href="manage_bikes.php" class="nav-link active">Manage Bikes</a>
      <a href="manage_bookings.php" class="nav-link">Manage Bookings</a>
      <a href="manage_users.php" class="nav-link">Manage Users</a>
    </nav>
    <div style="margin-top:14px;border-top:1px solid #f1f5f9;padding-top:12px;color:#486581">Logged in as<br><strong><?= htmlspecialchars($_SESSION['admin']) ?></strong><br><a class="small-link" href="../logout.php">Logout</a></div>
  </aside>

  <main class="main">
    <div class="admin-header">
      <div>
        <h2 class="admin-title">Manage Bikes</h2>
        <div style="color:#486581">Add, edit, and manage bike inventory</div>
      </div>
    </div>

    <div class="card">
	<?php if($editBike): ?>
		<h3>Edit Bike #<?= (int)$editBike['id'] ?></h3>
		<form id="bikeForm" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="id" value="<?= (int)$editBike['id'] ?>">
			<div class="form-row">
				Brand: <input id="brand" name="brand" value="<?= htmlspecialchars($editBike['brand']) ?>" required>
				<span id="brand-error" class="live-error" style="display:none;color:#b00020;font-size:13px;margin-top:4px;"></span>
			</div>
			<div class="form-row">
				Description: <textarea id="description" name="description" rows="3" required><?= htmlspecialchars($editBike['description'] ?? '') ?></textarea>
				<span id="description-error" class="live-error" style="display:none;color:#b00020;font-size:13px;margin-top:4px;"></span>
			</div>
			<div class="form-row">
				Price: <input id="price" name="price" type="number" value="<?= (int)$editBike['price'] ?>" required>
				<span id="price-error" class="live-error" style="display:none;color:#b00020;font-size:13px;margin-top:4px;"></span>
			</div>
			<div class="form-row">Image: <input type="file" name="image"></div>
			<div class="form-row"><button name="edit_bike">Save Changes</button> <a href="manage_bikes.php">Cancel</a></div>
		</form>
	<?php else: ?>
		<h3>Add New Bike</h3>
		<form id="bikeForm" method="POST" enctype="multipart/form-data">
			<div class="form-row">
				Brand: <input id="brand" name="brand" required>
				<span id="brand-error" class="live-error" style="display:none;color:#b00020;font-size:13px;margin-top:4px;"></span>
			</div>
			<div class="form-row">
				Description: <textarea id="description" name="description" rows="3" required></textarea>
				<span id="description-error" class="live-error" style="display:none;color:#b00020;font-size:13px;margin-top:4px;"></span>
			</div>
			<div class="form-row">
				Price: <input id="price" name="price" type="number" min="0" required>
				<span id="price-error" class="live-error" style="display:none;color:#b00020;font-size:13px;margin-top:4px;"></span>
			</div>
			<div class="form-row">Image: <input type="file" name="image" required></div>
			<div class="form-row"><button name="add_bike">Add Bike</button></div>
		</form>
	<?php endif; ?>

	<h3>Existing Bikes</h3>
	<table>
		<tr><th>#</th><th>Image</th><th>Brand</th><th>Description</th><th>Price</th><th>Actions</th></tr>
		<?php $sn = 1; while($row = mysqli_fetch_assoc($res)): ?>
			<tr>
				<td><?= $sn++ ?></td>
				<td><?php if($row['image']): ?><img class="thumb" src="../<?= htmlspecialchars($row['image']) ?>" alt="Bike image"><?php endif; ?></td>
				<td><?= htmlspecialchars($row['brand']) ?></td>
				<td style="max-width:200px;font-size:13px;"><?= htmlspecialchars($row['description'] ?? 'No description') ?></td>
				<td>NPR <?= htmlspecialchars($row['price']) ?></td>
				<td>
					<a href="?edit=<?= (int)$row['id'] ?>">Edit</a> |
					<a href="?delete=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this bike?')">Delete</a>
				</td>
			</tr>
		<?php endwhile; ?>
	</table>
    </div>
  </main>
</div>

<script>
// Live validation functions
function showError(fieldId, message) {
  var errorEl = document.getElementById(fieldId + '-error');
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.style.display = 'block';
  }
}

function hideError(fieldId) {
  var errorEl = document.getElementById(fieldId + '-error');
  if (errorEl) {
    errorEl.style.display = 'none';
  }
}

function validateBrand() {
  var brand = document.getElementById('brand').value.trim();
  if (!brand) {
    showError('brand', 'Brand name is required.');
    return false;
  } else if (brand.length < 2) {
    showError('brand', 'Brand name must be at least 2 characters.');
    return false;
  } else if (/^[0-9]+$/.test(brand)) {
    showError('brand', 'Brand name cannot be only numbers.');
    return false;
  } else {
    hideError('brand');
    return true;
  }
}

function validatePrice() {
  var price = document.getElementById('price').value;
  if (!price && price !== '0') {
    showError('price', 'Price is required.');
    return false;
  } else if (parseInt(price) < 0) {
    showError('price', 'Price cannot be negative.');
    return false;
  } else if (parseInt(price) > 100000) {
    showError('price', 'Price seems too high. Maximum is 100,000 NPR.');
    return false;
  } else {
    hideError('price');
    return true;
  }
}

// Add live event listeners
var brandInput = document.getElementById('brand');
var priceInput = document.getElementById('price');

if (brandInput) {
  brandInput.addEventListener('input', validateBrand);
}
if (priceInput) {
  priceInput.addEventListener('input', validatePrice);
}

// Form submit validation
var bikeForm = document.getElementById('bikeForm');
if (bikeForm) {
  bikeForm.addEventListener('submit', function(e) {
    var isBrandValid = validateBrand();
    var isPriceValid = validatePrice();
    
    if (!isBrandValid || !isPriceValid) {
      e.preventDefault();
      alert('Please fix the errors before submitting.');
    }
  });
}

// highlight nav
var links = document.querySelectorAll('.sidebar .nav-link');
links.forEach(function(a){ if(a.getAttribute('href') === window.location.pathname.split('/').pop()){ a.classList.add('active'); } });
</script>

</body>
</html>

