<?php
/**
 * admin/providers.php
 * Manage technician (vendor) accounts: verify, suspend/reactivate.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

    if ($vendor_id) {
        if (isset($_POST['toggle_verified'])) {
            $stmt = $pdo->prepare('SELECT is_verified FROM vendors WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $vendor_id]);
            $current = $stmt->fetchColumn();
            if ($current !== false) {
                $update = $pdo->prepare('UPDATE vendors SET is_verified = :v WHERE id = :id');
                $update->execute(['v' => $current ? 0 : 1, 'id' => $vendor_id]);
                $success = $current ? 'Provider unverified.' : 'Provider verified.';
            }
        } elseif (isset($_POST['toggle_status'])) {
            $stmt = $pdo->prepare('SELECT status FROM vendors WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $vendor_id]);
            $current = $stmt->fetchColumn();
            if ($current !== false) {
                $newStatus = $current === 'active' ? 'inactive' : 'active';
                $update = $pdo->prepare('UPDATE vendors SET status = :s WHERE id = :id');
                $update->execute(['s' => $newStatus, 'id' => $vendor_id]);
                $success = $newStatus === 'active' ? 'Provider reactivated.' : 'Provider suspended.';
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT v.*, s.name AS service_name, s.icon AS service_icon,
        (SELECT COUNT(*) FROM bookings b WHERE b.vendor_id = v.id) AS booking_count
        FROM vendors v JOIN services s ON s.id = v.service_id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (v.name LIKE :search OR v.email LIKE :search)";
    $params['search'] = '%' . $search . '%';
}
if ($filter === 'pending') {
    $sql .= " AND v.is_verified = 0";
} elseif ($filter === 'verified') {
    $sql .= " AND v.is_verified = 1";
} elseif ($filter === 'suspended') {
    $sql .= " AND v.status = 'inactive'";
}
$sql .= " ORDER BY v.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$providers = $stmt->fetchAll();

$page_title = 'Providers';
$page_subtitle = 'Manage technician accounts and verification';
$active_admin_tab = 'providers';
include 'includes/header.php';
?>

<?php if ($success): ?>
  <div class="mb-4 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
    <p class="text-sm text-brand-700 font-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

  <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
    <form method="GET" class="relative flex-1 max-w-sm">
      <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
        <?= svg_icon('search', 'w-4 h-4') ?>
      </span>
      <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search providers..."
        class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    </form>

    <div class="flex items-center gap-2 flex-wrap">
      <?php foreach (['all' => 'All', 'pending' => 'Pending', 'verified' => 'Verified', 'suspended' => 'Suspended'] as $key => $label): ?>
        <a href="?filter=<?= $key ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
           class="text-xs font-semibold px-3 py-1.5 rounded-full transition-colors <?= $filter === $key ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($providers)): ?>
    <div class="p-12 text-center">
      <span class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
        <?= svg_icon('briefcase', 'w-6 h-6 text-brand-500') ?>
      </span>
      <p class="text-sm font-semibold text-gray-700">No providers found</p>
      <p class="text-sm text-gray-400 mt-1">Technicians will appear here once they register.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
            <th class="font-medium py-3 px-5">Provider</th>
            <th class="font-medium py-3 px-2">Service</th>
            <th class="font-medium py-3 px-2">Price</th>
            <th class="font-medium py-3 px-2">Rating</th>
            <th class="font-medium py-3 px-2">Bookings</th>
            <th class="font-medium py-3 px-2">Verification</th>
            <th class="font-medium py-3 px-2">Status</th>
            <th class="font-medium py-3 px-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($providers as $p): ?>
            <tr class="border-b border-gray-50 last:border-0">
              <td class="py-3 px-5">
                <div class="flex items-center gap-2.5">
                  <?= initials_avatar($p['name'], 'w-8 h-8', 'text-[11px]') ?>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-800 truncate"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($p['email'], ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                </div>
              </td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= htmlspecialchars($p['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= format_ghs((float) $p['price']) ?></td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap">★ <?= number_format((float) $p['rating'], 1) ?></td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= (int) $p['booking_count'] ?></td>
              <td class="py-3 px-2">
                <form action="" method="POST">
                  <input type="hidden" name="vendor_id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" name="toggle_verified"
                    class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap <?= (int) $p['is_verified'] === 1 ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' ?>">
                    <?= (int) $p['is_verified'] === 1 ? 'Verified' : 'Pending' ?>
                  </button>
                </form>
              </td>
              <td class="py-3 px-2">
                <form action="" method="POST">
                  <input type="hidden" name="vendor_id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" name="toggle_status"
                    class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap <?= $p['status'] === 'active' ? 'bg-brand-100 text-brand-700 hover:bg-brand-200' : 'bg-red-100 text-red-700 hover:bg-red-200' ?>">
                    <?= $p['status'] === 'active' ? 'Active' : 'Suspended' ?>
                  </button>
                </form>
              </td>
              <td class="py-3 px-5 text-right text-xs text-gray-400">Click badges to change</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>