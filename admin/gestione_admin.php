<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

$successo = "";
$errore = "";

/* ---------------- DELETE ---------------- */
if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
    $id = intval($_GET['delete']);

    if ($id === 0) {
        $errore = "L'utente admin (ID 0) non può essere eliminato";
    } else {

    $stmt = $conn->prepare("SELECT username FROM utenti_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['username'] === 'admin') {
            $errore = "Non puoi eliminare l'utente admin";
        } else {
            $stmt = $conn->prepare("DELETE FROM utenti_admin WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $successo = "Utente eliminato con successo";
            } else {
                $errore = "Errore nell'eliminare l'utente";
            }
        }
    }
    }
    }
}

/* ---------------- EDIT ---------------- */
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    if ($id === 0) {
        $errore = "L'utente admin (ID 0) non può essere modificato";
    } else {

    $stmt = $conn->prepare("SELECT username FROM utenti_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $edit_username = $user['username'];
        $edit_id = $id;
    }
    }
}

/* ---------------- UPDATE ---------------- */
if (isset($_POST['update'])) {
    app_require_csrf();

    $id = intval($_POST['update_id']);
    $username = trim($_POST['update_username']);
    $password = $_POST['update_password'];

    if ($id === 0) {
        $errore = "L'utente admin (ID 0) non può essere modificato";
    } else {

    $stmt = $conn->prepare("SELECT username FROM utenti_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current_user = $stmt->get_result()->fetch_assoc();

    if ($current_user['username'] === 'admin' && $username !== 'admin') {
        $errore = "Non puoi cambiare il nome dell'utente admin (motivi di sicurezza)";
    } else {

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utenti_admin SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE utenti_admin SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $id);
        }

        if ($stmt->execute()) {
            $successo = "Utente aggiornato con successo";
        } else {
            $errore = "Errore nell'aggiornare l'utente";
        }
    }
    }
}

/* ---------------- CREATE ---------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['update'])) {
    app_require_csrf();

    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        $errore = "Le password non coincidono";
    } elseif (strlen($password) < 6) {
        $errore = "La password deve essere di almeno 6 caratteri";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO utenti_admin (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $successo = "Utente aggiunto con successo";
        } else {
            $errore = "Errore nell'aggiungere l'utente";
        }
    }
}

$pageTitle = "Gestione Admin - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section">
        <div class="db-title-area">
            <h1>Gestione Staff <span class="green-dot">.</span></h1>
            <p>Aggiungi o modifica gli account amministrativi del portale.</p>
        </div>
        <div class="db-status-badge">
            <span class="status-dot"></span> Account Protetti
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

    <div class="admin-management-grid">

        <!-- STRUMENTI DI GESTIONE -->
        <div class="search-form-neu management-inner-grid">

            <!-- AGGIUNTA -->
            <div>
                <label class="search-label-text">👤 Nuovo Account Admin</label>
                <form method="POST" style="display: flex; gap: 15px; flex-direction: column;">
                    <?= app_csrf_input() ?>
                    <input type="text" name="username" required placeholder="Scegli Username...">
                    <div style="display: flex; gap: 10px;">
                        <input type="password" name="password" required placeholder="Password..." style="flex: 1;">
                        <input type="password" name="confirm_password" required placeholder="Conferma..."
                            style="flex: 1;">
                    </div>
                    <button type="submit" class="mini-btn">Crea Profilo</button>
                </form>
            </div>

            <!-- MODIFICA -->
            <?php if (isset($edit_id)): ?>
                <div class="edit-account-panel">
                    <label class="search-label-text">✏️ Modifica: <?= htmlspecialchars($edit_username) ?></label>
                    <form method="POST" style="display: flex; gap: 15px; flex-direction: column;">
                        <?= app_csrf_input() ?>
                        <input type="hidden" name="update_id" value="<?php echo $edit_id; ?>">
                        <input type="text" name="update_username" value="<?php echo htmlspecialchars($edit_username); ?>"
                            required <?php if ($edit_username === 'admin')
                                echo 'readonly'; ?>>
                        <input type="password" name="update_password"
                            placeholder="Nuova password (lascia vuoto per non cambiare)">

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="update" class="mini-btn" style="flex: 1;">Aggiorna</button>
                            <a href="gestione_admin.php" class="mini-btn logout"
                                style="text-decoration: none; flex: 1; text-align: center;">Annulla</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div
                    style="display: flex; align-items: center; justify-content: center; height: 100%; color: #9499b7; font-style: italic;">
                    Seleziona un utente dalla lista per modificarlo.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- TABELLA UTENTI -->
    <div class="neu-table-card" style="margin-top: 50px;">
        <h3 style="margin-bottom: 25px; color: #3d4468;">Admin Esistenti</h3>
        <table class="admin-table-neu">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>USERNAME</th>
                    <th style="text-align: right;">AZIONI</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT id, username FROM utenti_admin ORDER BY id = 0 DESC, id ASC");
                while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td data-label="ID"><?= $row['id'] ?></td>
                        <td data-label="USERNAME">
                            <strong style="color: #3d4468;"><?= htmlspecialchars($row['username']) ?></strong>
                            <?php if ((int) $row['id'] === 0): ?>
                                <span
                                    style="font-size: 10px; background: var(--secondary-color); color: white; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">ROOT</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="AZIONI" style="display: flex; gap: 10px; justify-content: flex-end;">
                            <?php if ((int) $row['id'] !== 0): ?>
                                <a href="?edit=<?= $row['id'] ?>" class="neu-toggle mini-btn" title="Modifica"
                                    style="text-decoration: none; position: static; transform: none; width: 35px; height: 35px;">
                                    ✏️
                                </a>
                                <a href="?delete=<?= $row['id'] ?>&csrf_token=<?= urlencode(app_csrf_token()) ?>" class="neu-toggle mini-btn logout" title="Elimina"
                                    style="text-decoration: none; position: static; transform: none; width: 35px; height: 35px;"
                                    onclick="return confirm('Sei sicuro?')">
                                    🗑️
                                </a>
                            <?php else: ?>
                                <span style="color: #9499b7; font-size: 12px; font-weight: 700;">Protetto</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <style>
        .admin-management-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
            align-items: start;
        }

        .management-inner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            align-items: start;
        }

        .edit-account-panel {
            border-left: 1px solid rgba(190, 195, 207, 0.5);
            padding-left: 30px;
        }

        @media (max-width: 768px) {
            .management-inner-grid {
                grid-template-columns: 1fr;
            }

            .edit-account-panel {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid rgba(190, 195, 207, 0.5);
                padding-top: 30px;
            }

            .search-form-neu input[type="password"] {
                width: 100%;
            }

            .search-form-neu div[style*="display: flex"] {
                flex-direction: column !important;
            }
        }
    </style>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>