<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/security_utils.php';
if (!isset($pageTitle)) {
    $pageTitle = "Moki SUP Club";
}

app_start_secure_session();
$csrfToken = app_csrf_token();
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/img/moki.jpg">

    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/neumorphism.css">

    <!-- Flatpickr (Calendario Moderno) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/it.js"></script>
</head>

<body>

    <header class="site-header">
        <div class="header-container">
            <div class="logo-area">
                <a href="/">
                    <img src="/assets/img/moki.jpg" class="logo">
                    <span class="site-name">Moki Club Numana</span>
                </a>
            </div>

            <nav>
                <?php if (isset($_SESSION["admin"])): ?>
                    <a href="/admin/dashboard.php" class="admin-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        Dashboard
                    </a>
                    <a href="/admin/logout.php" class="admin-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/admin/login.php" class="admin-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Gestione Admin
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>