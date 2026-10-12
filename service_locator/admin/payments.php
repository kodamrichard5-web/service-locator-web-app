<?php
/**
 * admin/payments.php
 * Read-only payments ledger, derived from completed bookings.
 * Note: there's no real payment gateway wired into this app yet — this shows
 * what WOULD have been charged (vendor's current price per completed job),
 * not actual processed transactions. Say the word if you want a real gateway
 * (Paystack/Stripe etc.) integrated — that's a separate, larger build.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$totalRevenue = (float) $pdo->query(
    "SELECT COALESCE(SUM(v.price), 0) FROM bookings b JOIN vendors v ON v.id = b.vendor_id WHERE b.status = 'completed'"
)->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(v.price), 0) FROM bookings b JOIN vendors v ON v.id = b.vendor_id
     WHERE b.status = 'completed' AND b.booking_date >= :start"
);
$stmt->execute(['start' => date('Y-m-01')]);
$thisMonthRevenue = (float) $stmt->fetchColumn();

$completedCount = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();

$transactions = $pdo->query(
    "SELECT b.id, b.booking_date, u.name AS customer_name, v.name AS vendor_name, v.price, s.name AS service_name
     FROM bookings b
     JOIN users u    ON u.id = b.customer_id
     JOIN vendors v  ON v.id = b.vendor_id
     JOIN services s ON s.id = b.service_id
     WHERE b.status = 'completed'
     ORDER BY b.booking_date DESC
     LIMIT 100"
)->fetchAll();

$page_title = 'Payments';
$page_subtitle = 'Revenue ledger from completed bookings';
$active_admin_tab = 'payments';
include 'includes/header.php';
?>

<div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
  <p class="text-sm text-yellow-800">No payment gateway is connected yet — this is a ledger of completed bookings valued at each provider's current price, not real processed transactions.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">Total Revenue (All Time)</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= format_ghs($totalRevenue) ?></p>
  </div>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">This Month</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= format_ghs($thisMonthRevenue) ?></p>
  </div>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">Completed Jobs</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($completedCount) ?></p>
  </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
  <?php if (empty($transactions)): ?>
    <div class="p-12 text-center">
      <span class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
        <?= svg_icon('cash', 'w-6 h-6 text-brand-500') ?>
      </span>
      <p class="text-sm font-semibold text-gray-700">No completed bookings yet</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
            <th class="font-medium py-3 px-5">Booking ID</th>
            <th class="font-medium py-3 px-2">Service</th>
            <th class="font-medium py-3 px-2">Customer</th>
            <th class="font-medium py-3 px-2">Provider</th>
            <th class="font-medium py-3 px-2">Date</th>
            <th class="font-medium py-3 px-5">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $t): ?>
            <tr class="border-b border-gray-50 last:border-0">
              <td class="py-3 px-5 text-gray-500 whitespace-nowrap">#BK<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td class="py-3 px-2 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($t['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($t['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($t['vendor_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-500 whitespace-nowrap text-xs"><?= date('M j, Y', strtotime($t['booking_date'])) ?></td>
              <td class="py-3 px-5 font-semibold text-gray-800 whitespace-nowrap"><?= format_ghs((float) $t['price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>