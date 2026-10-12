<?php $pageTitle = 'Detail Reservasi Saya'; require __DIR__ . '/../layouts/header.php'; ?>
<a href="<?= BASE_URL ?>/reservation/history">← Kembali ke Riwayat Reservasi</a>
<div class="card">
<h1><?= htmlspecialchars($reservation['facility_name']) ?></h1>
<p>Nomor reservasi: #<?= (int) $reservation['id'] ?></p>
<span class="badge badge-<?= htmlspecialchars($reservation['status']) ?>"><?= htmlspecialchars($reservation['status']) ?></span>
<dl>
<?php foreach (['user_name'=>'Pemohon','facility_type'=>'Tipe fasilitas','facility_location'=>'Lokasi','reservation_date'=>'Tanggal','start_time'=>'Jam mulai (WIB)','end_time'=>'Jam selesai (WIB)','purpose'=>'Tujuan penggunaan','created_at'=>'Diajukan pada','updated_at'=>'Terakhir diperbarui','rejection_reason'=>'Alasan penolakan','cancellation_reason'=>'Alasan pembatalan','cancelled_at'=>'Dibatalkan pada'] as $key=>$label): ?>
<?php if (!empty($reservation[$key])): ?><dt><strong><?= $label ?></strong></dt><dd><?= nl2br(htmlspecialchars($reservation[$key])) ?></dd><?php endif; ?>
<?php endforeach; ?>
</dl>
<?php if (Reservation::canOwnerCancel($reservation)): ?>
<p>Pembatalan hanya diperbolehkan sebelum jam mulai reservasi.</p>
<form method="POST" action="<?= BASE_URL ?>/reservation/cancel" onsubmit="return confirmReservationAction(this, 'Yakin ingin membatalkan reservasi?');">
<input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>">
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
<input type="hidden" name="confirmed" value="0">
<div class="form-group"><label>Alasan pembatalan (opsional)</label>
<textarea name="cancellation_reason" rows="2" maxlength="255" placeholder="Jelaskan alasan pembatalan (min. 10 karakter jika diisi)"></textarea></div>
<button class="btn-danger" type="submit">Batalkan Reservasi</button>
</form>
<?php else: ?><p>Reservasi ini tidak dapat dibatalkan karena statusnya final atau waktu mulai sudah tercapai.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
