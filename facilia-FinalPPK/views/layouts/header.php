<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= APP_NAME ?><?= isset($pageTitle)?' — '.$pageTitle:'' ?></title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/../../public/assets/css/style.css') ?>"></head><body>
<?php
require_once __DIR__ . '/../../app/models/Notification.php';
require_once __DIR__ . '/../../app/helpers/listing.php';
$loggedIn=isLoggedIn(); $role=currentRole();
$name=$_SESSION['name']??$_SESSION['user_name']??'Pengguna';
$requestPath=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'';
$isActive=static fn(string $path):string=>(str_starts_with($requestPath,BASE_URL.$path))?'active':'';
$unread=($loggedIn&&$role==='USER')?Notification::unreadCount((int)($_SESSION['user_id']??0)):0;
// Angka antrean di sidebar petugas: reservasi PENDING & laporan kerusakan BARU.
$petugasPendingReservations=0; $petugasNewReports=0;
if($loggedIn&&$role==='PETUGAS'){
 require_once __DIR__ . '/../../app/models/Reservation.php';
 require_once __DIR__ . '/../../app/models/Report.php';
 try{ $petugasPendingReservations=Reservation::countPending(); $petugasNewReports=Report::countNew(); }
 catch(Throwable $e){ error_log('sidebar count gagal: '.$e->getMessage()); }
}
// Angka di sidebar admin: user yang menunggu verifikasi.
$adminPendingUsers=0;
if($loggedIn&&$role==='ADMIN'){
 require_once __DIR__ . '/../../app/models/User.php';
 try{ $adminPendingUsers=User::countAll('PENDING'); }
 catch(Throwable $e){ error_log('sidebar count gagal: '.$e->getMessage()); }
}
$topbarSubtitle=$topbarSubtitle??'Kelola fasilitas kampus dengan mudah.';
?>
<?php if($loggedIn): ?>
<div class="app-shell"><aside class="app-sidebar">
 <a class="sidebar-brand" href="<?= BASE_URL ?>/facilities"><span class="sidebar-logo">F</span><span><strong>FACILIA</strong><small>Smart Facility Reservation</small></span></a>
 <div class="sidebar-profile"><span class="sidebar-avatar"><?= htmlspecialchars(strtoupper(substr($name,0,1))) ?></span><span><strong><?= $role==='ADMIN'?'Admin':($role==='PETUGAS'?'Petugas':'User') ?></strong><small><?= htmlspecialchars($name) ?></small><em>● Online</em></span></div>
 <p class="sidebar-label">MENU</p><nav class="sidebar-menu">
  <?php if($role==='PETUGAS'): ?>
   <a class="<?= $isActive('/petugas/dashboard') ?>" href="<?= BASE_URL ?>/petugas/dashboard"><span>▦</span>Dashboard</a>
   <a class="<?= $isActive('/petugas/reservations') ?>" href="<?= BASE_URL ?>/petugas/reservations"><span>◷</span>Antrean Reservasi<?= menuCount($petugasPendingReservations) ?></a>
   <a class="<?= $isActive('/petugas/reports') ?>" href="<?= BASE_URL ?>/petugas/reports"><span>⌕</span>Laporan Kerusakan<?= menuCount($petugasNewReports) ?></a>
   <a href="<?= BASE_URL ?>/petugas/dashboard#facilities"><span>⚒</span>Fasilitas &amp; Perbaikan</a>
  <?php endif; ?>
  <a class="<?= $isActive('/facilities') ?>" href="<?= BASE_URL ?>/facilities"><span>⌂</span><?= $role==='PETUGAS'?'Katalog Fasilitas':'Fasilitas' ?></a>
  <?php if($role==='USER'): ?>
   <a class="<?= $isActive('/reservation/history') ?>" href="<?= BASE_URL ?>/reservation/history"><span>◷</span>Riwayat Reservasi</a>
   <a class="<?= $isActive('/report/create') ?>" href="<?= BASE_URL ?>/report/create"><span>＋</span>Buat Laporan</a>
   <a class="<?= $isActive('/report/my') ?>" href="<?= BASE_URL ?>/report/my"><span>▤</span>Laporan Saya</a>
   <a class="<?= $isActive('/notifications') ?>" href="<?= BASE_URL ?>/notifications"><span>✉</span>Notifikasi<?= menuCount($unread) ?></a>
  <?php endif; ?>
  <a class="<?= $isActive('/account/password') ?>" href="<?= BASE_URL ?>/account/password"><span>⚙</span>Ubah Password</a>
  <?php if($role==='ADMIN'): ?>
   <a class="<?= $isActive('/admin/dashboard') ?>" href="<?= BASE_URL ?>/admin/dashboard"><span>▦</span>Dashboard Admin<?= menuCount($adminPendingUsers) ?></a>
   <a class="<?= $isActive('/admin/users') ?>" href="<?= BASE_URL ?>/admin/users"><span>♙</span>Manajemen User</a>
   <a class="<?= $isActive('/admin/facilities') ?>" href="<?= BASE_URL ?>/admin/facilities"><span>⚒</span>Kelola Fasilitas</a>
   <a class="<?= $isActive('/admin/analytics') ?>" href="<?= BASE_URL ?>/admin/analytics"><span>⌁</span>Analytics</a>
  <?php endif; ?>
 </nav><div class="sidebar-help"><strong>Butuh Bantuan?</strong><p>Hubungi admin jika mengalami kendala.</p></div>
</aside><div class="app-page"><header class="app-topbar"><div><strong><?= htmlspecialchars($topbarTitle??$pageTitle??'FACILIA') ?></strong><small><?= htmlspecialchars($topbarSubtitle) ?></small></div><form method="POST" action="<?= BASE_URL ?>/logout" class="logout-form"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><button type="submit">Keluar</button></form></header><main class="container">
<?php else: ?>
<nav class="navbar"><a class="brand" href="<?= BASE_URL ?>/facilities">FACILIA</a><div class="nav-links"><a href="<?= BASE_URL ?>/facilities">Fasilitas</a><a href="<?= BASE_URL ?>/login">Login</a><a href="<?= BASE_URL ?>/register">Register</a></div></nav><main class="container">
<?php endif; ?>
<?php $flash=getFlash(); ?>
<?php if(!empty($flash['success'])): ?><div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div><?php endif; ?>
<?php if(!empty($flash['error'])): ?><div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div><?php endif; ?>
<?php if(!empty($flash['errors'])): ?><div class="alert alert-error"><?= htmlspecialchars($flash['errors']) ?></div><?php endif; ?>
