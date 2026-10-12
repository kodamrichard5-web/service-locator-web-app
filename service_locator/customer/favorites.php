<?php
/**
 * customer/favorites.php
 * Lists vendors the customer has saved. Also handles unfavoriting from this list.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];

// Handle unfavorite action (POST to avoid accidental removal via link prefetch/GET).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);
    if ($vendor_id) {
        $del = $pdo->prepare('DELETE FROM favorites WHERE customer_id = :cid AND vendor_id = :vid');
        $del->execute(['cid' => $customer_id, 'vid' => $vendor_id]);
    }
    header('Location: favorites.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT v.id, v.name, v.rating, v.price, s.name AS service_name
     FROM favorites f
     JOIN vendors v  ON v.id = f.vendor_id
     JOIN services s ON s.id = v.service_id
     WHERE f.customer_id = :cid
     ORDER BY f.created_at DESC"
);
$stmt->execute(['cid' => $customer_id]);
$favorites = $stmt->fetchAll();

$page_title = 'Favorites';
$active_tab = 'favorites';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl">

    <div class="px-5 pt-6">
      <div class="bg-white rounded-2xl shadow-sm py-6 px-5">
        <h1 class="text-3xl font-extrabold text-brand-900 text-center">Favorites</h1>
      </div>
    </div>

    <div class="px-5 mt-6">

      <?php if (empty($favorites)): ?>

        <div class="bg-white rounded-2xl shadow-sm p-10 flex flex-col items-center text-center">
          <span class="w-16 h-16 rounded-full bg-brand-50 flex items-center justify-center mb-4">
            <?= svg_icon('heart', 'w-7 h-7 text-brand-500') ?>
          </span>
          <h3 class="text-xl font-bold text-gray-800">No favorites yet</h3>
          <p class="text-gray-500 mt-2 mb-6">Save technicians you love for quick access later</p>
          <a href="home.php"
             class="bg-brand-500 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
            Browse Services
          </a>
        </div>

      <?php else: ?>

        <div class="space-y-3 pb-4">
          <?php foreach ($favorites as $vendor): ?>
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4 flex items-center gap-4">
              <?= initials_avatar($vendor['name'], 'w-14 h-14', 'text-lg') ?>
              <div class="min-w-0 flex-1">
                <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                  <?= svg_icon('star', 'w-3.5 h-3.5 text-yellow-400') ?>
                  <?= number_format((float) $vendor['rating'], 1) ?>
                  <span class="mx-1 text-gray-300">&bull;</span>
                  <?= htmlspecialchars($vendor['service_name'], ENT_QUOTES, 'UTF-8') ?>
                  <span class="mx-1 text-gray-300">&bull;</span>
                  <span class="font-semibold text-brand-700">$<?= number_format((float) $vendor['price'], 2) ?></span>
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <a href="vendor.php?id=<?= (int) $vendor['id'] ?>"
                   class="bg-brand-500 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors whitespace-nowrap">
                  View
                </a>
                <form action="" method="POST">
                  <input type="hidden" name="vendor_id" value="<?= (int) $vendor['id'] ?>">
                  <button type="submit" name="remove_favorite" aria-label="Remove from favorites"
                    class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4.5 h-4.5">
                      <path d="M12 20.25s-7.5-4.612-9.75-9.075C.6 7.53 2.7 4.5 6 4.5c2.1 0 3.6 1.2 6 3.75 2.4-2.55 3.9-3.75 6-3.75 3.3 0 5.4 3.03 3.75 6.675C19.5 15.638 12 20.25 12 20.25z"/>
                    </svg>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>