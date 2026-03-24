<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

$pageTitle = "Rimessaggio";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="form-wrapper">

    <div class="form-card">
        <h2 class="form-title">Modulo Rimessaggio Moki Club Numana</h2>


        <form action="submit_rimessaggio.php" method="POST" enctype="multipart/form-data" class="modern-form">
            <?= app_csrf_input() ?>

            <!-- DATI PERSONALI -->
            <div class="form-row">
                <input type="text" name="nome" placeholder="Nome" required autocomplete="off">
                <input type="text" name="cognome" placeholder="Cognome" required autocomplete="off">
            </div>

            <input type="text" name="luogo_nascita" placeholder="Luogo di nascita" autocomplete="off">

            <div class="form-group">
                <label>Data di nascita</label>
                <input type="date" name="data_nascita" autocomplete="off">
            </div>

            <input type="text" name="indirizzo" placeholder="Indirizzo" autocomplete="off">

            <div class="form-row">
                <input type="text" name="citta" placeholder="Città" autocomplete="off">
                <input type="text" name="cap" placeholder="CAP" autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Telefono</label>
                    <div class="phone-input-container">
                        <div class="prefix-selector">
                            <select name="prefisso_tel">
                                <option value="+39">IT (+39)</option>
                                <option value="+44">UK (+44)</option>
                                <option value="+33">FR (+33)</option>
                                <option value="+49">DE (+49)</option>
                                <option value="+41">CH (+41)</option>
                                <option value="+43">AT (+43)</option>
                            </select>
                        </div>
                        <input type="tel" name="telefono" placeholder="333 0000000" required autocomplete="off"
                            class="input-tel">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Email" autocomplete="off">
                </div>
            </div>

            <!-- DATI ATTREZZATURA -->
            <h3 class="section-title">Dati Tavola</h3>

            <div class="form-row">
                <input type="text" name="tavola_marca" placeholder="Tavola Marca" autocomplete="off">
                <input type="text" name="tavola_modello" placeholder="Tavola Modello" autocomplete="off">
            </div>

            <input type="text" name="tavola_anno" placeholder="Anno Tavola" autocomplete="off">

            <select name="sacca" autocomplete="off">
                <option value="" disabled selected hidden>Sacca</option>
                <option value="Si">Sì</option>
                <option value="No">No</option>
            </select>

            <input type="text" name="pagaia" placeholder="Pagaia (Marca e Modello)" autocomplete="off">
            <input type="text" name="altro" placeholder="Altro materiale lasciato" autocomplete="off">

            <!-- DOCUMENTI -->
            <h3 class="section-title">Dati Documento</h3>

            <select name="adulto_kid" required autocomplete="off">
                <option value="" disabled selected hidden>Adulto o Kid</option>
                <option value="Adulto">Adulto</option>
                <option value="Kid">Kid</option>
            </select>

            <select name="tipo_documento" autocomplete="off">
                <option value="" disabled selected hidden>Tipo Documento</option>
                <option value="Carta Identità">Carta Identità</option>
                <option value="Codice Fiscale">Codice Fiscale</option>
                <option value="Patente">Patente</option>
                <option value="Passaporto">Passaporto</option>
            </select>

            <input type="text" name="numero_documento" placeholder="Numero Documento" autocomplete="off">

            <!-- TIPO RIMESSAGGIO -->
            <h3 class="section-title">Tipo Rimessaggio</h3>

            <select name="tipo_rimessaggio" required autocomplete="off">
                <option value="" disabled selected hidden>Tipo Rimessaggio</option>
                <option value="Settimanale">Settimanale (90€ + 10€ tesseramento)</option>
                <option value="Mensile">Mensile (180€ + 10€ tesseramento)</option>
                <option value="Annuale">Annuale (450€)</option>
            </select>

            <div class="form-row">
                <input type="number" step="0.01" name="acconto" placeholder="Acconto €" autocomplete="off">
                <input type="number" step="0.01" name="saldo_totale" id="saldo_totale" placeholder="Saldo Totale €"
                    readonly autocomplete="off">
            </div>

            <div class="form-group">
                <label>Data versamento acconto</label>
                <input type="date" name="data_versamento_acconto" autocomplete="off">
            </div>

            <!-- RICEVUTA -->
            <div class="form-group">
                <label>Ricevuta di pagamento</label>
                <input type="file" name="ricevuta_pagamento">
            </div>

            <!-- FIRMA -->
            <div class="signature-section" style="background: transparent; box-shadow: none; border: none; padding: 0;">
                <label style="margin-bottom: 15px; display: block;">Firma Digitale</label>
                <div class="signature-placeholder" id="open-signature">
                    <span id="placeholder-text">🖌️ Tocca qui per firmare</span>
                    <img id="signature-preview" style="display:none;">
                </div>
            </div>

            <!-- MODAL FIRMA -->
            <div id="signature-modal" class="signature-modal">
                <div class="modal-content-neu">
                    <h2 style="color: #3d4468; margin-bottom: 10px;">Firma qui</h2>
                    <p style="color: #9499b7; font-size: 14px;">Usa il dito o il mouse per firmare nello spazio bianco
                    </p>

                    <canvas id="signature-pad" class="modal-signature-pad"></canvas>

                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="button" class="neu-button mini-btn" id="save-signature"
                            style="width: auto !important; padding: 15px 40px !important;">Conferma</button>
                        <button type="button" class="neu-button mini-btn logout" id="clear-signature"
                            style="width: auto !important; padding: 15px 40px !important;">Cancella</button>
                        <button type="button" class="neu-button mini-btn logout" id="close-modal"
                            style="width: auto !important; padding: 15px 40px !important; background: #9499b7; color: white;">Chiudi</button>
                    </div>
                </div>
            </div>

            <input type="hidden" name="firma" id="firma">

            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 40px;">
                Salva Rimessaggio
            </button>

        </form>
    </div>
</div>
<script>
    const modal = document.getElementById('signature-modal');
    const openBtn = document.getElementById('open-signature');
    const closeBtn = document.getElementById('close-modal');
    const saveBtn = document.getElementById('save-signature');
    const clearBtn = document.getElementById('clear-signature');
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    const hiddenInput = document.getElementById('firma');
    const previewImg = document.getElementById('signature-preview');
    const placeholderText = document.getElementById('placeholder-text');

    let drawing = false;

    function resizeCanvas() {
        if (!modal.classList.contains('active')) return;

        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;

        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;

        ctx.scale(ratio, ratio);

        ctx.strokeStyle = "#024b86";
        ctx.lineWidth = 3;
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
    }

    // Ricalibra se l'utente ruota il telefono
    window.addEventListener('resize', resizeCanvas);
    window.addEventListener('orientationchange', () => setTimeout(resizeCanvas, 500));

    openBtn.addEventListener('click', () => {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(resizeCanvas, 400); // Più tempo per l'animazione su mobile
    });

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    });

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDraw(e) {
        drawing = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        if (e.cancelable) e.preventDefault();
    }

    function draw(e) {
        if (!drawing) return;
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        if (e.cancelable) e.preventDefault();
    }

    function stopDraw() {
        if (drawing) {
            ctx.closePath();
            drawing = false;
        }
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    window.addEventListener('mouseup', stopDraw);

    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDraw);

    clearBtn.addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    saveBtn.addEventListener('click', () => {
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');

        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;

        tempCtx.fillStyle = "#ffffff";
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        tempCtx.drawImage(canvas, 0, 0);

        const dataURL = tempCanvas.toDataURL('image/png');
        hiddenInput.value = dataURL;
        previewImg.src = dataURL;
        previewImg.style.display = 'block';
        placeholderText.style.display = 'none';

        modal.classList.remove('active');
        document.body.style.overflow = '';
    });
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>