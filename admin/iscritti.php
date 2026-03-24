<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

$pageTitle = "Gestisci Iscritti - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";

$successo = "";
$errore = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* =========================
   ELIMINAZIONE
========================= */
if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM iscritti WHERE id_modulo = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $successo = "Iscritto eliminato con successo";
    } else {
        $errore = "Errore nell'eliminare l'iscritto";
    }
    }
}

/* =========================
   MODIFICA - CARICAMENTO
========================= */
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM iscritti WHERE id_modulo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultEdit = $stmt->get_result();
    $iscritto = $resultEdit->fetch_assoc();
}

/* =========================
   AGGIORNAMENTO
========================= */
if (isset($_POST['update'])) {
    app_require_csrf();

    $id = intval($_POST['update_id']);
    unset($_POST['update'], $_POST['update_id'], $_POST['csrf_token']);

    $campi = [];
    $valori = [];
    $tipi = "";

    foreach ($_POST as $campo => $valore) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $campo)) {
            continue;
        }

        $campi[] = "$campo = ?";
        $valori[] = $valore;
        $tipi .= "s";
    }

    if (empty($campi)) {
        $errore = "Nessun campo valido da aggiornare";
    } else {
        $valori[] = $id;
        $tipi .= "i";

        $sql = "UPDATE iscritti SET " . implode(", ", $campi) . " WHERE id_modulo = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log('Errore prepare admin/iscritti update: ' . $conn->error);
            $errore = "Errore durante l'aggiornamento";
        } else {
            $bind_names = [];
            $bind_names[] = $tipi;
            for ($i = 0; $i < count($valori); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $valori[$i];
                $bind_names[] = &$$bind_name;
            }

            call_user_func_array([$stmt, 'bind_param'], $bind_names);

            if ($stmt->execute()) {
                $successo = "Iscritto aggiornato con successo";
            } else {
                error_log('Errore execute admin/iscritti update: ' . $stmt->error);
                $errore = "Errore durante l'aggiornamento";
            }
        }
    }
}
/* =========================
   RICERCA
========================= */
/* =========================
   PAGINAZIONE E RICERCA
========================= */
$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Calcolo Totale Record per Paginazione
$count_query = "SELECT COUNT(*) as total FROM iscritti WHERE 1";
if ($search) {
    $count_query .= " AND (nome LIKE ? OR cognome LIKE ?)";
}
$stmt_count = $conn->prepare($count_query);
if ($search) {
    $search_param = "%$search%";
    $stmt_count->bind_param("ss", $search_param, $search_param);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Query vera
$query = "SELECT * FROM iscritti WHERE 1";
if ($search) {
    $query .= " AND (nome LIKE ? OR cognome LIKE ?)";
}
$query .= " ORDER BY id_modulo DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section admin-toolbar-header">
        <div class="db-title-area">
            <div class="admin-title-row">
                <h1>Gestione Iscritti <span class="green-dot">.</span></h1>
                <div class="admin-top-actions">
                    <a href="export_csv.php?type=iscritti" class="button type1"
                        style="height: 40px; min-width: 180px; font-size: 11px; border-color: #3d4468; color: #3d4468;">
                        <span class="btn-txt" style="letter-spacing: 1px;">📊 ESPORTA EXCEL</span>
                    </a>
                    <button id="open-bulk-email" class="button type1"
                        style="height: 40px; min-width: 220px; font-size: 11px; border-color: var(--secondary-color); color: var(--secondary-color);">
                        <span class="btn-txt" style="letter-spacing: 1px;">📧 INVIA AVVISO MASSIVO</span>
                    </button>
                    <style>
                        /* Fix local style for hover bubble */
                        #open-bulk-email::after {
                            background-color: var(--secondary-color);
                        }

                        .button.type1[href*="export"]::after {
                            background-color: #3d4468;
                        }
                    </style>
                </div>
            </div>
            <p>Visualizza, cerca e modifica i membri del Moki SUP Club.</p>
        </div>
        <div class="db-status-badge records-badge">
            <span class="status-dot"></span> <?= $total_rows ?> Totali
        </div>
    </header>

    <?php if ($successo): ?>
        <div class="success-container" style="margin-bottom: 30px;">
            <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>

    <?php if ($errore): ?>
        <div class="error-container" style="margin-bottom: 30px;">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <!-- =========================
         FORM RICERCA
    ========================= -->
    <form method="GET" class="search-form-neu">
        <label class="search-label-text">
            🔍 Strumenti di Ricerca
        </label>
        <input type="text" name="search" placeholder="Cerca per nome o cognome..."
            value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="mini-btn">Cerca</button>
        <?php if ($search): ?>
            <a href="iscritti.php" class="mini-btn logout" style="text-decoration: none;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- =========================
         MODIFICA (Card Neumorphica)
    ========================= -->
    <?php if (isset($iscritto)): ?>
        <div class="login-card" style="max-width: 100%; margin-bottom: 50px; text-align: left;">
            <h2 style="margin-bottom: 30px; color: #3d4468;">Modifica Iscritto:
                <?= htmlspecialchars($iscritto['nome'] . ' ' . $iscritto['cognome']) ?>
            </h2>

            <form method="POST" class="login-form">
                <?= app_csrf_input() ?>
                <input type="hidden" name="update_id" value="<?php echo $iscritto['id_modulo']; ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php
                    $enumFields = [
                        "tipo_iscrizione" => ["Iscrizione", "Abbonamento", "Lezione", "Alba"]
                    ];

                    foreach ($iscritto as $campo => $valore):
                        if ($campo == 'id_modulo')
                            continue;
                        ?>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label
                                style="margin-left: 5px; color: #9499b7; font-size: 12px;"><?php echo strtoupper(str_replace('_', ' ', $campo)); ?></label>
                            <div class="neu-input" style="box-shadow: inset 4px 4px 8px #bec3cf, inset -4px -4px 8px #ffffff;">
                                <?php if (isset($enumFields[$campo])): ?>
                                    <select name="<?php echo $campo; ?>"
                                        style="width: 100%; background: transparent; border: none; padding: 15px 20px; outline: none; color: #3d4468; cursor: pointer;">
                                        <?php foreach ($enumFields[$campo] as $opzione): ?>
                                            <option value="<?php echo $opzione; ?>" <?php if ($valore == $opzione)
                                                   echo "selected"; ?>>
                                                <?php echo $opzione; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif (strpos($campo, 'data') !== false): ?>
                                    <input type="date" name="<?php echo $campo; ?>" value="<?php echo htmlspecialchars($valore); ?>"
                                        style="width: 100%; border: none; background: transparent; padding: 15px 20px; outline: none; color: #3d4468;">
                                <?php else: ?>
                                    <input type="text" name="<?php echo $campo; ?>" value="<?php echo htmlspecialchars($valore); ?>"
                                        style="width: 100%; border: none; background: transparent; padding: 15px 20px; outline: none; color: #3d4468;">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 20px;">
                    <button type="submit" name="update" class="neu-button mini-btn">Salva Modifiche</button>
                    <a href="iscritti.php" class="neu-button mini-btn logout"
                        style="text-align: center; text-decoration: none;">Annulla</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- =========================
         LISTA (Tabella Neumorphica)
    ========================= -->
    <div class="neu-table-card">
        <h3 style="margin-bottom: 25px; color: #3d4468;">Tutti gli Iscritti</h3>

        <table class="admin-table-neu">
            <thead>
                <tr>
                    <?php
                    $fields = $result->fetch_fields();
                    foreach ($fields as $field) {
                        if (in_array($field->name, ['id_modulo', 'nome', 'cognome', 'email', 'telefono', 'tipo_iscrizione', 'data_iscrizione'])) {
                            echo "<th>" . strtoupper(str_replace('_', ' ', $field->name)) . "</th>";
                        }
                    }
                    echo "<th>AZIONI</th>";
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($fields as $field):
                            if (!in_array($field->name, ['id_modulo', 'nome', 'cognome', 'email', 'telefono', 'tipo_iscrizione', 'data_iscrizione']))
                                continue;
                            ?>
                            <td data-label="<?= strtoupper(htmlspecialchars($field->name)) ?>">
                                <?= htmlspecialchars($row[$field->name]) ?>
                            </td>
                        <?php endforeach; ?>

                        <td data-label="AZIONI" style="display: flex; gap: 10px;">
                            <a href="?edit=<?= $row['id_modulo'] ?>" class="neu-toggle mini-btn" title="Modifica"
                                style="text-decoration: none; position: static; transform: none; width: 35px; height: 35px;">
                                ✏️
                            </a>
                            <a href="?delete=<?= $row['id_modulo'] ?>&csrf_token=<?= urlencode(app_csrf_token()) ?>" class="neu-toggle mini-btn logout" title="Elimina"
                                style="text-decoration: none; position: static; transform: none; width: 35px; height: 35px;"
                                onclick="return confirm('Sei sicuro di voler eliminare questo iscritto?')">
                                🗑️
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINAZIONE -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 50px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                    class="neu-button mini-btn <?= $i === $page ? '' : 'logout' ?>"
                    style="text-decoration: none; min-width: 45px; text-align: center;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</main>

<!-- MODAL COMUNICAZIONE DI MASSA -->
<div class="signature-modal" id="bulk-email-modal">
    <div class="modal-content-neu" style="max-width: 1000px;">
        <h2 style="margin-bottom: 25px; color: #3d4468;">📧 Comunicazione di Massa</h2>

        <div class="bulk-modal-grid">
            <!-- SELEZIONE SOCI -->
            <div class="user-selection-panel">
                <label class="search-label-text">1. Seleziona Destinatari</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="bulk-user-search" placeholder="Cerca socio..."
                        style="flex: 1; padding: 10px; border-radius: 12px; border: none; box-shadow: inset 2px 2px 5px #bec3cf, inset -2px -2px 5px #ffffff;">
                    <button id="bulk-select-all" class="button type1"
                        style="height: 40px; min-width: 100px; padding: 0 15px; font-size: 11px; border-color: #3d4468; color: #3d4468;">
                        <span class="btn-txt">Tutti</span>
                    </button>
                </div>
                <div class="user-selection-list" id="bulk-user-list">
                    <!-- Dinamico da JS -->
                </div>
            </div>

            <!-- COMPOSIZIONE EMAIL -->
            <div class="composition-panel">
                <label class="search-label-text">2. Componi Messaggio</label>

                <div class="bulk-input-group">
                    <label>OGGETTO</label>
                    <input type="text" id="bulk-subject" placeholder="Es: Avviso chiusura Club">
                </div>

                <div class="bulk-input-group">
                    <label>MESSAGGIO (Usa {NOME} per personalizzare)</label>
                    <textarea id="bulk-message" placeholder="Ciao {NOME}, ti scriviamo per..."></textarea>
                </div>

                <div class="bulk-input-group">
                    <label>ALLEGATO (Opzionale)</label>
                    <input type="file" id="bulk-attachment"
                        style="box-shadow: none; background: transparent; padding: 0;">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 10px;">
                    <button id="bulk-send-btn" class="button type1"
                        style="flex: 1; height: 50px; min-width: 120px; border-color: var(--secondary-color); color: var(--secondary-color);">
                        <span class="btn-txt">🚀 INVIA</span>
                    </button>
                    <button id="close-bulk-modal" class="button type1"
                        style="flex: 1; height: 50px; min-width: 120px; border-color: #3d4468; color: #3d4468;">
                        <span class="btn-txt">INDIETRO</span>
                    </button>
                    <style>
                        #bulk-send-btn::after {
                            background-color: var(--secondary-color);
                        }

                        #close-bulk-modal::after {
                            background-color: #3d4468;
                        }

                        #bulk-select-all::after {
                            background-color: #3d4468;
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PROGRESS BAR IBRIDA -->
<div class="bulk-progress-container" id="bulk-progress-container">
    <div class="bulk-progress-header">
        <span>INVIO IN CORSO...</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill"></div>
    </div>
</div>

<script src="/assets/js/bulk_email.js"></script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>