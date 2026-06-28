<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$ids = array_keys($_SESSION['cart']);
$in = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

$items = [];
$subtotal = 0;
$stockError = '';

// Validasi stok sebelum checkout
foreach ($products as $p) {
    $qty = $_SESSION['cart'][$p['id']];
    if ($p['stock'] <= 0) {
        $stockError .= $p['name'] . ' sudah habis terjual. ';
    } elseif ($qty > $p['stock']) {
        $stockError .= $p['name'] . ' hanya tersedia ' . $p['stock'] . ' unit. ';
    } else {
        $items[] = ['product' => $p, 'qty' => $qty];
        $subtotal += $p['price'] * $qty;
    }
}

// Jika ada error stok, kembalikan ke cart
if ($stockError && !empty($_POST)) {
    $_SESSION['flash'] = 'Error stok: ' . $stockError;
    header('Location: cart.php');
    exit;
}
$shipping = 20000;
$total = $subtotal + $shipping;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pay = $_POST['pay'] ?? '';

    if (!$name || !$phone || !$city || !$address || !in_array($pay, ['qris','transfer','cod'])) {
        $error = 'Lengkapi semua data dan pilih metode pembayaran.';
    } else {
        $orderCode = 'KL-' . random_int(100000, 999999);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO orders
                (order_code, user_id, recipient_name, phone, city, address, payment_method, subtotal, shipping_fee, total, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,'paid')");
            $stmt->execute([$orderCode, $_SESSION['user_id'], $name, $phone, $city, $address, $pay, $subtotal, $shipping, $total]);
            $orderId = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, qty) VALUES (?,?,?,?,?)");
            foreach ($items as $it) {
                $stmtItem->execute([$orderId, $it['product']['id'], $it['product']['name'], $it['product']['price'], $it['qty']]);
                
                // Kurangi stok barang
                $stmtReduce = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmtReduce->execute([$it['qty'], $it['product']['id']]);
            }

            $stmtLog = $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?, 'paid')");
            $stmtLog->execute([$orderId]);

            $pdo->commit();
            $_SESSION['cart'] = [];
            header('Location: order_detail.php?code=' . $orderCode);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan pesanan: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>
<div class="section">
  <div class="section-head"><h2>Checkout</h2></div>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="checkout-grid">
      <div>
        <div class="field"><label>Nama Penerima</label><input name="name" value="<?= htmlspecialchars($_SESSION['user_name']) ?>" required></div>
        <div class="field-row">
          <div class="field"><label>No. HP</label><input name="phone" required></div>
          <div class="field"><label>Kota</label><input name="city" required></div>
        </div>
        <div class="field"><label>Alamat Lengkap</label><textarea name="address" rows="3" required></textarea></div>

        <div class="field" style="margin-top:20px;">
          <label style="font-size:0.9rem;">Metode Pembayaran</label>
          <div class="pay-methods">
            <label class="pay-opt">
              <input type="radio" name="pay" value="qris" checked onchange="updatePaymentDetail()"> 
              <div>
                <div style="font-weight:600;">QRIS</div>
                <div style="font-size:0.78rem;opacity:0.65;">Scan & bayar instan</div>
              </div>
            </label>
            <label class="pay-opt">
              <input type="radio" name="pay" value="transfer" onchange="updatePaymentDetail()"> 
              <div>
                <div style="font-weight:600;">Transfer Bank</div>
                <div style="font-size:0.78rem;opacity:0.65;">VA BCA / BNI / Mandiri</div>
              </div>
            </label>
            <label class="pay-opt">
              <input type="radio" name="pay" value="cod" onchange="updatePaymentDetail()"> 
              <div>
                <div style="font-weight:600;">Bayar di Tempat (COD)</div>
                <div style="font-size:0.78rem;opacity:0.65;">Bayar saat barang tiba</div>
              </div>
            </label>
          </div>
          
          <!-- Detail Pembayaran -->
          <div id="paymentDetail" style="margin-top:18px;"></div>
        </div>
      </div>

      <div class="summary-box">
        <h4 class="display" style="margin-bottom:14px;">Ringkasan Pesanan</h4>
        <?php foreach ($items as $it): ?>
          <div class="row"><span><?= htmlspecialchars($it['product']['name']) ?> ×<?= $it['qty'] ?></span><span><?= rupiah($it['product']['price'] * $it['qty']) ?></span></div>
        <?php endforeach; ?>
        <div class="row" style="margin-top:12px;"><span>Subtotal</span><span><?= rupiah($subtotal) ?></span></div>
        <div class="row"><span>Ongkos Kirim</span><span><?= rupiah($shipping) ?></span></div>
        <div class="row total"><span>Total</span><span><?= rupiah($total) ?></span></div>
        <button class="btn-main" type="submit" style="margin-top:14px;">Bayar Sekarang</button>
        <p style="font-size:0.72rem;color:var(--roast);opacity:0.6;margin-top:10px;">*Simulasi untuk tugas — tidak ada transaksi nyata.</p>
      </div>
    </div>
  </form>
</div>

<script>
function updatePaymentDetail() {
  const selectedPayment = document.querySelector('input[name="pay"]:checked').value;
  const detailDiv = document.getElementById('paymentDetail');
  
  let html = '';
  
  if (selectedPayment === 'qris') {
    html = `
      <div class="payment-detail">
        <h5 style="font-weight:600;margin-bottom:12px;font-size:0.95rem;">📱 QRIS - Scan Kode QR</h5>
        <div style="background:var(--paper);padding:20px;border-radius:12px;text-align:center;">
          <div style="background:#fff;padding:16px;border-radius:8px;display:inline-block;margin-bottom:12px;">
            <img src="assets/img/qr-payment.svg" alt="QRIS QR Code" style="width:200px;height:200px;border:2px solid #000;border-radius:4px;">
          </div>
          <div style="font-size:0.85rem;color:var(--roast);">
            <p><strong>Kopi Lereng QRIS</strong></p>
            <p style="margin-top:6px;opacity:0.8;">Arahkan kamera ponsel Anda ke QR code di atas</p>
            <p style="opacity:0.7;font-size:0.8rem;">atau pilih aplikasi pembayaran Anda (GCash, OVO, Dana, DANA, dll)</p>
          </div>
        </div>
      </div>
    `;
  } 
  else if (selectedPayment === 'transfer') {
    html = `
      <div class="payment-detail">
        <h5 style="font-weight:600;margin-bottom:12px;font-size:0.95rem;">🏦 Transfer Bank</h5>
        <div style="background:var(--paper);padding:16px;border-radius:12px;">
          
          <!-- BCA -->
          <button type="button" onclick="toggleBankDetail('bca')" style="width:100%;text-align:left;border:none;padding:12px;background:#fff;border-radius:8px;border-left:4px solid #FF6B35;cursor:pointer;margin-bottom:12px;transition:all 0.2s ease;" onmouseover="this.style.backgroundColor='#fff8f3'" onmouseout="this.style.backgroundColor='#fff'">
            <div style="font-weight:600;color:#2B1B14;">🔴 BCA</div>
            <div style="font-size:0.8rem;color:#FF6B35;">Klik untuk lihat nomor rekening →</div>
          </button>
          <div id="bca-detail" style="display:none;margin-bottom:12px;padding:12px;background:#fff;border-radius:8px;border-left:4px solid #FF6B35;animation:slideDown 0.3s ease;">
            <div style="font-size:0.85rem;color:var(--roast);">
              <div style="margin-bottom:8px;">No. Rekening: <span style="font-family:'JetBrains Mono';font-weight:600;cursor:pointer;padding:4px 8px;background:#ffe6cc;border-radius:4px;" onclick="copyToClipboard('1234567890', this)">1234567890 📋</span></div>
              <div>Atas Nama: <span style="font-weight:600;">Kopi Lereng</span></div>
            </div>
          </div>
          
          <!-- BNI -->
          <button type="button" onclick="toggleBankDetail('bni')" style="width:100%;text-align:left;border:none;padding:12px;background:#fff;border-radius:8px;border-left:4px solid #004B87;cursor:pointer;margin-bottom:12px;transition:all 0.2s ease;" onmouseover="this.style.backgroundColor='#f0f8ff'" onmouseout="this.style.backgroundColor='#fff'">
            <div style="font-weight:600;color:#2B1B14;">🟦 BNI</div>
            <div style="font-size:0.8rem;color:#004B87;">Klik untuk lihat nomor rekening →</div>
          </button>
          <div id="bni-detail" style="display:none;margin-bottom:12px;padding:12px;background:#fff;border-radius:8px;border-left:4px solid #004B87;animation:slideDown 0.3s ease;">
            <div style="font-size:0.85rem;color:var(--roast);">
              <div style="margin-bottom:8px;">No. Rekening: <span style="font-family:'JetBrains Mono';font-weight:600;cursor:pointer;padding:4px 8px;background:#e6f0ff;border-radius:4px;" onclick="copyToClipboard('0987654321', this)">0987654321 📋</span></div>
              <div>Atas Nama: <span style="font-weight:600;">Kopi Lereng</span></div>
            </div>
          </div>
          
          <!-- Mandiri -->
          <button type="button" onclick="toggleBankDetail('mandiri')" style="width:100%;text-align:left;border:none;padding:12px;background:#fff;border-radius:8px;border-left:4px solid #FF0000;cursor:pointer;margin-bottom:12px;transition:all 0.2s ease;" onmouseover="this.style.backgroundColor='#ffe6e6'" onmouseout="this.style.backgroundColor='#fff'">
            <div style="font-weight:600;color:#2B1B14;">🔵 Mandiri</div>
            <div style="font-size:0.8rem;color:#FF0000;">Klik untuk lihat nomor rekening →</div>
          </button>
          <div id="mandiri-detail" style="display:none;margin-bottom:12px;padding:12px;background:#fff;border-radius:8px;border-left:4px solid #FF0000;animation:slideDown 0.3s ease;">
            <div style="font-size:0.85rem;color:var(--roast);">
              <div style="margin-bottom:8px;">No. Rekening: <span style="font-family:'JetBrains Mono';font-weight:600;cursor:pointer;padding:4px 8px;background:#ffe6e6;border-radius:4px;" onclick="copyToClipboard('5555666677778', this)">5555666677778 📋</span></div>
              <div>Atas Nama: <span style="font-weight:600;">Kopi Lereng</span></div>
            </div>
          </div>
          
          <div style="margin-top:12px;padding:10px;background:rgba(181,101,29,0.1);border-radius:8px;font-size:0.8rem;color:var(--roast);">
            <strong>⚠️ Penting:</strong> Setelah transfer, tunggu konfirmasi pembayaran (maksimal 30 menit).
          </div>
        </div>
      </div>
    `;
  } 
  else if (selectedPayment === 'cod') {
    html = `
      <div class="payment-detail">
        <h5 style="font-weight:600;margin-bottom:12px;font-size:0.95rem;">🚚 Bayar di Tempat (COD)</h5>
        <div style="background:var(--paper);padding:16px;border-radius:12px;">
          <div style="background:#fff;padding:14px;border-radius:8px;border-left:4px solid var(--leaf);">
            <ul style="margin:0;padding-left:20px;font-size:0.9rem;color:var(--roast);line-height:1.8;">
              <li>Pembayaran dilakukan saat paket tiba di tangan Anda</li>
              <li>Siapkan uang pas atau bulat sesuai total pesanan</li>
              <li>Kurir akan meminta konfirmasi penerimaan paket</li>
              <li>Biaya tambahan COD mungkin berlaku (jika ada)</li>
            </ul>
          </div>
          <div style="margin-top:12px;padding:10px;background:rgba(92,107,71,0.1);border-radius:8px;font-size:0.8rem;color:var(--roast);">
            <strong>💡 Tips:</strong> Pastikan nomor HP Anda aktif agar kurir bisa menghubungi.
          </div>
        </div>
      </div>
    `;
  }
  
  detailDiv.innerHTML = html;
}

// Panggil fungsi saat halaman dimuat untuk menampilkan detail QRIS (default)
document.addEventListener('DOMContentLoaded', updatePaymentDetail);

function toggleBankDetail(bankName) {
  const detailDiv = document.getElementById(bankName + '-detail');
  const isVisible = detailDiv.style.display !== 'none';
  detailDiv.style.display = isVisible ? 'none' : 'block';
}

function copyToClipboard(text, element) {
  navigator.clipboard.writeText(text).then(() => {
    const originalText = element.textContent;
    element.textContent = '✓ Tersalin!';
    element.style.backgroundColor = '#d4edda';
    setTimeout(() => {
      element.textContent = originalText;
      element.style.backgroundColor = element.parentElement.style.backgroundColor || '#ffe6cc';
    }, 2000);
  }).catch(() => {
    alert('Gagal menyalin, coba manual copy.');
  });
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
