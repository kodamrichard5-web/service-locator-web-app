<?php
/**
 * admin/reviews.php
 * List and moderate customer reviews.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
    if ($id) {
        $delete = $pdo->prepare('DELETE FROM reviews WHERE id = :id');
        $delete->execute(['id' => $id]);
        $success = 'Review deleted.';
    }
}

$reviews = $pdo->query(
    "SELECT r.*, u.name AS customer_name, v.name AS vendor_name, s.name AS service_name
     FROM reviews r
     JOIN users u    ON u.id = r.customer_id
     JOIN vendors v  ON v.id = r.vendor_id
     JOIN bookings b ON b.id = r.booking_id
     JOIN services s ON s.id = b.service_id
     ORDER BY r.created_at DESC"
)->fetchAll();

$avgRating = 0;
$totalReviews = count($reviews);
if ($totalReviews > 0) {
    $avgRating = array_sum(array_column($reviews, 'rating')) / $totalReviews;
}

$page_title = 'Reviews';
$page_subtitle = 'Customer feedback across all providers';
$active_admin_tab = 'reviews';
include 'includes/header.php';
?>

<?php if ($success): ?>
  <div class="mb-4 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
    <p class="text-sm text-brand-700 font-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">Total Reviews</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($totalReviews) ?></p>
  </div>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">Average Rating</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($avgRating, 1) ?> <span class="text-sm font-normal text-gray-400">/ 5</span></p>
  </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
  <?php if (empty($reviews)): ?>
    <div class="p-12 text-center">
      <span class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
        <?= svg_icon('star', 'w-6 h-6 text-brand-500') ?>
      </span>
      <p class="text-sm font-semibold text-gray-700">No reviews yet</p>
      <p class="text-sm text-gray-400 mt-1">Reviews appear here once customers rate completed bookings.</p>
    </div>
  <?php else: ?>
    <div class="divide-y divide-gray-50">
      <?php foreach ($reviews as $r): ?>
        <div class="p-5 flex items-start gap-3">
          <?= initials_avatar($r['customer_name'], 'w-9 h-9', 'text-xs') ?>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <p class="font-medium text-gray-800"><?= htmlspecialchars($r['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                <span class="text-gray-400 font-normal">reviewed</span>
                <?= htmlspecialchars($r['vendor_name'], ENT_QUOTES, 'UTF-8') ?>
              </p>
              <span class="text-xs text-gray-400"><?= time_ago($r['created_at']) ?></span>
            </div>
            <p class="text-xs text-gray-400 mb-1"><?= htmlspecialchars($r['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <?= star_rating((float) $r['rating'], 'w-3.5 h-3.5') ?>
            <?php if (!empty($r['comment'])): ?>
              <p class="text-sm text-gray-600 mt-1.5"><?= htmlspecialchars($r['comment'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
          </div>
          <form action="" method="POST" onsubmit="return confirm('Delete this review?');" class="shrink-0">
            <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
            <button type="submit" name="delete_review" class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center text-red-400">
              <?= svg_icon('trash', 'w-4 h-4') ?>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>