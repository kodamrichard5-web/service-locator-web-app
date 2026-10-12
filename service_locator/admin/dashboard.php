<?php
/**
 * admin/dashboard.php
 * Admin overview: stat cards, bookings trend, status breakdown, recent activity.
 * Every number is queried live — nothing hardcoded.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$thisMonthStart = date('Y-m-01 00:00:00');
$lastMonthStart = date('Y-m-01 00:00:00', strtotime('first day of last month'));
$lastMonthEnd   = date('Y-m-t 23:59:59', strtotime('last day of last month'));

function pct_change(int $current, int $previous): array
{
    if ($previous === 0) {
        return $current > 0 ? ['+100%', true] : ['0%', true];
    }
    $change = (($current - $previous) / $previous) * 100;
    $up = $change >= 0;
    return [($up ? '+' : '') . number_format($change, 1) . '%', $up];
}

// --- Total Bookings ---
$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE created_at >= :start');
$stmt->execute(['start' => $thisMonthStart]);
$bookingsThisMonth = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE created_at BETWEEN :start AND :end');
$stmt->execute(['start' => $lastMonthStart, 'end' => $lastMonthEnd]);
$bookingsLastMonth = (int) $stmt->fetchColumn();
[$bookingsChangeLabel, $bookingsUp] = pct_change($bookingsThisMonth, $bookingsLastMonth);

// --- Total Providers ---
$totalProviders = (int) $pdo->query('SELECT COUNT(*) FROM vendors')->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM vendors WHERE created_at >= :start');
$stmt->execute(['start' => $thisMonthStart]);
$providersThisMonth = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM vendors WHERE created_at BETWEEN :start AND :end');
$stmt->execute(['start' => $lastMonthStart, 'end' => $lastMonthEnd]);
$providersLastMonth = (int) $stmt->fetchColumn();
[$providersChangeLabel, $providersUp] = pct_change($providersThisMonth, $providersLastMonth);

// --- Total Users ---
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at >= :start');
$stmt->execute(['start' => $thisMonthStart]);
$usersThisMonth = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at BETWEEN :start AND :end');
$stmt->execute(['start' => $lastMonthStart, 'end' => $lastMonthEnd]);
$usersLastMonth = (int) $stmt->fetchColumn();
[$usersChangeLabel, $usersUp] = pct_change($usersThisMonth, $usersLastMonth);

// --- Total Revenue (completed bookings × vendor's current price — see note in earnings.php) ---
$stmt = $pdo->query(
    "SELECT COALESCE(SUM(v.price), 0) FROM bookings b JOIN vendors v ON v.id = b.vendor_id WHERE b.status = 'completed'"
);
$totalRevenue = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(v.price), 0) FROM bookings b JOIN vendors v ON v.id = b.vendor_id
     WHERE b.status = 'completed' AND b.booking_date >= :start"
);
$stmt->execute(['start' => date('Y-m-01')]);
$revenueThisMonth = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(v.price), 0) FROM bookings b JOIN vendors v ON v.id = b.vendor_id
     WHERE b.status = 'completed' AND b.booking_date BETWEEN :start AND :end"
);
$stmt->execute(['start' => date('Y-m-01', strtotime('first day of last month')), 'end' => date('Y-m-t', strtotime('last day of last month'))]);
$revenueLastMonth = (float) $stmt->fetchColumn();
[$revenueChangeLabel, $revenueUp] = pct_change((int) $revenueThisMonth, (int) $revenueLastMonth);

// --- Bookings Overview: weekly counts for the last 5 weeks ---
$stmt = $pdo->query(
    "SELECT YEARWEEK(created_at, 1) AS yw, MIN(DATE(created_at)) AS week_start, COUNT(*) AS c
     FROM bookings
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 35 DAY)
     GROUP BY yw
     ORDER BY yw ASC"
);
$weeklyRows = $stmt->fetchAll();
$weeklyLabels = [];
$weeklyCounts = [];
foreach (array_slice($weeklyRows, -5) as $row) {
    $weeklyLabels[] = date('M j', strtotime($row['week_start']));
    $weeklyCounts[] = (int) $row['c'];
}

// --- Bookings by Status ---
$statusCounts = ['pending' => 0, 'accepted' => 0, 'in-progress' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['c'];
}

// --- Recent Bookings ---
$recentBookings = $pdo->query(
    "SELECT b.id, b.status, b.booking_date, b.booking_time,
            u.name AS customer_name, u.phone AS customer_phone,
            v.id AS vendor_id, v.name AS vendor_name, v.price,
            s.name AS service_name, s.icon AS service_icon
     FROM bookings b
     JOIN users u    ON u.id = b.customer_id
     JOIN vendors v  ON v.id = b.vendor_id
     JOIN services s ON s.id = b.service_id
     ORDER BY b.created_at DESC
     LIMIT 5"
)->fetchAll();

// --- Provider Verification queue ---
$pendingProviders = $pdo->query(
    "SELECT v.id, v.name, v.is_verified, s.name AS service_name
     FROM vendors v JOIN services s ON s.id = v.service_id
     ORDER BY v.is_verified ASC, v.created_at DESC
     LIMIT 4"
)->fetchAll();

// --- Top Services (by booking count) ---
$topServices = $pdo->query(
    "SELECT s.id, s.name, s.icon, COUNT(b.id) AS booking_count
     FROM services s
     LEFT JOIN bookings b ON b.service_id = s.id
     GROUP BY s.id, s.name, s.icon
     ORDER BY booking_count DESC, s.name ASC
     LIMIT 5"
)->fetchAll();

// --- Recent Reviews ---
$recentReviews = $pdo->query(
    "SELECT r.rating, r.comment, r.created_at, u.name AS customer_name
     FROM reviews r JOIN users u ON u.id = r.customer_id
     ORDER BY r.created_at DESC
     LIMIT 3"
)->fetchAll();

$page_title = 'Dashboard';
$page_subtitle = 'Welcome back, ' . ($_SESSION['admin_name'] ?? 'Admin') . '!';
$active_admin_tab = 'dashboard';
include 'includes/header.php';
?>

<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-3">
      <span class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center text-brand-700">
        <?= svg_icon('calendar', 'w-5 h-5') ?>
      </span>
    </div>
    <p class="text-xs text-gray-400">Total Bookings</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($totalBookings) ?></p>
    <p class="text-xs mt-1 <?= $bookingsUp ? 'text-brand-600' : 'text-red-500' ?>"><?= $bookingsUp ? '↑' : '↓' ?> <?= $bookingsChangeLabel ?> from last month</p>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-3">
      <span class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700">
        <?= svg_icon('briefcase', 'w-5 h-5') ?>
      </span>
    </div>
    <p class="text-xs text-gray-400">Total Providers</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($totalProviders) ?></p>
    <p class="text-xs mt-1 <?= $providersUp ? 'text-brand-600' : 'text-red-500' ?>"><?= $providersUp ? '↑' : '↓' ?> <?= $providersChangeLabel ?> from last month</p>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-3">
      <span class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700">
        <?= svg_icon('user-circle', 'w-5 h-5') ?>
      </span>
    </div>
    <p class="text-xs text-gray-400">Total Users</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($totalUsers) ?></p>
    <p class="text-xs mt-1 <?= $usersUp ? 'text-brand-600' : 'text-red-500' ?>"><?= $usersUp ? '↑' : '↓' ?> <?= $usersChangeLabel ?> from last month</p>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-3">
      <span class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center text-yellow-700">
        <?= svg_icon('cash', 'w-5 h-5') ?>
      </span>
    </div>
    <p class="text-xs text-gray-400">Total Revenue</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= format_ghs($totalRevenue) ?></p>
    <p class="text-xs mt-1 <?= $revenueUp ? 'text-brand-600' : 'text-red-500' ?>"><?= $revenueUp ? '↑' : '↓' ?> <?= $revenueChangeLabel ?> from last month</p>
  </div>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

  <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800">Bookings Overview</h3>
      <span class="text-xs font-medium text-gray-500 border border-gray-200 rounded-lg px-3 py-1.5">Last 5 weeks</span>
    </div>
    <?php if (empty($weeklyCounts)): ?>
      <p class="text-sm text-gray-400 text-center py-16">Not enough booking activity yet to chart a trend.</p>
    <?php else: ?>
      <canvas id="bookingsChart" height="90"></canvas>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-bold text-gray-800 mb-4">Bookings by Status</h3>
    <?php if ($totalBookings === 0): ?>
      <p class="text-sm text-gray-400 text-center py-16">No bookings yet.</p>
    <?php else: ?>
      <canvas id="statusChart" height="180"></canvas>
      <div class="mt-4 space-y-1.5">
        <?php
        $dotColors = ['pending' => 'bg-yellow-400', 'accepted' => 'bg-blue-500', 'in-progress' => 'bg-purple-500', 'completed' => 'bg-brand-500', 'cancelled' => 'bg-gray-400'];
        foreach ($statusCounts as $status => $count):
            [, , $label] = status_badge_classes($status);
            $pct = $totalBookings > 0 ? round(($count / $totalBookings) * 100) : 0;
        ?>
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-gray-600">
              <span class="w-2 h-2 rounded-full <?= $dotColors[$status] ?? 'bg-gray-400' ?>"></span>
              <?= $label ?>
            </span>
            <span class="text-gray-400"><?= $count ?> (<?= $pct ?>%)</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Bookings + side widgets -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800">Recent Bookings</h3>
      <a href="bookings.php" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View All</a>
    </div>

    <?php if (empty($recentBookings)): ?>
      <p class="text-sm text-gray-400 text-center py-10">No bookings yet.</p>
    <?php else: ?>
      <div class="overflow-x-auto -mx-5">
        <table class="w-full text-sm min-w-[600px]">
          <thead>
            <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
              <th class="font-medium pb-2 px-5">Service</th>
              <th class="font-medium pb-2 px-2">Customer</th>
              <th class="font-medium pb-2 px-2">Provider</th>
              <th class="font-medium pb-2 px-2">Date</th>
              <th class="font-medium pb-2 px-2">Status</th>
              <th class="font-medium pb-2 px-2">Amount</th>
              <th class="font-medium pb-2 px-5 text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $b):
                [$badge_bg, $badge_text, $badge_label] = status_badge_classes($b['status']);
                $when = format_booking_datetime($b['booking_date'], $b['booking_time']);
            ?>
              <tr class="border-b border-gray-50 last:border-0">
                <td class="py-3 px-5">
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
                  <a href="bookings.php?view=<?= (int) $b['id'] ?>" class="inline-flex w-7 h-7 rounded-lg hover:bg-gray-100 items-center justify-center text-gray-400">
                    <?= svg_icon('eye', 'w-4 h-4') ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="space-y-4">

    <!-- Provider Verification -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-gray-800 text-sm">Provider Verification</h3>
        <a href="providers.php" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View All</a>
      </div>
      <?php if (empty($pendingProviders)): ?>
        <p class="text-xs text-gray-400 text-center py-6">No providers yet.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($pendingProviders as $p): ?>
            <a href="providers.php" class="flex items-center gap-2.5 group">
              <?= initials_avatar($p['name'], 'w-8 h-8', 'text-[10px]') ?>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($p['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <span class="shrink-0 text-[11px] font-semibold px-2 py-1 rounded-full <?= (int) $p['is_verified'] === 1 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                <?= (int) $p['is_verified'] === 1 ? 'Verified' : 'Pending' ?>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Top Services -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-gray-800 text-sm">Top Services</h3>
      </div>
      <?php if (empty($topServices)): ?>
        <p class="text-xs text-gray-400 text-center py-6">No services yet.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($topServices as $s): ?>
            <div class="flex items-center gap-2.5">
              <span class="w-8 h-8 rounded-lg bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
                <?= svg_icon($s['icon'], 'w-4 h-4') ?>
              </span>
              <p class="text-sm font-medium text-gray-800 flex-1 truncate"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></p>
              <span class="text-xs text-gray-400 shrink-0"><?= (int) $s['booking_count'] ?> bookings</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent Reviews -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-gray-800 text-sm">Recent Reviews</h3>
        <a href="reviews.php" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View All</a>
      </div>
      <?php if (empty($recentReviews)): ?>
        <p class="text-xs text-gray-400 text-center py-6">No reviews yet.</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($recentReviews as $r): ?>
            <div>
              <div class="flex items-center gap-2.5 mb-1">
                <?= initials_avatar($r['customer_name'], 'w-7 h-7', 'text-[10px]') ?>
                <p class="text-sm font-medium text-gray-800 flex-1 truncate"><?= htmlspecialchars($r['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <span class="text-[10px] text-gray-400 shrink-0"><?= time_ago($r['created_at']) ?></span>
              </div>
              <?= star_rating((float) $r['rating'], 'w-3 h-3') ?>
              <?php if (!empty($r['comment'])): ?>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($r['comment'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php if (!empty($weeklyCounts) || $totalBookings > 0): ?>
<script>
<?php if (!empty($weeklyCounts)): ?>
new Chart(document.getElementById('bookingsChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($weeklyLabels) ?>,
    datasets: [{
      data: <?= json_encode($weeklyCounts) ?>,
      borderColor: '#22C55E',
      backgroundColor: 'rgba(34,197,94,0.1)',
      fill: true,
      tension: 0.35,
      pointBackgroundColor: '#22C55E',
      pointRadius: 4,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
<?php endif; ?>

<?php if ($totalBookings > 0): ?>
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Pending', 'Accepted', 'In-Progress', 'Completed', 'Cancelled'],
    datasets: [{
      data: <?= json_encode(array_values($statusCounts)) ?>,
      backgroundColor: ['#FACC15', '#3B82F6', '#A855F7', '#22C55E', '#9CA3AF'],
      borderWidth: 0,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    cutout: '65%'
  }
});
<?php endif; ?>
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>