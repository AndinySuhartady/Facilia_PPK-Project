<?php $pageTitle = 'Notifikasi'; require __DIR__ . '/../layouts/header.php'; ?>
<?php $unreadOnPage = count(array_filter($notifications, static fn($n) => !$n['is_read'])); ?>

<div class="top-bar">
    <div>
        <h1>Notifikasi <?= menuCount($unreadOnPage) ?></h1>
        <p class="subtitle">Angka berkurang otomatis saat detail notifikasi dibuka.</p>
    </div>
    <?php if ($unreadOnPage > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>/notifications/read">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-secondary">Tandai semua sudah dibaca</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Waktu</th><th>Pesan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($notifications as $n): ?>
                <tr class="notif-row <?= $n['is_read'] ? '' : 'is-unread' ?>">
                    <td style="white-space:nowrap;"><?php if (!$n['is_read']): ?><span class="notif-dot" title="Belum dibaca"></span><?php endif; ?><?= htmlspecialchars(date('d M Y H:i', strtotime($n['created_at']))) ?></td>
                    <td><?= htmlspecialchars($n['message']) ?></td>
                    <td>
                        <?php if (!empty($n['link'])): ?>
                            <a class="link" href="<?= BASE_URL ?>/notifications/open?id=<?= (int) $n['id'] ?>">Lihat detail</a>
                        <?php elseif (!$n['is_read']): ?>
                            <a class="link" href="<?= BASE_URL ?>/notifications/open?id=<?= (int) $n['id'] ?>">Tandai dibaca</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($notifications)): ?>
                <tr><td colspan="3" class="empty-state">Belum ada notifikasi.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
