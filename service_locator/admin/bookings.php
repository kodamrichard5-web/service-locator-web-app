<?php
/**
 * admin/bookings.php
 * Platform-wide booking list with status filter, search, and a detail panel.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$viewId = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);

$sql = "SELECT b.id, b.status, b.booking_date, b.booking_time, b.address, b.notes, b.created_at,
        u.name AS customer_name, u.phone AS customer_phone,
        v.id AS vendor_id, v.name AS vendor_name, v.price,
        s.name AS service_name, s.icon AS service_icon
        FROM bookings b
        JOIN users u    ON u.id = b.customer_id
        JOIN vendors v  ON v.id = b.vendor_id
        JOIN services s ON s.id = b.service_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE :search OR v.name LIKE :search OR s.name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}
if (in_array($statusFilter, ['pending', 'accepted', 'in-progress', 'completed', 'cancelled'], true)) {
    $sql .= " AND b.status = :status";
    $params['status'] = $statusFilter;
}
$sql .= " ORDER BY b.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$viewedBooking = null;
if ($viewId) {
    foreach ($bookings as $b) {
        if ((int) $b['id'] === $viewId) {
            $viewedBooking = $b;
            break;
        }
    }
    if (!$viewedBooking) {
        $vs = $pdo->prepare(
            "SELECT b.id, b.status, b.booking_date, b.booking_time, b.address, b.notes, b.created_at,
                    u.name AS customer_name, u.phone AS customer_phone,
                    v.id AS vendor_id, v.name AS vendor_name, v.price,
                    s.name AS service_name, s.icon AS service_icon
             FROM bookings b
             JOIN users u    ON u.id = b.customer_id
             JOIN vendors v  ON v.id = b.vendor_id
             JOIN services s ON s.id = b.service_id
             WHERE b.id = :id LIMIT 1"
        );
        $vs->execute(['id' => $viewId]);
        $viewedBooking = $vs->fetch() ?: null;
    }
}

$page_title = 'Bookings';
$page_subtitle = 'All bookings across the platform';
$active_admin_tab = 'bookings';
include 'includes/header.php';
?>

<?php if ($viewedBooking):
    [$vbg, $vtext, $vlabel] = status_badge_classes($viewedBooking['status']);
    $vwhen = format_booking_datetime($viewedBooking['booking_date'], $viewedBooking['booking_time']);
?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800">Booking #<?= (int) $viewedBooking['id'] ?></h3>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $vbg ?> <?= $vtext ?>"><?= htmlspecialchars($vlabel, ENT_QUOTES, 'UTF-8') ?></span>
        <a href="bookings.php<?= $search !== '' || $statusFilter !== 'all' ? '?' . http_build_query(array_filter(['q' => $search, 'status' => $statusFilter !== 'all' ? $statusFilter : null])) : '' ?>" class="text-gray-400 hover:text-gray-600 text-xs font-semibold">Close ✕</a>
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
      <div><p class="text-xs text-gray-400">Service</p><p class="text-gray-800 font-medium"><?= htmlspecialchars($viewedBooking['service_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
      <div><p class="text-xs text-gray-400">Amount</p><p class="text-gray-800 font-medium"><?= format_ghs((float) $viewedBooking['price']) ?></p></div>
      <div><p class="text-xs text-gray-400">Customer</p><p class="text-gray-800 font-medium"><?= htmlspecialchars($viewedBooking['customer_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($viewedBooking['customer_phone'], ENT_QUOTES, 'UTF-8') ?></p></div>
      <div><p class="text-xs text-gray-400">Provider</p><p class="text-gray-800 font-medium"><?= htmlspecialchars($viewedBooking['vendor_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
      <div><p class="text-xs text-gray-400">Scheduled</p><p class="text-gray-800 font-medium"><?= htmlspecialchars($vwhen, ENT_QUOTES, 'UTF-8') ?></p></div>
      <div><p class="text-xs text-gray-400">Address</p><p class="text-gray-800 font-medium"><?= htmlspecialchars($viewedBooking['address'], ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php if (!empty($viewedBooking['notes'])): ?>
        <div class="sm:col-span-2"><p class="text-xs text-gray-400">Notes</p><p class="text-gray-800"><?= htmlspecialchars($viewedBooking['notes'], ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

  <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
    <form method="GET" class="relative flex-1 max-w-sm">
      <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
        <?= svg_icon('search', 'w-4 h-4') ?>
      </span>
      <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search bookings..."
        class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      <?php if ($statusFilter !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    </form>

    <div class="flex items-center gap-2 flex-wrap">
      <?php foreach (['all' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'in-progress' => 'In-Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label): ?>
        <a href="?status=<?= $key ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
           class="text-xs font-semibold px-3 py-1.5 rounded-full transition-colors <?= $statusFilter === $key ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="p-12 text-center">
      <span class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
        <?= svg_icon('calendar', 'w-6 h-6 text-brand-500') ?>
      </span>
      <p class="text-sm font-semibold text-gray-700">No bookings found</p>
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
            <th class="font-medium py-3 px-2">Date &amp; Time</th>
            <th class="font-medium py-3 px-2">Status</th>
            <th class="font-medium py-3 px-2">Amount</th>
            <th class="font-medium py-3 px-5 text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b):
              [$badge_bg, $badge_text, $badge_label] = status_badge_classes($b['status']);
              $when = format_booking_datetime($b['booking_date'], $b['booking_time']);
          ?>
            <tr class="border-b border-gray-50 last:border-0 <?= $viewId === (int) $b['id'] ? 'bg-brand-50/50' : '' ?>">
              <td class="py-3 px-5 text-gray-500 whitespace-nowrap">#BK<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td class="py-3 px-2">
                <div class="flex items-center gap-2">
                  <span class="w-7 h-7 rounded-lg bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
                    <?= svg_icon($b['service_icon'], 'w-3.5 h-3.5') ?>
                  </span>
                  <span class="text-gray-700 whitespace-nowrap"><?= htmlspecialchars($b['service_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </td>
              <td class="py-3 px-2 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($b['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($b['vendor_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-500 whitespace-nowrap text-xs"><?= htmlspecialchars($when, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap <?= $badge_bg ?> <?= $badge_text ?>"><?= htmlspecialchars($badge_label, ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td class="py-3 px-2 text-gray-700 whitespace-nowrap"><?= format_ghs((float) $b['price']) ?></td>
              <td class="py-3 px-5 text-right">
                <a href="?view=<?= (int) $b['id'] ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?><?= $statusFilter !== 'all' ? '&status=' . $statusFilter : '' ?>"
                   class="inline-flex w-7 h-7 rounded-lg hover:bg-gray-100 items-center justify-center text-gray-400">
                  <?= svg_icon('eye', 'w-4 h-4') ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="text-xs text-gray-400 px-5 py-3">Showing up to 100 most recent matching bookings.</p>
  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>