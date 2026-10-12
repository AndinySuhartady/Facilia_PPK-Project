<?php
$pageTitle = 'Dashboard Petugas';
$name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Petugas';
$topbarTitle = 'Selamat datang, ' . $name . '! 👋';
$topbarSubtitle = 'Kelola reservasi, laporan kerusakan, dan fasilitas dengan mudah.';
// Memakai layout bersama (sidebar + topbar) agar tampilan petugas konsisten di semua halaman.
require __DIR__ . '/../layouts/header.php';
?>
<style>
/* Gaya khusus konten dashboard petugas — sidebar/topbar memakai layout bersama. */
.pd .quick{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin:28px 0}
.pd .quick-card{display:block;padding:22px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;text-decoration:none;transition:.2s}
.pd .quick-card:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(15,23,42,.09)}
.pd .quick-card h3{display:flex;align-items:center;gap:10px;margin:0 0 7px;color:#2563eb;font-size:18px}
.pd .quick-card p{margin:0;color:#64748b}
.pd .section-title{margin:32px 0 15px}
.pd .stats{display:grid;grid-template-columns:repeat(5,1fr);gap:15px}
.pd .stat{padding:20px;background:#fff;border-radius:13px;box-shadow:0 5px 16px rgba(15,23,42,.05)}
.pd .stat-label{margin:0;color:#64748b;font-size:14px}
.pd .stat-number{margin:11px 0 0;font-size:29px}
.pd .purple{border-top:4px solid #8b5cf6}.pd .blue{border-top:4px solid #2563eb}.pd .orange{border-top:4px solid #f59e0b}
.pd .green{border-top:4px solid #22c55e}.pd .red{border-top:4px solid #ef4444}.pd .gray{border-top:4px solid #64748b}
.pd .panel{margin-top:28px;padding:24px;background:#fff;border-radius:14px;box-shadow:0 5px 18px rgba(15,23,42,.06);scroll-margin-top:20px}
.pd .panel-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:17px}
.pd .panel-title{margin:0}.pd .panel-subtitle{margin:6px 0 0;color:#64748b;font-size:13px}
.pd th{background:#f8fafc;font-size:12px}
.pd .empty{padding:22px;text-align:center;color:#64748b}
.pd .facility-ACTIVE{color:#166534;background:#dcfce7}.pd .facility-INACTIVE{color:#475569;background:#e2e8f0}.pd .facility-MAINTENANCE{color:#9a3412;background:#ffedd5}
@media(max-width:950px){.pd .stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:650px){.pd .quick,.pd .stats{grid-template-columns:1fr}.pd .panel-header{align-items:flex-start;flex-direction:column}}
</style>

<div class="pd">
  <h1>Dashboard Petugas</h1>
  <p class="subtitle">Pantau reservasi dan laporan fasilitas kampus.</p>

  <div class="quick">
    <a class="quick-card" href="<?= BASE_URL ?>/petugas/reports?status=BARU"><h3>Antrean Laporan<?= menuCount($reportStats['BARU']) ?></h3><p>Periksa dan tindak lanjuti laporan kerusakan.</p></a>
    <a class="quick-card" href="<?= BASE_URL ?>/petugas/reservations?status=PENDING"><h3>Antrean Reservasi<?= menuCount($reservationStats['PENDING']) ?></h3><p>Setujui atau tolak permintaan reservasi.</p></a>
  </div>

  <h3 class="section-title">Statistik Laporan</h3>
  <section class="stats">
    <article class="stat purple"><p class="stat-label">Total laporan</p><p class="stat-number"><?= $totalReports ?></p></article>
    <article class="stat blue"><p class="stat-label">Baru</p><p class="stat-number"><?= $reportStats['BARU'] ?></p></article>
    <article class="stat orange"><p class="stat-label">Diproses</p><p class="stat-number"><?= $reportStats['DIPROSES'] ?></p></article>
    <article class="stat green"><p class="stat-label">Selesai</p><p class="stat-number"><?= $reportStats['SELESAI'] ?></p></article>
    <article class="stat red"><p class="stat-label">Ditolak</p><p class="stat-number"><?= $reportStats['DITOLAK'] ?></p></article>
  </section>

  <h3 class="section-title">Statistik Reservasi</h3>
  <section class="stats">
    <article class="stat purple"><p class="stat-label">Total reservasi</p><p class="stat-number"><?= $totalReservations ?></p></article>
    <article class="stat orange"><p class="stat-label">Pending</p><p class="stat-number"><?= $reservationStats['PENDING'] ?></p></article>
    <article class="stat green"><p class="stat-label">Disetujui</p><p class="stat-number"><?= $reservationStats['APPROVED'] ?></p></article>
    <article class="stat red"><p class="stat-label">Ditolak</p><p class="stat-number"><?= $reservationStats['REJECTED'] ?></p></article>
    <article class="stat gray"><p class="stat-label">Dibatalkan</p><p class="stat-number"><?= $reservationStats['CANCELLED'] ?></p></article>
  </section>

  <section class="panel">
    <div class="panel-header"><h3 class="panel-title">Laporan Terbaru</h3><a class="link" href="<?= BASE_URL ?>/petugas/reports">Lihat semua</a></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>ID</th><th>Pelapor</th><th>Fasilitas</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($latestReports as $report): ?>
        <tr>
          <td>#<?= (int) $report['id'] ?></td>
          <td><?= htmlspecialchars($report['reporter_name']) ?></td>
          <td><?= htmlspecialchars($report['facility_name']) ?></td>
          <td><?= htmlspecialchars($report['category']) ?></td>
          <td><span class="badge badge-<?= htmlspecialchars($report['status']) ?>"><?= htmlspecialchars($report['status']) ?></span></td>
          <td><a class="link" href="<?= BASE_URL ?>/petugas/reports/detail?id=<?= (int) $report['id'] ?>">Tangani</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$latestReports): ?><tr><td colspan="6" class="empty">Belum ada laporan.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>

  <section class="panel">
    <div class="panel-header"><h3 class="panel-title">Reservasi Terbaru</h3><a class="link" href="<?= BASE_URL ?>/petugas/reservations">Lihat semua</a></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>ID</th><th>Pemohon</th><th>Fasilitas</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($latestReservations as $reservation): ?>
        <tr>
          <td>#<?= (int) $reservation['id'] ?></td>
          <td><?= htmlspecialchars($reservation['user_name']) ?></td>
          <td><?= htmlspecialchars($reservation['facility_name']) ?></td>
          <td><?= htmlspecialchars(date('d-m-Y', strtotime($reservation['reservation_date']))) ?></td>
          <td><?= htmlspecialchars(substr($reservation['start_time'], 0, 5)) ?> – <?= htmlspecialchars(substr($reservation['end_time'], 0, 5)) ?></td>
          <td><span class="badge badge-<?= htmlspecialchars($reservation['status']) ?>"><?= htmlspecialchars($reservation['status']) ?></span></td>
          <td><a class="link" href="<?= BASE_URL ?>/petugas/reservations/detail?id=<?= (int) $reservation['id'] ?>">Periksa</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$latestReservations): ?><tr><td colspan="7" class="empty">Belum ada reservasi.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>

  <section class="panel" id="facilities">
    <div class="panel-header"><div><h3 class="panel-title">Fasilitas &amp; Maintenance</h3><p class="panel-subtitle">Pantau status operasional fasilitas kampus.</p></div><a class="link" href="<?= BASE_URL ?>/petugas/reports">Lihat laporan</a></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Fasilitas</th><th>Jenis</th><th>Lokasi</th><th>Kapasitas</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($dashboardFacilities as $facility): ?>
        <tr>
          <td><strong><?= htmlspecialchars($facility['name']) ?></strong></td>
          <td><?= htmlspecialchars($facility['type']) ?></td>
          <td><?= htmlspecialchars($facility['location']) ?></td>
          <td><?= (int) $facility['capacity'] ?></td>
          <td><span class="badge facility-<?= htmlspecialchars($facility['status']) ?>"><?= $facility['status'] === 'MAINTENANCE' ? 'DALAM PERBAIKAN' : htmlspecialchars($facility['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$dashboardFacilities): ?><tr><td colspan="5" class="empty">Belum ada data fasilitas.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
