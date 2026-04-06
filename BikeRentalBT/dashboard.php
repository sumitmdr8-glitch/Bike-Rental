<?php
include 'config.php';
if(!isset($_SESSION['user'])){
  header('Location: login.php');
  exit;
}

$email = $_SESSION['user'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$u_result = mysqli_stmt_get_result($stmt);
$u = mysqli_fetch_assoc($u_result);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
<div class="user-dashboard">
  <aside class="user-sidebar">
    <h3>👤 User Panel</h3>
    <nav>
      <a href="dashboard.php" class="nav-link active">Dashboard</a>
      <a href="my_profile.php" class="nav-link">My Profile</a>
    </nav>
    <div class="user-info-footer">
      <strong><?= htmlspecialchars($u['name']) ?></strong>
      <?= htmlspecialchars($u['email']) ?><br>
      <a href="logout.php" class="small-link">Logout</a>
    </div>
  </aside>

  <main class="user-main">
    <div class="user-header">
      <h2>Welcome, <?= htmlspecialchars($u['name']) ?></h2>
      <p class="subtitle">Manage your bookings and explore bikes to rent</p>
      <?php if(isset($_GET['book']) && $_GET['book'] === 'success'): ?>
        <div class="success" id="flash-msg">Booking requested — admin will confirm it shortly.</div>
        <script>history.replaceState({}, document.title, window.location.pathname);</script>
      <?php endif; ?>
      <?php if(isset($_GET['cancel'])): ?>
        <?php if($_GET['cancel']==='success'): ?>
          <div class="success" id="flash-msg">Booking cancelled.</div>
        <?php elseif($_GET['cancel']==='already'): ?>
          <div class="error" id="flash-msg">Booking was already cancelled.</div>
        <?php elseif($_GET['cancel']==='done'): ?>
          <div class="error" id="flash-msg">Cannot cancel booking that has been marked as done by admin.</div>
        <?php elseif($_GET['cancel']==='expired'): ?>
          <div class="error" id="flash-msg">Cannot cancel booking after the end date has passed.</div>
        <?php else: ?>
          <div class="error" id="flash-msg">Unable to cancel booking.</div>
        <?php endif; ?>
        <script>history.replaceState({}, document.title, window.location.pathname);</script>
      <?php endif; ?>
    </div>
  <?php

  // stats
  $uid = (int)$u['id'];
  $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE user_id = ?");
  mysqli_stmt_bind_param($stmt, 'i', $uid);
  mysqli_stmt_execute($stmt);
  $total = mysqli_stmt_get_result($stmt);
  $total = (int)mysqli_fetch_row($total)[0];
  mysqli_stmt_close($stmt);

  $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = ?");
  $status = 'confirmed';
  mysqli_stmt_bind_param($stmt, 'is', $uid, $status);
  mysqli_stmt_execute($stmt);
  $confirmed = mysqli_stmt_get_result($stmt);
  $confirmed = (int)mysqli_fetch_row($confirmed)[0];
  mysqli_stmt_close($stmt);

  $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = ?");
  $status = 'pending';
  mysqli_stmt_bind_param($stmt, 'is', $uid, $status);
  mysqli_stmt_execute($stmt);
  $pending = mysqli_stmt_get_result($stmt);
  $pending = (int)mysqli_fetch_row($pending)[0];
  mysqli_stmt_close($stmt);
  ?>

    <div class="stats-grid">
      <a href="my_profile.php" class="stat-card-link">
        <div class="stat-card">
          <div class="num"><?= $total ?></div>
          <div class="label">Total bookings</div>
        </div>
      </a>
      <a href="my_profile.php" class="stat-card-link">
        <div class="stat-card">
          <div class="num"><?= $confirmed ?></div>
          <div class="label">Confirmed</div>
        </div>
      </a>
      <a href="my_profile.php" class="stat-card-link">
        <div class="stat-card">
          <div class="num"><?= $pending ?></div>
          <div class="label">Pending</div>
        </div>
      </a>
    </div>

    <div class="content-card">
      <h2>Your Current Bookings</h2>
<?php

// Only show current bookings (not past, not cancelled, not done, not denied)
$current_date = date('Y-m-d');
$stmt = mysqli_prepare($conn, "SELECT bookings.*, bikes.brand, bikes.price FROM bookings JOIN bikes ON bookings.bike_id = bikes.id WHERE bookings.user_id = ? AND bookings.date_to >= ? AND bookings.status NOT IN ('cancelled', 'done', 'denied') ORDER BY bookings.date_from ASC");
mysqli_stmt_bind_param($stmt, 'is', $uid, $current_date);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);
  if(mysqli_num_rows($res) === 0){

    echo '<p class="muted">You have no current active bookings. Browse our bikes to request one.</p>';
  } else {
    echo '<ul class="bookings-list">';
    while($r = mysqli_fetch_assoc($res)){
      $status = htmlspecialchars($r['status']);
      $badge = '<span class="badge '.($r['status']=='pending'?'pending':($r['status']=='confirmed'?'confirmed':'done')).'">'. $status .'</span>';
      $from = new DateTime($r['date_from']);
      $to = new DateTime($r['date_to']);
      $days = $from->diff($to)->days + 1;
      $total = $days * (int)$r['price'];
      echo '<li class="booking-item">';
      echo '<div class="booking-meta"><div class="title">'.htmlspecialchars($r['brand']).' '.$badge.'</div>';
      echo '<div class="dates">'.htmlspecialchars($r['date_from']).' → '.htmlspecialchars($r['date_to']).' (' . $days . ' days)</div>';
      echo '<div class="amount" style="color: #666; font-size: 14px; margin-top: 5px;"><strong>Total: NPR ' . number_format($total) . '</strong></div></div>';

      // Only show cancel button for pending and confirmed bookings
      if($r['status'] === 'pending' || $r['status'] === 'confirmed'){
        echo '<div><form style="display:inline" method="POST" action="cancel_booking.php"><input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '"><input type="hidden" name="booking_id" value="'.(int)$r['id'].'"><button class="btn small" type="submit" name="cancel">Cancel</button></form></div>';
      }
      echo '</li>';
    }
    echo '</ul>';
  }
?>

    </div>

    <div class="content-card available-bikes">
      <h2>Bikes to Rent</h2>
      <p class="muted">Browse and book available bikes</p>
      <div class="bikes-grid">
        <?php
        $bikes_res = mysqli_query($conn, "SELECT * FROM bikes");
        while($bike = mysqli_fetch_assoc($bikes_res)){
        ?>
        <div class="bike-card">
          <img src="<?= htmlspecialchars($bike['image']) ?>" alt="<?= htmlspecialchars($bike['brand']) ?>">
          <h3><?= htmlspecialchars($bike['brand']) ?></h3>
          <p class="description"><?= htmlspecialchars($bike['description'] ?? '') ?></p>
          <p>NPR <?= htmlspecialchars($bike['price']) ?> / day</p>
          <a class="btn" href="book_bike.php?bike=<?= (int)$bike['id'] ?>">Book Now</a>
        </div>
        <?php } ?>
      </div>
    </div>
  </main>
</div>
</body>
</html>
