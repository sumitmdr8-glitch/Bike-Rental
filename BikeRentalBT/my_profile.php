<?php
include 'config.php';

if(!isset($_SESSION['user'])){
  header('Location: login.php');
  exit;
}

$user_email = $_SESSION['user'];
$error = '';
$success = '';
$info = '';

// Check for URL parameters
if(isset($_GET['success'])){
  $success = 'Profile updated successfully!';
}
if(isset($_GET['info'])){
  $info = 'No changes were made to your profile.';
}

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $user_email);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

if(!$user){
  header('Location: logout.php');
  exit;
}

$user_id = (int)$user['id'];

// Handle profile update
if(isset($_POST['update_profile'])){
  $name = trim($_POST['name'] ?? '');
  $new_email = trim($_POST['email'] ?? '');
  $contact = trim($_POST['contact_number'] ?? '');
  
  // Validation
  if($name === '' || $new_email === '' || $contact === ''){
    $error = 'Please fill all required fields.';
  } elseif(!preg_match('/^[a-zA-Z\s]+$/', $name)){
    $error = 'Name must not contain numbers.';
  } elseif(!filter_var($new_email, FILTER_VALIDATE_EMAIL)){
    $error = 'Invalid email address.';
  } elseif(!preg_match('/^[0-9]{10}$/', $contact)){
    $error = 'Contact number must be exactly 10 digits.';
  } else {
    // Check if email already exists (if changed)
    if($new_email !== $user_email){
      $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
      mysqli_stmt_bind_param($check_stmt, 'si', $new_email, $user_id);
      mysqli_stmt_execute($check_stmt);
      mysqli_stmt_store_result($check_stmt);
      if(mysqli_stmt_num_rows($check_stmt) > 0){
        $error = 'Email already registered by another user.';
      }
      mysqli_stmt_close($check_stmt);
    }
    
    if($error === ''){
      // Handle document upload
      $docname = $user['document'];
      if(isset($_FILES['document']) && $_FILES['document']['error'] === 0){
        $allowed = ['application/pdf','image/jpeg','image/png'];
        $type = $_FILES['document']['type'];
        if(!in_array($type, $allowed)){
          $error = 'Invalid document type (PDF/JPEG/PNG).';
        } elseif($_FILES['document']['size'] > 2 * 1024 * 1024){
          $error = 'Document too large (max 2MB).';
        } else {
          $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
          $docname = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $target = __DIR__ . '/uploads/' . $docname;
          if(!move_uploaded_file($_FILES['document']['tmp_name'], $target)){
            $error = 'Document upload failed.';
          }
        }
      }
    }
    
    // Check if any changes were made
    if($name === $user['name'] && $new_email === $user_email && $contact === $user['contact_number'] && $docname === $user['document']){
      $info = 'No changes were made to your profile.';
      header('Location: my_profile.php?info=1');
      exit;
    } else {
      // Update user data - password change disabled
      $update_stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, contact_number = ?, document = ? WHERE id = ?");
      mysqli_stmt_bind_param($update_stmt, 'ssssi', $name, $new_email, $contact, $docname, $user_id);
      
      if(mysqli_stmt_execute($update_stmt)){
        mysqli_stmt_close($update_stmt);
        
        // Update session if email changed
        if($new_email !== $user_email){
          $_SESSION['user'] = $new_email;
        }
        
        // Refresh user data
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $user_result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($user_result);
        mysqli_stmt_close($stmt);
        $user_email = $user['email'];
        
        $success = 'Profile updated successfully!';
      } else {
        $error = 'Failed to update profile. Please try again.';
      }
    }
  }
}

// Fetch all user bookings (past and present)
$bookings_stmt = mysqli_prepare($conn, "SELECT bookings.*, bikes.brand, bikes.price 
  FROM bookings 
  JOIN bikes ON bookings.bike_id = bikes.id 
  WHERE bookings.user_id = ? 
  ORDER BY bookings.date_from DESC");
mysqli_stmt_bind_param($bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);
$bookings = [];
while($booking = mysqli_fetch_assoc($bookings_result)){
  $bookings[] = $booking;
}
mysqli_stmt_close($bookings_stmt);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Profile - BikeRentalBT</title>
  <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
  <div class="profile-container">
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <div class="profile-header">
      <h1>My Profile</h1>
      <div>
        <span class="welcome-text">Welcome, <span class="user-name"><?= htmlspecialchars($user['name']) ?></span> 👋</span>
      </div>
    </div>
    
    <?php if($error): ?>
      <div class="message error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
      <div class="message success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if($info): ?>
      <div class="message info-message"><?= htmlspecialchars($info) ?></div>
    <?php endif; ?>
    
    <!-- Profile View Section -->
    <div class="profile-section" id="profileView">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0;">👤 My Details</h3>
        <button onclick="showEditForm()" class="btn-primary" style="padding:10px 25px; font-size:0.9rem;">✏️ Edit Profile</button>
      </div>
      <div class="form-grid" style="pointer-events:none; opacity:0.8;">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly style="background:#f0f0f0;">
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background:#f0f0f0;">
        </div>
        <div class="form-group">
          <label>Contact Number</label>
          <input type="tel" value="<?= htmlspecialchars($user['contact_number']) ?>" readonly style="background:#f0f0f0;">
        </div>
        <div class="form-group">
          <label>Document</label>
          <?php if($user['document']): ?>
            <?php if(strpos($user['document'], '.pdf') !== false): ?>
              <a href="uploads/<?= htmlspecialchars($user['document']) ?>" target="_blank" class="document-link" style="pointer-events:auto;">📄 View Document</a>
            <?php else: ?>
              <img src="uploads/<?= htmlspecialchars($user['document']) ?>" alt="Document" class="document-preview">
            <?php endif; ?>
          <?php else: ?>
            <p style="color: #888; margin: 10px 0;">📂 No document uploaded</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Profile Edit Section (Hidden by default) -->
    <div class="profile-section" id="profileEdit" style="display:none;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0;">✏️ Edit Profile Information</h3>
        <button onclick="showViewForm()" class="btn-primary" style="padding:10px 25px; font-size:0.9rem; background:linear-gradient(135deg, #666 0%, #999 100%);">← Cancel</button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="profileForm">
        <div class="form-grid">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="tel" id="contact_number" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" required maxlength="10">
          </div>
          
          <div class="form-group">
            <label>Current Document</label>
            <?php if($user['document']): ?>
              <?php if(strpos($user['document'], '.pdf') !== false): ?>
                <a href="uploads/<?= htmlspecialchars($user['document']) ?>" target="_blank" class="document-link">📄 View Current Document</a>
              <?php else: ?>
                <img src="uploads/<?= htmlspecialchars($user['document']) ?>" alt="Document" class="document-preview">
              <?php endif; ?>
            <?php else: ?>
              <p style="color: #888; margin: 10px 0;">📂 No document uploaded</p>
            <?php endif; ?>
            <input type="file" name="document" accept="application/pdf,image/jpeg,image/png" style="margin-top: 15px;">
            <small>Upload new document to replace (PDF/JPEG/PNG, max 2MB)</small>
          </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
          <button type="submit" name="update_profile" class="btn-primary">💾 Update Profile</button>
        </div>
      </form>
    </div>
    
    <!-- Booking History Section -->
    <div class="profile-section">
      <h3>My Booking History</h3>
      <div class="booking-history">
        <?php if(empty($bookings)): ?>
          <div class="no-bookings">
            <p>🚴 You haven't made any bookings yet.</p>
            <a href="dashboard.php" class="btn-primary" style="text-decoration: none; display: inline-block; margin-top: 15px;">Browse Bikes</a>
          </div>
        <?php else: ?>
          <?php foreach($bookings as $booking): 
            $from = new DateTime($booking['date_from']);
            $to = new DateTime($booking['date_to']);
            $days = $from->diff($to)->days + 1;
            $total = $days * (int)$booking['price'];
            
            $status_class = 'status-' . $booking['status'];
            $status_text = ucfirst($booking['status']);
          ?>
            <div class="booking-item">
              <div class="booking-info">
                <div class="bike-name"><?= htmlspecialchars($booking['brand']) ?></div>
                <div class="dates"><?= htmlspecialchars($booking['date_from']) ?> → <?= htmlspecialchars($booking['date_to']) ?> (<?= $days ?> days)</div>
                <div class="amount">Total: NPR <?= number_format($total) ?></div>
              </div>
              <div class="booking-status <?= $status_class ?>"><?= $status_text ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script>
    // Toggle between view and edit forms
    function showEditForm() {
      document.getElementById('profileView').style.display = 'none';
      document.getElementById('profileEdit').style.display = 'block';
    }
    function showViewForm() {
      document.getElementById('profileEdit').style.display = 'none';
      document.getElementById('profileView').style.display = 'block';
    }
    function showError(fieldId, message) {
      var field = document.getElementById(fieldId);
      var errorEl = field.parentNode.querySelector('.live-error');
      if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'live-error';
        errorEl.style.cssText = 'color:#b00020;font-size:13px;margin-top:4px;display:block;';
        field.parentNode.appendChild(errorEl);
      }
      errorEl.textContent = message;
    }
    
    function hideError(fieldId) {
      var field = document.getElementById(fieldId);
      var errorEl = field.parentNode.querySelector('.live-error');
      if (errorEl) errorEl.textContent = '';
    }
    
    // Name validation
    document.getElementById('name').addEventListener('input', function() {
      var value = this.value.trim();
      if (!value) {
        showError('name', 'Name is required.');
      } else if (/[0-9]/.test(value)) {
        showError('name', 'Name must not contain numbers.');
      } else if (value.length < 2) {
        showError('name', 'Name must be at least 2 characters.');
      } else {
        hideError('name');
      }
    });
    
    // Email validation
    document.getElementById('email').addEventListener('input', function() {
      var value = this.value.trim();
      var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!value) {
        showError('email', 'Email is required.');
      } else if (!emailRe.test(value)) {
        showError('email', 'Please enter a valid email address.');
      } else {
        hideError('email');
      }
    });
    
    // Contact number validation and limit
    document.getElementById('contact_number').addEventListener('input', function() {
      var value = this.value;
      // Remove non-digits
      var digitsOnly = value.replace(/[^0-9]/g, '');
      // Limit to 10 digits
      if (digitsOnly.length > 10) {
        digitsOnly = digitsOnly.substring(0, 10);
      }
      this.value = digitsOnly;
      
      if (!digitsOnly) {
        showError('contact_number', 'Contact number is required.');
      } else if (digitsOnly.length !== 10) {
        showError('contact_number', 'Contact number must be exactly 10 digits.');
      } else {
        hideError('contact_number');
      }
    });
    
    // Form submit validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
      var isValid = true;
      
      // Validate name
      var name = document.getElementById('name').value.trim();
      if (!name || /[0-9]/.test(name) || name.length < 2) {
        isValid = false;
      }
      
      // Validate email
      var email = document.getElementById('email').value.trim();
      var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email || !emailRe.test(email)) {
        isValid = false;
      }
      
      // Validate contact
      var contact = document.getElementById('contact_number').value;
      if (!contact || contact.length !== 10) {
        isValid = false;
      }
      
      if (!isValid) {
        e.preventDefault();
        alert('Please fix the errors before submitting.');
      }
    });
    // Clear URL parameters after showing messages
    if (window.location.search.includes('success=') || window.location.search.includes('info=')) {
      const url = new URL(window.location);
      url.searchParams.delete('success');
      url.searchParams.delete('info');
      window.history.replaceState({}, '', url);
    }
  </script>
</body>
</html>
