<?php
/**
 * customer/category.php
 * Lists active vendors for a single service category.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);

if (!$service_id) {
    header('Location: home.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, icon FROM services WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $service_id]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: home.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, name, rating, price, bio
     FROM vendors
     WHERE service_id = :service_id AND status = 'active'
     ORDER BY rating DESC, name ASC"
);
$stmt->execute(['service_id' => $service_id]);
$vendors = $stmt->fetchAll();

$page_title = $service['name'];
$active_tab = 'home';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-4xl bg-white min-h-screen shadow-sm">

    <!-- Header -->
    <header class="px-5 pt-6 pb-4 flex items-center gap-3">
      <a href="home.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <div class="flex items-center gap-2 min-w-0">
        <span class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
          <?= svg_icon($service['icon'], 'w-4.5 h-4.5 text-brand-700') ?>
        </span>
        <h1 class="text-xl font-extrabold text-brand-900 truncate"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
    </header>

    <div class="px-5">
      <?php if (empty($vendors)): ?>

        <div class="bg-brand-50 border border-dashed border-brand-200 rounded-2xl py-12 px-6 flex flex-col items-center text-center mt-4">
          <span class="w-14 h-14 rounded-full bg-white flex items-center justify-center mb-3 shadow-sm">
            <?= svg_icon('briefcase', 'w-6 h-6 text-brand-600') ?>
          </span>
          <p class="text-base font-semibold text-gray-700">No professionals available yet</p>
          <p class="text-sm text-gray-500 mt-1">Check back soon, or try another service category.</p>
          <a href="home.php" class="mt-5 bg-brand-500 hover:bg-brand-700 text-white font-semibold px-6 py-2.5 rounded-xl transition-colors">
            Browse Other Services
          </a>
        </div>

      <?php else: ?>

        <p class="text-sm text-gray-500 mb-4"><?= count($vendors) ?> professional<?= count($vendors) === 1 ? '' : 's' ?> available</p>

        <div class="space-y-3 pb-4">
          <?php foreach ($vendors as $vendor): ?>
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4 flex items-center gap-4">
              <?= initials_avatar($vendor['name'], 'w-14 h-14', 'text-lg') ?>
              <div class="min-w-0 flex-1">
                <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                  <?= svg_icon('star', 'w-3.5 h-3.5 text-yellow-400') ?>
                  <?= number_format((float) $vendor['rating'], 1) ?>
                  <span class="mx-1 text-gray-300">&bull;</span>
                  <span class="font-semibold text-brand-700">$<?= number_format((float) $vendor['price'], 2) ?></span>
                </p>
              </div>
              <a href="vendor.php?id=<?= (int) $vendor['id'] ?>"
                 class="shrink-0 bg-brand-500 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors whitespace-nowrap">
                View Profile
              </a>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>