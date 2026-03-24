<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

$pageTitle = "Log Email - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";

// =============================
// FILTRI
// =============================

$tipo = $_GET['tipo'] ?? '';
$stato = $_GET['stato'] ?? '';

// SVUOTA LOG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    app_require_csrf();
    if ($conn->query("DELETE FROM email_log")) {
        $successo = "Log email svuotati correttamente.";
    } else {
        $errore = "Errore durante lo svuotamento dei log.";
    }
}

$query = "SELECT * FROM email_log WHERE 1";
$params = [];
$types = "";

if (!empty($tipo)) {
    $query .= " AND tipo = ?";
    $params[] = $tipo;
    $types .= "s";
}

if (!empty($stato)) {
    $query .= " AND stato = ?";
    $params[] = $stato;
    $types .= "s";
}

$query .= " ORDER BY data_invio DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section">
        <div class="db-title-area">
            <div style="display: flex; align-items: center; gap: 20px;">
                <h1>Log Email <span class="green-dot">.</span></h1>
                <form method="POST" style="margin:0;"
                    onsubmit="return confirm('Sei sicuro di voler eliminare TUTTI i log email? Questa operazione non è reversibile.');">
                    <?= app_csrf_input() ?>
                    <button type="submit" name="clear_logs" value="1" class="mini-btn logout" style="text-decoration: none;">🗑️
                        Svuota Log</button>
                </form>
            </div>
            <p>Cronologia delle comunicazioni automatiche inviate ai soci.</p>
        </div>
        <div class="db-status-badge">
            <span class="status-dot"></span> Sincronizzato
        </div>
    </header>

    <?php if (isset($successo) && $successo): ?>
        <div class="success-container" style="margin-bottom: 30px;">
            <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errore) && $errore): ?>
        <div class="error-container" style="margin-bottom: 30px;">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <!-- CONTROLLI E FILTRI -->
    <div class="search-form-neu" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">

        <!-- INVIO TEST -->
        <div>
            <label class="search-label-text">🔍 Invia Test Rapido</label>
            <form method="POST" action="send_test_email.php" style="display: flex; gap: 10px; flex-direction: column;">
                <?= app_csrf_input() ?>
                <input type="email" name="destinatario" placeholder="Email del destinatario..." required>
                <div style="display: flex; gap: 10px;">
                    <select name="tipo" required
                        style="flex: 1; padding: 15px; border-radius: 20px; border: none; background: #e0e5ec; box-shadow: inset 6px 6px 12px #bec3cf, inset -6px -6px 12px #ffffff; color: #3d4468; font-weight: 600;">
                        <option value="">Seleziona modello...</option>
                        <option value="iscrizione">Iscrizione</option>
                        <option value="rimessaggio">Rimessaggio</option>
                    </select>
                    <button type="submit" class="mini-btn">Invia Prova</button>
                </div>
            </form>
        </div>

        <!-- FILTRA RISULTATI -->
        <div>
            <label class="search-label-text">📊 Filtra Comunicazioni</label>
            <form method="GET" style="display: flex; gap: 10px; flex-direction: column;">
                <div style="display: flex; gap: 10px;">
                    <select name="tipo"
                        style="flex: 1; padding: 15px; border-radius: 20px; border: none; background: #e0e5ec; box-shadow: inset 6px 6px 12px #bec3cf, inset -6px -6px 12px #ffffff; color: #3d4468; font-weight: 600;">
                        <option value="">Tutti i tipi</option>
                        <option value="iscrizione" <?= $tipo == 'iscrizione' ? 'selected' : '' ?>>Iscrizione</option>
                        <option value="rimessaggio" <?= $tipo == 'rimessaggio' ? 'selected' : '' ?>>Rimessaggio</option>
                    </select>
                    <select name="stato"
                        style="flex: 1; padding: 15px; border-radius: 20px; border: none; background: #e0e5ec; box-shadow: inset 6px 6px 12px #bec3cf, inset -6px -6px 12px #ffffff; color: #3d4468; font-weight: 600;">
                        <option value="">Tutti gli stati</option>
                        <option value="inviata" <?= $stato == 'inviata' ? 'selected' : '' ?>>Inviata</option>
                        <option value="errore" <?= $stato == 'errore' ? 'selected' : '' ?>>Errore</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="mini-btn" style="flex: 1;">Applica Filtri</button>
                    <a href="email_log.php" class="mini-btn logout"
                        style="text-decoration: none; flex: 1; text-align: center;">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABELLA -->
    <div class="neu-table-card">
        <h3 style="margin-bottom: 25px; color: #3d4468;">Cronologia Invii</h3>
        <table class="admin-table-neu">
            <thead>
                <tr>
                    <th>TIPO</th>
                    <th>DESTINATARIO</th>
                    <th>OGGETTO</th>
                    <th>STATO</th>
                    <th>DATA</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="TIPO"><span
                                style="font-size: 10px; background: #e0e5ec; padding: 4px 10px; border-radius: 20px; box-shadow: 2px 2px 5px #bec3cf, -2px -2px 5px #ffffff; color: #6fb21b; font-weight: bold;"><?= strtoupper(htmlspecialchars($row['tipo'])) ?></span>
                        </td>
                        <td data-label="DESTINATARIO"><?= htmlspecialchars($row['destinatario']) ?></td>
                        <td data-label="OGGETTO"><?= htmlspecialchars($row['oggetto']) ?></td>
                        <td data-label="STATO">
                            <?php if ($row['stato'] == 'inviata'): ?>
                                <span style="color: #6fb21b; font-weight: bold;">✔ Inviata</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: bold;">✖ Errore</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="DATA" style="font-size: 12px; color: #9499b7;"><?= $row['data_invio'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>