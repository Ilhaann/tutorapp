<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-3 col-lg-2 sidebar">
    <div class="sidebar-header">
        <h4><?php echo APP_NAME; ?></h4>
    </div>
    <div class="sidebar-profile">
        <div class="avatar-container">
            <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $user['profile_picture'] ?? 'default-avatar.jpg'; ?>"
                 onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                 class="rounded-circle"
                 alt="Profile Picture">
        </div>
        <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
        <p>Tutor</p>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
            <i class="bi bi-person"></i> Profile
        </a>
        <a class="nav-link <?php echo $current_page === 'subjects.php' ? 'active' : ''; ?>" href="subjects.php">
            <i class="bi bi-book"></i> Units
        </a>
        <a class="nav-link <?php echo $current_page === 'availability.php' ? 'active' : ''; ?>" href="availability.php">
            <i class="bi bi-calendar"></i> Availability
        </a>
        <a class="nav-link <?php echo $current_page === 'sessions.php' ? 'active' : ''; ?>" href="sessions.php">
            <i class="bi bi-clock-history"></i> Sessions
        </a>
        <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" href="messages.php">
            <i class="bi bi-envelope"></i> Messages
        </a>
        <a class="nav-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
            <i class="bi bi-star"></i> Reviews
        </a>
        <a class="nav-link" href="../auth/logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</div>

<style>
.sidebar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    min-height: 100vh;
    padding: 10px;
    position: fixed;
    width: 200px;
    z-index: 1000;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}
.sidebar-header {
    padding: 5px 0;
    border-bottom: 1px solid rgba(255,255,255,0.2);
    margin-bottom: 10px;
}
.sidebar-header h4 {
    color: white;
    font-weight: bold;
    margin: 0;
    text-align: center;
    font-size: 1.1rem;
}
.sidebar-profile {
    padding: 5px 0;
    margin-bottom: 10px;
    text-align: center;
}
.avatar-container {
    width: 60px;
    height: 60px;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
}
.avatar-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 50%;
    background-color: #f8f9fa;
}
.sidebar-profile h5 {
    color: white;
    margin: 5px 0 0 0;
    text-align: center;
    font-size: 0.95rem;
}
.sidebar-profile p {
    color: white;
    margin: 0;
    text-align: center;
    font-size: 0.85rem;
}
.sidebar .nav-link {
    color: white;
    padding: 8px 10px;
    margin: 2px 0;
    border-radius: 5px;
    font-weight: 500;
    background-color: rgba(255,255,255,0.1);
    font-size: 0.9rem;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    background-color: rgba(255,255,255,0.2);
    color: white;
    transform: translateX(5px);
}
.sidebar .nav-link i {
    margin-right: 8px;
    font-size: 1rem;
}
</style> 