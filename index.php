<?php
require_once __DIR__ . '/security_headers.php';
$pageTitle = "Benvenuto - Moki SUP Club";
require_once __DIR__ . "/includes/header.php";
?>


<section class="hero">
    <div class="hero-overlay">
        <div class="hero-content">
            <h1>Benvenuto al Moki Club Numana</h1>
            <p>Centro sportivo water sports: SUP\WIND\SURF\FOIL</p>

            <div class="button-container">
                <a href="/cliente/iscrizione.php" class="button type1 main-button home-cta-button">
                    <span class="btn-txt">Iscriviti al Club</span>
                </a>
                <a href="/cliente/rimessaggio.php" class="button type1 secondary-button home-cta-button">
                    <span class="btn-txt">Prenota Rimessaggio</span>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="stats-container">
        <div class="stat-item" style="animation: fadeInUp 0.5s ease backwards; animation-delay: 0.2s;">
            <span class="stat-number">100%</span>
            <span class="stat-label">Ocean Friendly</span>
        </div>
        <div class="stat-item" style="animation: fadeInUp 0.5s ease backwards; animation-delay: 0.4s;">
            <span class="stat-number">100%</span>
            <span class="stat-label">Water Sports</span>
        </div>
        <div class="stat-item" style="animation: fadeInUp 0.5s ease backwards; animation-delay: 0.6s;">
            <span class="stat-number">11°</span>
            <span class="stat-label">stagione</span>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>