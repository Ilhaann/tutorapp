<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-3 col-lg-2 sidebar">
    <div class="sidebar-header">
        <!-- Logo: replace src with your actual logo path if available -->
        <img src="../assets/images/logo.png" alt="Logo" style="max-width:120px; max-height:60px; display:block; margin:0 auto 10px;" onerror="this.style.display='none'">
        <h4 style="color:black;"><?php echo APP_NAME; ?></h4>
    </div>
    <div class="sidebar-profile">
        <div class="avatar-container">
            <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $_SESSION['user_id']; ?>.jpg" 
                 onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                 class="rounded-circle"
                 alt="Profile Picture">
        </div>
        <h5 style="color:black;"><?php echo htmlspecialchars(($user['first_name'] ?? $user['name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h5>
        <p style="color:black;">Tutee</p>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php" style="color:black;">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php" style="color:black;">
            <i class="bi bi-person"></i> Profile
        </a>
        <a class="nav-link <?php echo $current_page === 'my_sessions.php' ? 'active' : ''; ?>" href="my_sessions.php" style="color:black;">
            <i class="bi bi-calendar"></i> My Sessions
        </a>
        <a class="nav-link <?php echo $current_page === 'tutors.php' ? 'active' : ''; ?>" href="tutors.php" style="color:black;">
            <i class="bi bi-search"></i> Find Tutors
        </a>
        <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" href="messages.php" style="color:black;">
            <i class="bi bi-envelope"></i> Messages
        </a>
        <a class="nav-link <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>" href="payments.php" style="color:black;">
            <i class="bi bi-wallet"></i> Payments
        </a>
    </nav>
</div>

<style>
.sidebar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: black;
    min-height: 100vh;
    padding: 20px;
    position: fixed;
    width: 200px;
    z-index: 1000;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}
.sidebar-header {
    padding: 15px 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.sidebar-header h4 {
    color: black;
    font-weight: bold;
    margin: 0;
    text-align: center;
    font-size: 1.1rem;
}
.sidebar-profile {
    text-align: center;
    margin-bottom: 30px;
}
.avatar-container {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
}
.avatar-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 3px solid #333;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.sidebar-profile h5 {
    color: black;
    margin: 10px 0 5px;
    font-size: 1rem;
}
.sidebar-profile p {
    color: black;
    margin: 0;
    font-size: 0.9rem;
}
.nav-link {
    color: black !important;
    padding: 10px 15px;
    margin: 5px 0;
    border-radius: 5px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}
.nav-link:hover, .nav-link.active {
    color: #764ba2 !important;
    background: rgba(0,0,0,0.05);
}
.nav-link i {
    margin-right: 10px;
    font-size: 1rem;
}
</style> 