<?php
/**
 * customer/vendor.php
 * Public-facing profile for a single vendor/technician.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$vendor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vendor_id) {
    header('Location: home.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT v.*, s.name AS service_name, s.icon AS service_icon
     FROM vendors v
     JOIN services s ON s.id = v.service_id
     WHERE v.id = :id AND v.status = 'active'
     LIMIT 1"
);
$stmt->execute(['id' => $vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: home.php');
    exit;
}

// Handle favorite toggle (POST to self, then redirect to avoid resubmission on refresh).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
    $check = $pdo->prepare('SELECT id FROM favorites WHERE customer_id = :cid AND vendor_id = :vid');
    $check->execute(['cid' => $_SESSION['user_id'], 'vid' => $vendor_id]);

    if ($check->fetch()) {
        $del = $pdo->prepare('DELETE FROM favorites WHERE customer_id = :cid AND vendor_id = :vid');
        $del->execute(['cid' => $_SESSION['user_id'], 'vid' => $vendor_id]);
    } else {
        $add = $pdo->prepare('INSERT INTO favorites (customer_id, vendor_id) VALUES (:cid, :vid)');
        $add->execute(['cid' => $_SESSION['user_id'], 'vid' => $vendor_id]);
    }

    header('Location: vendor.php?id=' . $vendor_id);
    exit;
}

$favStmt = $pdo->prepare('SELECT id FROM favorites WHERE customer_id = :cid AND vendor_id = :vid');
$favStmt->execute(['cid' => $_SESSION['user_id'], 'vid' => $vendor_id]);
$isFavorited = (bool) $favStmt->fetch();

$page_title = $vendor['name'];
$active_tab = 'home';
include '../includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  #vendorMap { z-index: 0; }
</style>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-4xl bg-white min-h-screen shadow-sm">

    <!-- Header -->
    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="category.php?service_id=<?= (int) $vendor['service_id'] ?>" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900 flex-1">Technician Profile</h1>
      <form action="" method="POST">
        <input type="hidden" name="vendor_id" value="<?= (int) $vendor['id'] ?>">
        <button type="submit" name="toggle_favorite" aria-label="Toggle favorite"
          class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-colors <?= $isFavorited ? 'bg-red-50 text-red-500' : 'bg-brand-100 text-brand-700' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="<?= $isFavorited ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-7.5-4.612-9.75-9.075C.6 7.53 2.7 4.5 6 4.5c2.1 0 3.6 1.2 6 3.75 2.4-2.55 3.9-3.75 6-3.75 3.3 0 5.4 3.03 3.75 6.675C19.5 15.638 12 20.25 12 20.25z"/>
          </svg>
        </button>
      </form>
    </header>

    <div class="px-5 mt-4">

      <!-- Profile card -->
      <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 flex flex-col items-center text-center">
        <?= initials_avatar($vendor['name'], 'w-24 h-24', 'text-3xl') ?>

        <h2 class="text-2xl font-extrabold text-gray-900 mt-4"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></h2>

        <?php if ((int) $vendor['is_verified'] === 1): ?>
          <span class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 bg-brand-100 px-2.5 py-1 rounded-full">
            <?= svg_icon('check', 'w-3.5 h-3.5') ?> Verified
          </span>
        <?php endif; ?>

        <div class="flex items-center gap-1 mt-2">
          <?= star_rating((float) $vendor['rating'], 'w-4 h-4') ?>
          <span class="text-sm text-gray-600 ml-1"><?= number_format((float) $vendor['rating'], 1) ?></span>
        </div>

        <div class="flex items-center gap-2 mt-3 text-sm">
          <span class="inline-flex items-center gap-1.5 bg-brand-50 text-brand-700 px-3 py-1.5 rounded-full font-medium">
            <?= svg_icon($vendor['service_icon'], 'w-4 h-4') ?>
            <?= htmlspecialchars($vendor['service_name'], ENT_QUOTES, 'UTF-8') ?>
          </span>
          <span class="font-bold text-brand-900">GHC <?= number_format((float) $vendor['price'], 2) ?></span>
        </div>
      </div>

      <!-- Bio -->
      <div class="mt-5">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2">About</h3>
        <p class="text-sm text-gray-600 leading-relaxed">
          <?= $vendor['bio'] !== ''
              ? nl2br(htmlspecialchars($vendor['bio'], ENT_QUOTES, 'UTF-8'))
              : '<span class="text-gray-400 italic">This technician hasn\'t added a bio yet.</span>' ?>
        </p>
      </div>

      <!-- Location -->
      <div class="mt-5">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2">Location</h3>
        <?php if ($vendor['latitude'] && $vendor['longitude']): ?>
          <div id="vendorMap" class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm h-40"></div>
        <?php else: ?>
          <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 h-24 flex items-center justify-center">
            <p class="text-xs text-gray-400">This technician hasn't set their location yet.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Book Now -->
      <div class="mt-8 pb-6">
        <a href="booking.php?vendor_id=<?= (int) $vendor['id'] ?>"
           class="block w-full text-center bg-brand-500 hover:bg-brand-700 text-white font-bold text-lg py-4 rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
          Book Now
        </a>
      </div>

    </div>
  </div>
</div>

<?php if ($vendor['latitude'] && $vendor['longitude']): ?>
<script>
  const vendorMap = L.map('vendorMap', { zoomControl: false, scrollWheelZoom: false })
    .setView([<?= (float) $vendor['latitude'] ?>, <?= (float) $vendor['longitude'] ?>], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(vendorMap);

  L.marker([<?= (float) $vendor['latitude'] ?>, <?= (float) $vendor['longitude'] ?>]).addTo(vendorMap);
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>