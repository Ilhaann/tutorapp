<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>">Home</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <?php if (get_user_role() === 'tutor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/tutor/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/tutor/sessions.php">Sessions</a>
                            </li>
                        <?php elseif (get_user_role() === 'tutee'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/tutee/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/tutee/sessions.php">Sessions</a>
                            </li>
                        <?php elseif (get_user_role() === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/users.php">Users</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/reports.php">Reports</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> Profile
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4"> 