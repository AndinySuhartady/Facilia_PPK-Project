<?php $pageTitle = 'Detail Reservasi'; require __DIR__ . '/../layouts/header.php'; ?>

<a class="link" href="<?= BASE_URL ?>/petugas/reservations">← Kembali ke Antrean Reservasi</a>

<div class="card" style="max-width:680px;margin-top:16px;">
    <div class="top-bar">
        <div>
            <h1 style="margin-bottom:4px;"><?= htmlspecialchars($reservation['facility_name']) ?></h1>
            <p class="muted">Diajukan oleh <?= htmlspecialchars($reservation['user_name']) ?></p>
        </div>
        <span class="badge badge-<?= $reservation['status'] ?>"><?= $reservation['status'] ?></span>
    </div>

    <div class="detail-grid">
        <div><p class="label">Tanggal</p><p class="value"><?= htmlspecialchars($reservation['reservation_date']) ?></p></div>
        <div><p class="label">Jam</p><p class="value"><?= substr($reservation['start_time'], 0, 5) ?> – <?= substr($reservation['end_time'], 0, 5) ?></p></div>
    </div>

    <?php if (!empty($reservation['purpose'])): ?>
        <p class="label">Keperluan</p>
        <div class="text-block"><?= nl2br(htmlspecialchars($reservation['purpose'])) ?></div>
    <?php endif; ?>

    <?php if ($reservation['status'] === 'REJECTED' && !empty($reservation['rejection_reason'])): ?>
        <p class="label" style="margin-top:16px;">Alasan Penolakan</p>
        <div class="text-block"><?= nl2br(htmlspecialchars($reservation['rejection_reason'])) ?></div>
    <?php endif; ?>

    <?php if ($reservation['status'] === 'CANCELLED' && !empty($reservation['cancellation_reason'])): ?>
        <p class="label" style="margin-top:16px;">Alasan Pembatalan</p>
        <div class="text-block"><?= nl2br(htmlspecialchars($reservation['cancellation_reason'])) ?></div>
    <?php endif; ?>

    <?php if ($reservation['status'] === 'PENDING'): ?>
        <div style="margin-top:24px;">
            <form method="POST" style="margin-bottom:14px;"
                  onsubmit="return openReservationDecisionModal(this, 'approve');">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="decision" value="APPROVED">
                <input type="hidden" name="confirmed" value="0">
                <button type="submit" class="btn-success">Setujui Reservasi</button>
            </form>
            <form method="POST" onsubmit="return openReservationDecisionModal(this, 'reject');">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="decision" value="REJECTED">
                <input type="hidden" name="confirmed" value="0">
                <div class="form-group">
                    <label>Alasan penolakan <span class="required">*</span></label>
                    <textarea name="rejection_reason" required minlength="10" maxlength="255" placeholder="Jelaskan alasan reservasi ini ditolak (minimal 10 karakter)..."></textarea>
                </div>
                <button type="submit" class="btn-danger">Tolak Reservasi</button>
            </form>
        </div>
    <?php elseif ($reservation['status'] === 'APPROVED'): ?>
        <div class="alert alert-warning" style="margin-top:24px;">
            Pembatalan darurat hanya digunakan untuk kondisi mendesak (mis. fasilitas mendadak rusak).
        </div>
        <form method="POST" onsubmit="return openCancellationModal(this);">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="decision" value="CANCELLED">
            <input type="hidden" name="confirmed" value="0">
            <div class="form-group">
                <label>Alasan pembatalan <span class="required">*</span></label>
                <textarea name="cancellation_reason" required minlength="10" maxlength="255" placeholder="Jelaskan alasan pembatalan darurat (minimal 10 karakter)..."></textarea>
            </div>
            <button type="submit" class="btn-warning">Batalkan Reservasi</button>
        </form>
    <?php else: ?>
        <div class="text-block" style="margin-top:16px;">Reservasi ini sudah final dan tidak memerlukan tindakan lebih lanjut.</div>
    <?php endif; ?>
</div>

<?php if ($reservation['status'] === 'PENDING'): ?>
<div class="confirm-modal" id="reservationDecisionModal" hidden aria-hidden="true">
    <div class="confirm-modal__backdrop" data-close-decision-modal></div>
    <section class="confirm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="reservationDecisionTitle">
        <div class="confirm-modal__icon" aria-hidden="true">!</div>
        <div id="reservationDecisionStepOne">
            <h2 id="reservationDecisionTitle">Yakin ingin menyetujui reservasi?</h2>
            <p id="reservationDecisionDescription">Periksa kembali jadwal dan fasilitas sebelum melanjutkan.</p>
            <div class="confirm-modal__actions">
                <button type="button" class="confirm-modal__cancel" data-close-decision-modal>Kembali</button>
                <button type="button" class="confirm-modal__next" id="continueReservationDecision">Lanjutkan</button>
            </div>
        </div>
        <div id="reservationDecisionStepTwo" hidden>
            <h2>Konfirmasi terakhir, keputusan ini sudah benar?</h2>
            <p>Keputusan akan langsung disimpan dan ditampilkan kepada pengguna.</p>
            <div class="confirm-modal__actions">
                <button type="button" class="confirm-modal__cancel" id="backToReservationDecision">Kembali</button>
                <button type="button" class="confirm-modal__danger" id="confirmReservationDecision">Ya, Simpan</button>
            </div>
        </div>
    </section>
</div>
<?php endif; ?>

<?php if ($reservation['status'] === 'APPROVED'): ?>
<div class="confirm-modal" id="cancellationModal" hidden aria-hidden="true">
    <div class="confirm-modal__backdrop" data-close-modal></div>
    <section class="confirm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="cancellationModalTitle">
        <div class="confirm-modal__icon" aria-hidden="true">!</div>

        <div id="cancellationStepOne">
            <h2 id="cancellationModalTitle">Yakin ingin membatalkan reservasi?</h2>
            <p>Reservasi yang sudah disetujui akan dibatalkan dan tidak dapat dipulihkan melalui halaman ini.</p>
            <div class="confirm-modal__actions">
                <button type="button" class="confirm-modal__cancel" data-close-modal>Kembali</button>
                <button type="button" class="confirm-modal__next" id="continueCancellation">Lanjutkan</button>
            </div>
        </div>

        <div id="cancellationStepTwo" hidden>
            <h2>Konfirmasi terakhir, keputusan ini sudah benar?</h2>
            <p>Pastikan alasan pembatalan sudah sesuai sebelum menyimpan keputusan.</p>
            <div class="confirm-modal__actions">
                <button type="button" class="confirm-modal__cancel" id="backToFirstStep">Kembali</button>
                <button type="button" class="confirm-modal__danger" id="confirmCancellation">Ya, Batalkan</button>
            </div>
        </div>
    </section>
</div>
<?php endif; ?>

<script>
const reservationDecisionModal = document.getElementById('reservationDecisionModal');
const reservationDecisionStepOne = document.getElementById('reservationDecisionStepOne');
const reservationDecisionStepTwo = document.getElementById('reservationDecisionStepTwo');
let reservationDecisionForm = null;

function showReservationDecisionStep(step) {
    if (!reservationDecisionStepOne || !reservationDecisionStepTwo) return;
    reservationDecisionStepOne.hidden = step !== 1;
    reservationDecisionStepTwo.hidden = step !== 2;
}

function openReservationDecisionModal(form, action) {
    if (!reservationDecisionModal) return false;
    if (action === 'reject' && !form.reportValidity()) return false;

    reservationDecisionForm = form;
    const rejecting = action === 'reject';
    document.getElementById('reservationDecisionTitle').textContent = rejecting
        ? 'Yakin ingin menolak reservasi?'
        : 'Yakin ingin menyetujui reservasi?';
    document.getElementById('reservationDecisionDescription').textContent = rejecting
        ? 'Pastikan alasan penolakan sudah jelas dan sesuai.'
        : 'Periksa kembali jadwal dan fasilitas sebelum melanjutkan.';
    document.getElementById('confirmReservationDecision').textContent = rejecting
        ? 'Ya, Tolak'
        : 'Ya, Setujui';
    showReservationDecisionStep(1);
    reservationDecisionModal.hidden = false;
    reservationDecisionModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    document.getElementById('continueReservationDecision').focus();
    return false;
}

function closeReservationDecisionModal() {
    if (!reservationDecisionModal) return;
    reservationDecisionModal.hidden = true;
    reservationDecisionModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    reservationDecisionForm = null;
}

document.querySelectorAll('[data-close-decision-modal]').forEach((button) => {
    button.addEventListener('click', closeReservationDecisionModal);
});
document.getElementById('continueReservationDecision')?.addEventListener('click', () => {
    showReservationDecisionStep(2);
    document.getElementById('confirmReservationDecision').focus();
});
document.getElementById('backToReservationDecision')?.addEventListener('click', () => {
    showReservationDecisionStep(1);
    document.getElementById('continueReservationDecision').focus();
});
document.getElementById('confirmReservationDecision')?.addEventListener('click', () => {
    if (!reservationDecisionForm) return;
    reservationDecisionForm.querySelector('[name="confirmed"]').value = '1';
    reservationDecisionForm.submit();
});

const cancellationModal = document.getElementById('cancellationModal');
const cancellationStepOne = document.getElementById('cancellationStepOne');
const cancellationStepTwo = document.getElementById('cancellationStepTwo');
let cancellationForm = null;

function showCancellationStep(step) {
    if (!cancellationStepOne || !cancellationStepTwo) return;
    cancellationStepOne.hidden = step !== 1;
    cancellationStepTwo.hidden = step !== 2;
}

function openCancellationModal(form) {
    if (!cancellationModal) return false;
    cancellationForm = form;
    showCancellationStep(1);
    cancellationModal.hidden = false;
    cancellationModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    document.getElementById('continueCancellation').focus();
    return false;
}

function closeCancellationModal() {
    if (!cancellationModal) return;
    cancellationModal.hidden = true;
    cancellationModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    cancellationForm = null;
}

document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', closeCancellationModal);
});

document.getElementById('continueCancellation')?.addEventListener('click', () => {
    showCancellationStep(2);
    document.getElementById('confirmCancellation').focus();
});

document.getElementById('backToFirstStep')?.addEventListener('click', () => {
    showCancellationStep(1);
    document.getElementById('continueCancellation').focus();
});

document.getElementById('confirmCancellation')?.addEventListener('click', () => {
    if (!cancellationForm) return;
    cancellationForm.querySelector('[name="confirmed"]').value = '1';
    cancellationForm.submit();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && reservationDecisionModal && !reservationDecisionModal.hidden) {
        closeReservationDecisionModal();
        return;
    }
    if (event.key === 'Escape' && cancellationModal && !cancellationModal.hidden) {
        closeCancellationModal();
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
