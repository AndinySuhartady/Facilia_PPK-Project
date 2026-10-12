<?php $pageTitle = 'Detail Laporan'; require __DIR__ . '/../layouts/header.php'; ?>

<a class="link" href="<?= BASE_URL ?>/report/my">← Kembali ke Laporan Saya</a>

<div class="card" style="max-width:640px;margin-top:16px;">
    <div class="top-bar">
        <div>
            <h1 style="margin-bottom:4px;"><?= htmlspecialchars($report['facility_name']) ?></h1>
            <p class="muted"><?= htmlspecialchars($report['facility_location']) ?> · <?= htmlspecialchars($report['category']) ?></p>
        </div>
        <span class="badge badge-<?= $report['status'] ?>"><?= $report['status'] ?></span>
    </div>

    <div class="detail-grid">
        <div>
            <p class="label">Tanggal Lapor</p>
            <p class="value"><?= htmlspecialchars(date('d M Y H:i', strtotime($report['created_at']))) ?></p>
        </div>
        <?php if (!empty($report['resolved_at'])): ?>
            <div>
                <p class="label">Selesai Ditangani</p>
                <p class="value"><?= htmlspecialchars(date('d M Y H:i', strtotime($report['resolved_at']))) ?></p>
            </div>
        <?php endif; ?>
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
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
