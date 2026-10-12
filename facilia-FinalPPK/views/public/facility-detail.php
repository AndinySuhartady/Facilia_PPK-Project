<?php $pageTitle = $facility['name']; require __DIR__ . '/../layouts/header.php'; ?>

<div class="card">
    <h1><?= htmlspecialchars($facility['name']) ?> <span class="badge badge-<?= $facility['status'] ?>"><?= $facility['status'] ?></span></h1>
    <p style="color:#6b7280;">
        <?= htmlspecialchars($facility['type']) ?> · <?= htmlspecialchars($facility['location']) ?> · Kapasitas <?= (int) $facility['capacity'] ?> orang
    </p>
    <?php if (!empty($facility['description'])): ?>
        <p><?= nl2br(htmlspecialchars($facility['description'])) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Cek Availability</h2>
    <form method="GET" action="<?= BASE_URL ?>/facility/detail" style="display:flex;gap:10px;align-items:end;margin-bottom:16px;">
        <input type="hidden" name="id" value="<?= $facility['id'] ?>">
        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" min="<?= htmlspecialchars($minimumReservationDate) ?>" max="<?= htmlspecialchars($maximumReservationDate) ?>">
        </div>
        <button type="submit">Lihat</button>
    </form>

    <p style="font-size:0.85rem;color:#6b7280;">
        Kotak hijau = tersedia, kotak merah = tidak tersedia (sudah dipesan atau fasilitas dalam perbaikan) pada <?= htmlspecialchars($date) ?>.
        Ini hanya panduan visual — Anda tetap bebas memilih <strong>jam mulai & jam selesai sendiri</strong> di bawah,
        selama tidak menabrak jadwal yang sudah disetujui.
    </p>
    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:10px;">
        <?php foreach ($availabilityGrid as $seg): ?>
            <div title="<?= $seg['start'] ?>–<?= $seg['end'] ?>"
                 style="padding:6px 8px;border-radius:4px;font-size:0.72rem;
                        background:<?= $seg['available'] ? '#dcfce7' : '#fee2e2' ?>;
                        color:<?= $seg['available'] ? '#166534' : '#991b1b' ?>;">
                <?= $seg['start'] ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($facility['status'] !== 'ACTIVE'): ?>
    <div class="card">
        <p style="color:#991b1b;">Fasilitas ini sedang berstatus <strong><?= $facility['status'] ?></strong> dan tidak dapat dipesan saat ini.</p>
    </div>
<?php elseif (!isLoggedIn()): ?>
    <div class="card">
        <p>Silakan <a href="<?= BASE_URL ?>/login">login</a> untuk membuat reservasi.</p>
    </div>
<?php elseif (currentRole() === 'USER'): ?>
    <div class="card" style="max-width:480px;">
        <h2>Ajukan Reservasi</h2>
        <p style="font-size:0.85rem;color:#6b7280;">
            Reservasi wajib diajukan minimal H-1 (tidak dapat memesan untuk hari yang sama).<br>
            Pilih rentang jam bebas — mis. 07:00 sampai 09:00 untuk reservasi 2 jam sekaligus.
            Jam hanya boleh kelipatan 30 menit, antara 07:00–20:00.
        </p>
        <form method="POST" action="<?= BASE_URL ?>/reservation/create"
              onsubmit="return confirmReservationAction(this, 'Ajukan reservasi dengan jadwal tersebut?');">
            <input type="hidden" name="facility_id" value="<?= $facility['id'] ?>">
            <input type="hidden" name="reservation_date" value="<?= htmlspecialchars($date) ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="confirmed" value="0">

            <div class="form-group">
                <label>Tanggal reservasi</label>
                <input type="text" value="<?= htmlspecialchars($date) ?>" disabled>
            </div>

            <div style="display:flex;gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Jam mulai</label>
                    <select name="start_time" id="start_time" required>
                        <?php foreach (array_slice($timeOptions, 0, -1) as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Jam selesai</label>
                    <select name="end_time" id="end_time" required>
                        <?php foreach (array_slice($timeOptions, 1) as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Tujuan penggunaan *</label>
                <textarea name="purpose" rows="2" required maxlength="255" placeholder="mis. Rapat organisasi mahasiswa"></textarea>
            </div>

            <button type="submit">Ajukan Reservasi</button>
        </form>
    </div>

    <script>
        // Bantu UX: jam selesai otomatis mengikuti jam mulai + 30 menit,
        // tapi user tetap bebas mengubahnya ke rentang lebih panjang (mis. +2 jam).
        // Validasi sesungguhnya tetap dilakukan di server (lihat ReservationController::store()).
        const startSelect = document.getElementById('start_time');
        const endSelect = document.getElementById('end_time');

        function toMinutes(hhmm) {
            const [h, m] = hhmm.split(':').map(Number);
            return h * 60 + m;
        }

        startSelect.addEventListener('change', () => {
            const startMinutes = toMinutes(startSelect.value);
            const currentEndMinutes = toMinutes(endSelect.value);
            if (currentEndMinutes <= startMinutes) {
                for (const opt of endSelect.options) {
                    if (toMinutes(opt.value) === startMinutes + 30) {
                        endSelect.value = opt.value;
                        break;
                    }
                }
            }
        });
    </script>
<?php else: ?>
    <div class="card"><p>Hanya akun User yang dapat mengajukan reservasi.</p></div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
