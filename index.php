<?php
require_once 'config.php';

function rupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'jual') {
        $produk_id = (int)($_POST['produk_id'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 0);

        if ($produk_id <= 0 || $jumlah <= 0) {
            $error = 'Pilih produk dan masukkan jumlah yang valid.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ? FOR UPDATE');
                $stmt->execute([$produk_id]);
                $produk = $stmt->fetch();

                if (!$produk) {
                    throw new Exception('Produk tidak ditemukan.');
                }
                if ($jumlah > (int)$produk['stok']) {
                    throw new Exception('Stok tidak mencukupi.');
                }

                $total = (int)$produk['harga'] * $jumlah;
                $stmt = $pdo->prepare('INSERT INTO transaksi (jenis, produk_id, jumlah, nominal, keterangan) VALUES ("penjualan", ?, ?, ?, ?)');
                $stmt->execute([$produk_id, $jumlah, $total, 'Penjualan ' . $produk['nama']]);

                $stmt = $pdo->prepare('UPDATE produk SET stok = stok - ? WHERE id = ?');
                $stmt->execute([$jumlah, $produk_id]);

                $pdo->commit();
                $pesan = 'Penjualan berhasil dicatat.';
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
    } elseif ($aksi === 'catat') {
        $jenis = $_POST['jenis'] ?? '';
        $nominal = (int)($_POST['nominal'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');

        if (!in_array($jenis, ['pemasukan', 'pengeluaran'], true) || $nominal <= 0 || $keterangan === '') {
            $error = 'Isi jenis, nominal positif, dan keterangan dengan benar.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO transaksi (jenis, produk_id, jumlah, nominal, keterangan) VALUES (?, NULL, 0, ?, ?)');
            $stmt->execute([$jenis, $nominal, $keterangan]);
            $pesan = 'Transaksi berhasil dicatat.';
        }
    } elseif ($aksi === 'tambah_stok') {
        $produk_id = (int)($_POST['produk_id'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        if ($produk_id <= 0 || $jumlah <= 0) {
            $error = 'Pilih produk dan masukkan jumlah stok yang valid.';
        } else {
            $stmt = $pdo->prepare('UPDATE produk SET stok = stok + ? WHERE id = ?');
            $stmt->execute([$jumlah, $produk_id]);
            $pesan = 'Stok berhasil ditambahkan.';
        }
    }
}

$produk = $pdo->query('SELECT * FROM produk ORDER BY nama')->fetchAll();
$ringkasan = $pdo->query("
    SELECT
      COALESCE(SUM(CASE WHEN jenis = 'penjualan' THEN nominal ELSE 0 END), 0) AS penjualan,
      COALESCE(SUM(CASE WHEN jenis = 'pemasukan' THEN nominal ELSE 0 END), 0) AS pemasukan_lain,
      COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' THEN nominal ELSE 0 END), 0) AS pengeluaran
    FROM transaksi
")->fetch();

$totalMasuk = (int)$ringkasan['penjualan'] + (int)$ringkasan['pemasukan_lain'];
$saldo = $totalMasuk - (int)$ringkasan['pengeluaran'];

$riwayat = $pdo->query("
    SELECT t.*, p.nama AS produk_nama
    FROM transaksi t
    LEFT JOIN produk p ON p.id = t.produk_id
    ORDER BY t.created_at DESC, t.id DESC
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>510 Band | Merchandise Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="brand-mark">510</div>
    <div>
        <p class="eyebrow">BANDUNG • OFFICIAL MERCH</p>
        <h1>510 Band <span>Merchandise Manager</span></h1>
    </div>
    <div class="status"><span></span> LOCAL SYSTEM</div>
</header>

<main class="container">
    <section class="intro hero-banner">
        <div class="hero-copy">
            <p class="eyebrow">FINANCE DASHBOARD • 510 BAND</p>
            <h2>Kelola merchandise.<br><em>Pantau setiap transaksi.</em></h2>
            <p class="muted">Catat penjualan, pemasukan, pengeluaran, dan stok merchandise dalam satu tempat.</p>
        </div>
        <div class="intro-note">510<br><small>SPIRITUAL ERA</small></div>
    </section>

    <?php if ($pesan): ?><div class="notice success"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <section class="stats">
        <article class="stat-card"><p>Total Penjualan</p><h3><?= rupiah($ringkasan['penjualan']) ?></h3><small>Omzet merchandise</small></article>
        <article class="stat-card"><p>Pemasukan Lain</p><h3><?= rupiah($ringkasan['pemasukan_lain']) ?></h3><small>Pemasukan tambahan</small></article>
        <article class="stat-card"><p>Total Pengeluaran</p><h3><?= rupiah($ringkasan['pengeluaran']) ?></h3><small>Biaya operasional</small></article>
        <article class="stat-card highlight"><p>Saldo Bersih</p><h3><?= rupiah($saldo) ?></h3><small><?= $saldo >= 0 ? 'Surplus sementara' : 'Defisit sementara' ?></small></article>
    </section>

    <section class="workspace">
        <div class="panel">
            <div class="panel-heading"><div><p class="eyebrow">01 / TRANSACTION</p><h2>Catat Penjualan</h2></div><span class="icon">↗</span></div>
            <form method="post">
                <input type="hidden" name="aksi" value="jual">
                <label for="produk_id">Produk merchandise</label>
                <select name="produk_id" id="produk_id" required>
                    <option value="">Pilih produk...</option>
                    <?php foreach ($produk as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> — <?= rupiah($p['harga']) ?> (stok <?= $p['stok'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <label for="jumlah_jual">Jumlah terjual</label>
                <input type="number" id="jumlah_jual" name="jumlah" min="1" required placeholder="Contoh: 2">
                <button type="submit">Simpan Penjualan <span>→</span></button>
            </form>
        </div>

        <div class="panel">
            <div class="panel-heading"><div><p class="eyebrow">02 / CASH FLOW</p><h2>Pemasukan & Pengeluaran</h2></div><span class="icon">＋</span></div>
            <form method="post">
                <input type="hidden" name="aksi" value="catat">
                <label for="jenis">Jenis transaksi</label>
                <select name="jenis" id="jenis" required>
                    <option value="pemasukan">Pemasukan tambahan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
                <label for="nominal">Nominal (Rupiah)</label>
                <input type="number" id="nominal" name="nominal" min="1" required placeholder="Contoh: 50000">
                <label for="keterangan">Keterangan</label>
                <input type="text" id="keterangan" name="keterangan" required maxlength="150" placeholder="Contoh: biaya packaging">
                <button type="submit">Simpan Transaksi <span>→</span></button>
            </form>
        </div>
    </section>

    <section class="panel stock-panel">
        <div class="panel-heading"><div><p class="eyebrow">03 / INVENTORY</p><h2>Stok Merchandise</h2></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Produk</th><th>Harga</th><th>Stok tersedia</th></tr></thead>
                <tbody>
                <?php foreach ($produk as $p): ?>
                    <tr><td><?= htmlspecialchars($p['nama']) ?></td><td><?= rupiah($p['harga']) ?></td><td><span class="stock-badge"><?= $p['stok'] ?> unit</span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" class="inline-form">
            <input type="hidden" name="aksi" value="tambah_stok">
            <select name="produk_id" required aria-label="Pilih produk">
                <option value="">Pilih produk untuk tambah stok...</option>
                <?php foreach ($produk as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="jumlah" min="1" required placeholder="Jumlah stok">
            <button type="submit" class="secondary">Tambah Stok</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading"><div><p class="eyebrow">04 / ACTIVITY LOG</p><h2>Riwayat Transaksi</h2></div><span class="muted small">20 transaksi terakhir</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Jenis</th><th>Keterangan</th><th>Jumlah</th><th>Nominal</th></tr></thead>
                <tbody>
                <?php if (!$riwayat): ?>
                    <tr><td colspan="5" class="empty">Belum ada transaksi. Mulai catat transaksi pertamamu.</td></tr>
                <?php else: foreach ($riwayat as $t): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                        <td><span class="type <?= htmlspecialchars($t['jenis']) ?>"><?= ucfirst($t['jenis']) ?></span></td>
                        <td><?= htmlspecialchars($t['produk_nama'] ?: $t['keterangan']) ?></td>
                        <td><?= $t['jenis'] === 'penjualan' ? (int)$t['jumlah'] . ' unit' : '—' ?></td>
                        <td class="money"><?= rupiah($t['nominal']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <footer><span>510 BAND • MERCH MANAGEMENT</span><span>Built for local development</span></footer>
</main>
</body>
</html>
