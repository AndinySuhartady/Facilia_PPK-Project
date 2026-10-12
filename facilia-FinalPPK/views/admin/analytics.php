<?php $pageTitle = 'Analytics'; require __DIR__ . '/../layouts/header.php'; ?>
<h1>Analytics Fasilitas</h1>
<form method="GET" class="filter-bar card">
<div class="form-group"><label>Dari Tanggal</label><input type="date" name="from" required value="<?= htmlspecialchars($dateFrom) ?>"></div>
<div class="form-group"><label>Sampai Tanggal</label><input type="date" name="to" required value="<?= htmlspecialchars($dateTo) ?>"></div>
<button type="submit">Filter</button>
</form>
<p>Periode <?= htmlspecialchars($dateFrom) ?> sampai <?= htmlspecialchars($dateTo) ?> (kedua tanggal dihitung).</p>
<div class="card">
<h2>Okupansi Fasilitas</h2>
<p>Okupansi = jam reservasi APPROVED ÷ (jumlah hari periode × 13 jam) × 100%. Jam operasional 07.00–20.00 setiap hari. Ini penggunaan terjadwal; belum mengurangi waktu perbaikan atau penonaktifan karena riwayat waktunya belum dicatat.</p>
<?php foreach (['xlsx'=>'Unduh Excel (.xlsx)','csv'=>'Unduh CSV'] as $format=>$label): ?>
<a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/analytics/export?<?= http_build_query(['from'=>$dateFrom,'to'=>$dateTo,'format'=>$format]) ?>"><?= $label ?></a>
<?php endforeach; ?>
<div style="overflow-x:auto"><table><thead><tr><th>Fasilitas</th><th>Lokasi</th><th>Booking disetujui</th><th>Jam terpakai</th><th>Jam periode</th><th>Okupansi</th></tr></thead><tbody>
<?php foreach ($occupancy as $row): ?><tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= htmlspecialchars($row['location']) ?></td><td><?= (int)$row['approved_count'] ?></td><td><?= number_format($row['used_hours'],2,',','.') ?></td><td><?= $row['available_hours'] ?></td><td><div style="background:#e5e7eb;border-radius:4px;height:10px;width:140px;"><div style="background:#4f46e5;border-radius:4px;height:10px;width:<?= (int) min(100, max(0, round($row['occupancy_percent']))) ?>%;"></div></div><?= number_format($row['occupancy_percent'],2,',','.') ?>%</td></tr><?php endforeach; ?>
<?php if (!$occupancy): ?><tr><td colspan="6">Belum ada fasilitas.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
<div class="card"><h2>Frekuensi Kerusakan per Fasilitas, Lokasi & Kategori</h2>
<?php foreach (['xlsx'=>'Unduh Excel (.xlsx)','csv'=>'Unduh CSV'] as $format=>$label): ?>
<a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/analytics/export-damage?<?= http_build_query(['from'=>$dateFrom,'to'=>$dateTo,'format'=>$format]) ?>"><?= $label ?></a>
<?php endforeach; ?>
<table><thead><tr><th>Fasilitas</th><th>Lokasi</th><th>Kategori</th><th>Jumlah laporan</th></tr></thead><tbody>
<?php foreach ($damageFrequency as $row): ?><tr><td><?= htmlspecialchars($row['facility_name']) ?></td><td><?= htmlspecialchars($row['location']) ?></td><td><?= htmlspecialchars($row['category']) ?></td><td><?= (int)$row['total'] ?></td></tr><?php endforeach; ?>
<?php if (!$damageFrequency): ?><tr><td colspan="4">Belum ada laporan dalam periode ini.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
