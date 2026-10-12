<?php $pageTitle = 'Detail Laporan'; require __DIR__ . '/../layouts/header.php'; ?>

<a class="link" href="<?= BASE_URL ?>/petugas/reports">← Kembali ke Antrean Laporan</a>

<div class="card" style="max-width:680px;margin-top:16px;">
    <div class="top-bar">
        <div>
            <h1 style="margin-bottom:4px;"><?= htmlspecialchars($report['facility_name']) ?></h1>
            <p class="muted">Dilaporkan oleh <?= htmlspecialchars($report['reporter_name']) ?> (<?= htmlspecialchars($report['reporter_email']) ?>)</p>
        </div>
        <span class="badge badge-<?= $report['status'] ?>"><?= $report['status'] ?></span>
    </div>

    <div class="detail-grid">
        <div><p class="label">Kategori</p><p class="value"><?= htmlspecialchars($report['category']) ?></p></div>
        <div><p class="label">Tanggal Lapor</p><p class="value"><?= htmlspecialchars(date('d M Y H:i', strtotime($report['created_at']))) ?></p></div>
    </div>

    <p class="label">Deskripsi</p>
    <div class="text-block"><?= nl2br(htmlspecialchars($report['description'])) ?></div>

    <?php if (!empty($report['photo_path'])): ?>
        <p class="label" style="margin-top:16px;">Foto Kerusakan</p>
        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($report['photo_path']) ?>" alt="Foto kerusakan" style="max-width:100%;border-radius:9px;">
    <?php else: ?>
        <p class="label" style="margin-top:16px;">Foto Kerusakan</p>
        <p class="muted" style="margin:0;">Tidak ada foto yang dilampirkan.</p>
    <?php endif; ?>

    <?php if (!empty($report['resolution_note'])): ?>
        <p class="label" style="margin-top:16px;">Catatan Petugas</p>
        <div class="text-block"><?= nl2br(htmlspecialchars($report['resolution_note'])) ?></div>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">

    <h2 style="font-size:1rem;">Status Fasilitas Saat Ini</h2>
    <p><span class="badge badge-<?= $facility['status'] ?>"><?= $facility['status'] ?></span></p>

    <?php if ($report['status'] === 'DIPROSES' && $facility['status'] === 'ACTIVE'): ?>
        <form method="POST" style="margin-bottom:20px;"
              onsubmit="return openFacilityStatusModal(this, 'Tandai fasilitas dalam perbaikan?', 'Reservasi yang sudah disetujui untuk fasilitas ini akan dibatalkan otomatis.', 'Ya, Tandai Maintenance');">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action_type" value="facility_status">
            <input type="hidden" name="facility_status" value="MAINTENANCE">
            <p class="muted">Reservasi APPROVED mendatang pada fasilitas ini akan dibatalkan otomatis dengan alasan maintenance.</p>
            <button type="submit" class="btn-warning">Tandai Sedang Diperbaiki (MAINTENANCE)</button>
        </form>
    <?php elseif (in_array($report['status'], ['SELESAI', 'DITOLAK'], true) && $facility['status'] === 'MAINTENANCE'): ?>
        <form method="POST" style="margin-bottom:20px;"
              onsubmit="return openFacilityStatusModal(this, 'Aktifkan kembali fasilitas ini?', 'Fasilitas akan kembali tersedia dan dapat dipesan oleh pengguna.', 'Ya, Aktifkan');">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action_type" value="facility_status">
            <input type="hidden" name="facility_status" value="ACTIVE">
            <button type="submit" class="btn-success">Aktifkan Kembali Fasilitas</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($availableStatuses)): ?>
        <h2 style="font-size:1rem;">Ubah Status Laporan</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action_type" value="report_status">

            <div class="form-group">
                <label>Status baru</label>
                <select name="status" required>
                    <option value="">Pilih status</option>
                    <?php foreach ($availableStatuses as $s): ?>
                        <option value="<?= $s ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Catatan petugas <span class="muted">(wajib untuk SELESAI/DITOLAK)</span></label>
                <textarea name="resolution_note" minlength="10" maxlength="2000" placeholder="Jelaskan tindakan yang dilakukan (minimal 10 karakter untuk status final)..."></textarea>
            </div>

            <button type="submit">Simpan Perubahan Status</button>
        </form>
    <?php else: ?>
        <div class="text-block">Laporan ini sudah berstatus final (<?= $report['status'] ?>) dan tidak dapat diubah lagi.</div>
    <?php endif; ?>
</div>

<div class="confirm-modal" id="facilityStatusModal" hidden aria-hidden="true">
    <div class="confirm-modal__backdrop" data-close-facility-modal></div>
    <section class="confirm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="facilityStatusModalTitle">
        <div class="confirm-modal__icon" aria-hidden="true">!</div>
        <h2 id="facilityStatusModalTitle">Konfirmasi perubahan status fasilitas</h2>
        <p id="facilityStatusModalMessage"></p>
        <div class="confirm-modal__actions">
            <button type="button" class="confirm-modal__cancel" data-close-facility-modal>Kembali</button>
            <button type="button" class="confirm-modal__next" id="confirmFacilityStatus">Ya, Lanjutkan</button>
        </div>
    </section>
</div>

<script>
const facilityStatusModal = document.getElementById('facilityStatusModal');
const facilityStatusModalTitle = document.getElementById('facilityStatusModalTitle');
const facilityStatusModalMessage = document.getElementById('facilityStatusModalMessage');
const confirmFacilityStatus = document.getElementById('confirmFacilityStatus');
let facilityStatusForm = null;

function openFacilityStatusModal(form, title, message, confirmLabel) {
    facilityStatusForm = form;
    facilityStatusModalTitle.textContent = title;
    facilityStatusModalMessage.textContent = message;
    confirmFacilityStatus.textContent = confirmLabel;
    facilityStatusModal.hidden = false;
    facilityStatusModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    confirmFacilityStatus.focus();
    return false;
}

function closeFacilityStatusModal() {
    facilityStatusModal.hidden = true;
    facilityStatusModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    facilityStatusForm = null;
}

document.querySelectorAll('[data-close-facility-modal]').forEach((button) => {
    button.addEventListener('click', closeFacilityStatusModal);
});

confirmFacilityStatus.addEventListener('click', () => {
    if (!facilityStatusForm) return;
    facilityStatusForm.submit();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !facilityStatusModal.hidden) {
        closeFacilityStatusModal();
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
