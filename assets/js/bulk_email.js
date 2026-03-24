document.addEventListener('DOMContentLoaded', function () {
    const bulkModal = document.getElementById('bulk-email-modal');
    const openBtn = document.getElementById('open-bulk-email');
    const closeBtn = document.getElementById('close-bulk-modal');
    const userListContainer = document.getElementById('bulk-user-list');
    const searchInput = document.getElementById('bulk-user-search');
    const selectAllBtn = document.getElementById('bulk-select-all');
    const sendBtn = document.getElementById('bulk-send-btn');
    const progressContainer = document.getElementById('bulk-progress-container');
    const progressFill = document.querySelector('.progress-fill');
    const progressText = document.querySelector('.bulk-progress-header span');

    if (!bulkModal || !openBtn || !closeBtn || !userListContainer || !searchInput || !selectAllBtn || !sendBtn || !progressContainer || !progressFill || !progressText) {
        return;
    }

    const source = bulkModal.dataset.source || 'iscritti';
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    let allUsers = [];
    let selectedAttachment = null;

    // Apertura Modal
    openBtn.addEventListener('click', function () {
        bulkModal.classList.add('active');
        document.body.classList.add('modal-open');
        loadUsers();
    });

    // Chiusura Modal
    closeBtn.addEventListener('click', function () {
        bulkModal.classList.remove('active');
        document.body.classList.remove('modal-open');
    });

    // Caricamento Utenti
    async function loadUsers() {
        try {
            const response = await fetch(`ajax_bulk_email.php?action=get_users&source=${encodeURIComponent(source)}&csrf_token=${encodeURIComponent(csrfToken)}`);
            const data = await response.json();
            if (data.success) {
                allUsers = data.users;
                renderUserList(allUsers);
            }
        } catch (error) {
            console.error("Errore caricamento utenti:", error);
        }
    }

    // Rendering Lista
    function renderUserList(users) {
        userListContainer.innerHTML = '';
        users.forEach(user => {
            const item = document.createElement('div');
            item.className = 'user-item' + (user.interessato_offerte == 0 ? ' disabled' : '');
            item.innerHTML = `
                <input type="checkbox" class="user-check" data-id="${user.id}" ${user.interessato_offerte == 0 ? 'disabled' : ''}>
                <div class="user-info">
                    ${user.cognome} ${user.nome}
                    <span>${user.email}</span>
                </div>
            `;

            if (user.interessato_offerte == 1) {
                item.addEventListener('click', function (e) {
                    if (e.target.tagName !== 'INPUT') {
                        const cb = item.querySelector('.user-check');
                        cb.checked = !cb.checked;
                    }
                });
            }
            userListContainer.appendChild(item);
        });
    }

    // Ricerca
    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const filtered = allUsers.filter(u =>
            (u.nome + ' ' + u.cognome).toLowerCase().includes(query) ||
            u.email.toLowerCase().includes(query)
        );
        renderUserList(filtered);
    });

    // Seleziona Tutti (Disponibili)
    selectAllBtn.addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('.user-check:not(:disabled)');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
    });

    // Caricamento Allegato
    document.getElementById('bulk-attachment').addEventListener('change', async function () {
        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('csrf_token', csrfToken);

        try {
            const resp = await fetch('ajax_bulk_email.php?action=upload_attachment', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                selectedAttachment = data.path;
                alert("File allegato correttamente!");
            } else {
                alert("Errore caricamento: " + data.error);
            }
        } catch (e) {
            alert("Errore durante l'upload.");
        }
    });

    // INVIO MASSIVO
    sendBtn.addEventListener('click', async function () {
        const selectedIds = Array.from(document.querySelectorAll('.user-check:checked')).map(cb => cb.dataset.id);
        const subject = document.getElementById('bulk-subject').value;
        const message = document.getElementById('bulk-message').value;
        const audienceLabel = source === 'rimessaggi' ? 'destinatari' : 'soci';

        if (selectedIds.length === 0) return alert("Seleziona almeno un destinatario!");
        if (!subject || !message) return alert("Oggetto e Messaggio sono obbligatori!");

        if (!confirm(`Stai per inviare questa email a ${selectedIds.length} ${audienceLabel}. Procedere?`)) return;

        bulkModal.classList.remove('active');
        progressContainer.style.display = 'block';

        let sent = 0;
        const total = selectedIds.length;

        for (const id of selectedIds) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append('source', source);
            formData.append('csrf_token', csrfToken);
            if (selectedAttachment) formData.append('attachment', selectedAttachment);

            try {
                const resp = await fetch('ajax_bulk_email.php?action=send_email', {
                    method: 'POST',
                    body: formData
                });
                const res = await resp.json();
            } catch (e) {
                console.error("Errore invio a ID " + id);
            }

            sent++;
            const pct = (sent / total) * 100;
            progressFill.style.width = pct + '%';
            progressText.innerText = `INVIO IN CORSO... ${sent}/${total}`;
        }

        progressText.innerText = "INVIO COMPLETATO!";
        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressFill.style.width = '0%';
            document.body.classList.remove('modal-open');
            location.reload();
        }, 3000);
    });
});
