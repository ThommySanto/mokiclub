<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

// Recupero statistiche reali
$query_iscritti = "SELECT COUNT(*) as totale FROM iscritti";
$res_iscritti = $conn->query($query_iscritti);
$tot_iscritti = $res_iscritti->fetch_assoc()['totale'] ?? 0;

$query_rimessaggi = "SELECT COUNT(*) as totale FROM rimessaggi";
$res_rimessaggi = $conn->query($query_rimessaggi);
$tot_rimessaggi = $res_rimessaggi->fetch_assoc()['totale'] ?? 0;

$pageTitle = "Dashboard Admin - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-dashboard-wrapper">
   <!-- Header Dashboard -->
   <header class="db-header-section">
      <div class="db-title-area">
         <h1>Moki Control <span class="green-dot">.</span></h1>
         <p>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION["admin"]); ?></strong>. Pannello di controllo
            centrale.</p>
      </div>
      <div class="db-status-badge">
         <span class="status-dot"></span> System Online
      </div>
   </header>

   <!-- Sezione Statistiche Rapide -->
   <div class="stats-overview">
      <div class="neu-stat-card">
         <span class="stat-label">Iscritti al Club</span>
         <div class="stat-value"><?php echo $tot_iscritti; ?></div>
         <div class="stat-trend positive">Membri attivi</div>
      </div>
      <div class="neu-stat-card">
         <span class="stat-label">Rimessaggi Totali</span>
         <div class="stat-value"><?php echo $tot_rimessaggi; ?></div>
         <div class="stat-trend">Tavole in magazzino</div>
      </div>
   </div>

   <!-- Bento Grid Navigazione -->
   <div class="admin-dashboard-bento">

      <!-- Iscritti (Card Grande) -->
      <a href="iscritti.php" class="bento-card bento-iscritti">
         <div class="bento-icon">🏄‍♂️</div>
         <div class="bento-text">
            <h3>Gestisci Iscritti</h3>
            <p>Anagrafica, pagamenti e certificati.</p>
         </div>
      </a>

      <!-- Rimessaggi -->
      <a href="rimessaggi.php" class="bento-card bento-rimessaggi">
         <div class="bento-icon">📦</div>
         <div class="bento-text">
            <h3>Rimessaggi</h3>
            <p>Slot tavole e pagamenti.</p>
         </div>
      </a>

      <!-- Utility Grid (Full Width) -->
      <div class="bento-utility-grid">
         <a href="email_log.php" class="bento-card mini-bento">
            <div class="bento-icon-small">📧</div>
            <span>Log Email</span>
         </a>
         <a href="gestione_admin.php" class="bento-card mini-bento">
            <div class="bento-icon-small">🛡️</div>
            <span>Gestione Admin</span>
         </a>
         <a href="logout.php" class="bento-card mini-bento bento-logout">
            <div class="bento-icon-small">🚪</div>
            <span>Scollegati</span>
         </a>
      </div>

   </div>
</main>

<style>
   /* Stili specifici per il layout della dashboard che non sono nel CSS globale */
   .admin-dashboard-wrapper {
      padding: 40px 20px;
      max-width: 1200px;
      margin: 0 auto;
      min-height: 80vh;
   }

   .db-header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
      text-align: left;
   }

   .db-title-area h1 {
      font-size: 2.8rem;
      margin: 0;
      color: #3d4468;
   }

   .green-dot {
      color: var(--secondary-color);
   }

   .db-status-badge {
      padding: 10px 20px;
      background: #e0e5ec;
      border-radius: 50px;
      box-shadow: inset 4px 4px 8px #bec3cf, inset -4px -4px 8px #ffffff;
      font-size: 13px;
      font-weight: 700;
      color: #6c7293;
      display: flex;
      align-items: center;
      gap: 10px;
   }

   .status-dot {
      width: 8px;
      height: 8px;
      background: var(--secondary-color);
      border-radius: 50%;
      box-shadow: 0 0 10px var(--secondary-color);
   }

   .stats-overview {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 30px;
      margin-bottom: 40px;
   }

   .neu-stat-card {
      background: #e0e5ec;
      padding: 30px;
      border-radius: 30px;
      box-shadow: 15px 15px 30px #bec3cf, -15px -15px 30px #ffffff;
      text-align: left;
      transition: transform 0.3s ease;
   }

   .neu-stat-card:hover {
      transform: translateY(-5px);
   }

   .stat-label {
      color: #9499b7;
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
   }

   .stat-value {
      font-size: 3.5rem;
      font-weight: 900;
      color: #3d4468;
      margin: 10px 0;
      line-height: 1;
   }

   .stat-trend {
      color: #6c7293;
      font-size: 13px;
      font-weight: 500;
   }

   .stat-trend.positive {
      color: var(--secondary-color);
      font-weight: 700;
   }

   .bento-iscritti,
   .bento-rimessaggi {
      grid-column: span 6 !important;
      flex-direction: row !important;
      gap: 30px;
      text-align: left !important;
      justify-content: flex-start !important;
   }

   .bento-utility-grid {
      grid-column: span 12 !important;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      /* Aumentato gap per respiro */
      margin-top: 10px;
   }

   .mini-bento {
      padding: 25px !important;
      border-radius: 25px !important;
      flex-direction: column !important;
   }

   .bento-icon-small {
      font-size: 2rem;
      margin-bottom: 15px;
   }

   .mini-bento span {
      font-size: 13px;
      font-weight: 800;
      text-transform: uppercase;
      color: #3d4468;
      letter-spacing: 0.5px;
   }

   @media (max-width: 992px) {
      .db-header-section {
         flex-direction: column;
         text-align: center;
         gap: 20px;
      }

      .db-title-area h1 {
         font-size: 2.2rem;
      }

      .stats-overview {
         grid-template-columns: 1fr;
         gap: 20px;
      }

      .stat-value {
         font-size: 2.8rem;
      }

      .bento-iscritti,
      .bento-rimessaggi {
         grid-column: span 12 !important;
         flex-direction: column !important;
         text-align: center !important;
         padding: 30px 20px !important;
      }

      .bento-icon {
         margin-bottom: 15px;
      }

      .bento-utility-grid {
         grid-column: span 12 !important;
         grid-template-columns: 1fr;
      }
   }

   @media (max-width: 480px) {
      .db-title-area h1 {
         font-size: 1.8rem;
      }

      .admin-dashboard-wrapper {
         padding: 20px 15px;
      }

      .neu-stat-card {
         padding: 20px;
      }
   }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>