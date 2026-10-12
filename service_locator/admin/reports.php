<?php
/**
 * admin/reports.php
 * Higher-level summary: revenue trend over recent months + top providers by
 * completed jobs. A lighter-weight companion to the dashboard's daily view.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

// Revenue + booking count per month for the last 6 months.
$monthLabels = [];
$monthRevenue = [];
$monthBookings = [];
for ($i = 5; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd   = date('Y-m-t', strtotime("-$i months"));
    $monthLabels[] = date('M Y', strtotime($monthStart));

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(v.price), 0) FROM bookings b JOIN vendors v ON v.id = b.vendor_id
         WHERE b.status = 'completed' AND b.booking_date BETWEEN :start AND :end"
    );
    $stmt->execute(['start' => $monthStart, 'end' => $monthEnd]);
    $monthRevenue[] = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE created_at BETWEEN :start AND :end");
    $stmt->execute(['start' => $monthStart . ' 00:00:00', 'end' => $monthEnd . ' 23:59:59']);
    $monthBookings[] = (int) $stmt->fetchColumn();
}

// Top providers by completed jobs
$topProviders = $pdo->query(
    "SELECT v.name, v.rating, s.name AS service_name,
            COUNT(CASE WHEN b.status = 'completed' THEN 1 END) AS completed_count,
            COALESCE(SUM(CASE WHEN b.status = 'completed' THEN v.price END), 0) AS revenue
     FROM vendors v
     JOIN services s ON s.id = v.service_id
     LEFT JOIN bookings b ON b.vendor_id = v.id
     GROUP BY v.id, v.name, v.rating, s.name
     ORDER BY completed_count DESC
     LIMIT 10"
)->fetchAll();

$page_title = 'Reports';
$page_subtitle = 'Revenue and provider performance over time';
$active_admin_tab = 'reports';
include 'includes/header.php';
?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
  <h3 class="font-bold text-gray-800 mb-4">Revenue — Last 6 Months</h3>
  <?php if (array_sum($monthRevenue) == 0): ?>
    <p class="text-sm text-gray-400 text-center py-16">No completed bookings yet to report on.</p>
  <?php else: ?>
    <canvas id="revenueChart" height="80"></canvas>
  <?php endif; ?>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
  <div class="p-5 border-b border-gray-100">
    <h3 class="font-bold text-gray-800">Top Providers by Completed Jobs</h3>
  </div>
  <?php if (empty($topProviders) || array_sum(array_column($topProviders, 'completed_count')) == 0): ?>
    <div class="p-12 text-center">
      <p class="text-sm font-semibold text-gray-700">No completed jobs yet</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
            <th class="font-medium py-3 px-5">Provider</th>
            <th class="font-medium py-3 px-2">Service</th>
            <th class="font-medium py-3 px-2">Rating</th>
            <th class="font-medium py-3 px-2">Completed Jobs</th>
            <th class="font-medium py-3 px-5">Revenue</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topProviders as $p): ?>
            <tr class="border-b border-gray-50 last:border-0">
              <td class="py-3 px-5">
                <div class="flex items-center gap-2.5">
                  <?= initials_avatar($p['name'], 'w-8 h-8', 'text-[11px]') ?>
                  <span class="font-medium text-gray-800"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= htmlspecialchars($p['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap">★ <?= number_format((float) $p['rating'], 1) ?></td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= (int) $p['completed_count'] ?></td>
              <td class="py-3 px-5 font-semibold text-gray-800 whitespace-nowrap"><?= format_ghs((float) $p['revenue']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if (array_sum($monthRevenue) > 0): ?>
<script>
new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($monthLabels) ?>,
    datasets: [{
      label: 'Revenue (GHC)',
      data: <?= json_encode($monthRevenue) ?>,
      backgroundColor: '#22C55E',
      borderRadius: 6,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>