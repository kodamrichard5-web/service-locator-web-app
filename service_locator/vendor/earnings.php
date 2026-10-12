<?php
/**
 * vendor/earnings.php
 * Earnings overview + a list of completed jobs and their estimated payout.
 * Note: bookings don't store a per-job price historically, so payout per job
 * is the vendor's current flat price — an estimate, called out as such below.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_vendor();

$vendor_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT price FROM vendors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $vendor_id]);
$price = (float) $stmt->fetchColumn();

function completed_count_since(PDO $pdo, int $vendor_id, ?string $since): int
{
    if ($since) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM bookings WHERE vendor_id = :id AND status = 'completed' AND booking_date >= :since"
        );
        $stmt->execute(['id' => $vendor_id, 'since' => $since]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE vendor_id = :id AND status = 'completed'");
        $stmt->execute(['id' => $vendor_id]);
    }
    return (int) $stmt->fetchColumn();
}

$todayCount   = completed_count_since($pdo, $vendor_id, date('Y-m-d'));
$weekCount    = completed_count_since($pdo, $vendor_id, date('Y-m-d', strtotime('monday this week')));
$monthCount   = completed_count_since($pdo, $vendor_id, date('Y-m-01'));
$allTimeCount = completed_count_since($pdo, $vendor_id, null);

$completedStmt = $pdo->prepare(
    "SELECT b.id, b.booking_date, u.name AS customer_name, s.name AS service_name
     FROM bookings b
     JOIN users u    ON u.id = b.customer_id
     JOIN services s ON s.id = b.service_id
     WHERE b.vendor_id = :id AND b.status = 'completed'
     ORDER BY b.booking_date DESC
     LIMIT 20"
);
$completedStmt->execute(['id' => $vendor_id]);
$completedJobs = $completedStmt->fetchAll();

$page_title = 'Earnings';
$active_tab = 'earnings';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl">

    <div class="px-5 pt-6">
      <div class="bg-white rounded-2xl shadow-sm py-6 px-5">
        <h1 class="text-3xl font-extrabold text-brand-900 text-center">Earnings</h1>
      </div>
    </div>

    <div class="px-5 mt-6">

      <!-- Summary cards -->
      <div class="grid grid-cols-2 gap-3 mb-2">
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
          <p class="text-xs text-gray-500">Today</p>
          <p class="text-xl font-extrabold text-brand-900 mt-1"><?= format_ghs($todayCount * $price) ?></p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
          <p class="text-xs text-gray-500">This Week</p>
          <p class="text-xl font-extrabold text-brand-900 mt-1"><?= format_ghs($weekCount * $price) ?></p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
          <p class="text-xs text-gray-500">This Month</p>
          <p class="text-xl font-extrabold text-brand-900 mt-1"><?= format_ghs($monthCount * $price) ?></p>
        </div>
        <div class="bg-brand-500 rounded-2xl shadow-sm p-4">
          <p class="text-xs text-brand-50">All Time</p>
          <p class="text-xl font-extrabold text-white mt-1"><?= format_ghs($allTimeCount * $price) ?></p>
        </div>
      </div>

      <p class="text-xs text-gray-400 mb-5">
        Estimated using your current price (<?= format_ghs($price) ?>/job) &times; completed jobs in each period.
      </p>

      <!-- Job history -->
      <h2 class="text-lg font-bold text-gray-900 mb-3">Payout History</h2>

      <?php if (empty($completedJobs)): ?>
        <div class="bg-brand-50 border border-dashed border-brand-200 rounded-2xl py-10 px-6 flex flex-col items-center text-center">
          <span class="w-14 h-14 rounded-full bg-white flex items-center justify-center mb-3 shadow-sm">
            <?= svg_icon('cash', 'w-6 h-6 text-brand-600') ?>
          </span>
          <p class="text-sm font-semibold text-gray-700">No completed jobs yet</p>
          <p class="text-sm text-gray-500 mt-0.5">Payouts will show up here once you complete a job.</p>
        </div>
      <?php else: ?>
        <div class="space-y-2 pb-4">
          <?php foreach ($completedJobs as $job): ?>
            <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl shadow-sm p-3.5">
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($job['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-gray-500 truncate">
                  <?= htmlspecialchars($job['service_name'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= date('M j, Y', strtotime($job['booking_date'])) ?>
                </p>
              </div>
              <p class="shrink-0 font-bold text-brand-700"><?= format_ghs($price) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include '../includes/vendor_footer.php'; ?>