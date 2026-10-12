<?php
/**
 * admin/services.php
 * CRUD for service categories. Also the destination for the "Categories" nav
 * item — in this data model, services ARE the categories, so both live here.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$errors = [];
$success = null;

$availableIcons = ['broom', 'wrench', 'bolt', 'leaf', 'bug', 'paintbrush', 'truck', 'tool'];

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name'] ?? '');
    $icon = $_POST['icon'] ?? 'tool';

    if ($name === '') {
        $errors[] = 'Please enter a service name.';
    }
    if (!in_array($icon, $availableIcons, true)) {
        $icon = 'tool';
    }

    if (empty($errors)) {
        $insert = $pdo->prepare('INSERT INTO services (name, icon) VALUES (:name, :icon)');
        $insert->execute(['name' => $name, 'icon' => $icon]);
        $success = 'Service added.';
    }
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $id   = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $icon = $_POST['icon'] ?? 'tool';

    if (!$id) {
        $errors[] = 'Invalid service.';
    }
    if ($name === '') {
        $errors[] = 'Please enter a service name.';
    }
    if (!in_array($icon, $availableIcons, true)) {
        $icon = 'tool';
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE services SET name = :name, icon = :icon WHERE id = :id');
        $update->execute(['name' => $name, 'icon' => $icon, 'id' => $id]);
        $success = 'Service updated.';
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    if ($id) {
        $inUse = $pdo->prepare('SELECT COUNT(*) FROM vendors WHERE service_id = :id');
        $inUse->execute(['id' => $id]);
        if ((int) $inUse->fetchColumn() > 0) {
            $errors[] = 'Cannot delete — one or more providers are assigned to this service. Reassign them first.';
        } else {
            $delete = $pdo->prepare('DELETE FROM services WHERE id = :id');
            $delete->execute(['id' => $id]);
            $success = 'Service deleted.';
        }
    }
}

$services = $pdo->query(
    "SELECT s.*, (SELECT COUNT(*) FROM vendors v WHERE v.service_id = s.id) AS provider_count,
            (SELECT COUNT(*) FROM bookings b WHERE b.service_id = s.id) AS booking_count
     FROM services s ORDER BY s.name ASC"
)->fetchAll();

$page_title = 'Services & Categories';
$page_subtitle = 'Manage the service categories customers can browse';
$active_admin_tab = 'services';
include 'includes/header.php';
?>

<?php if ($success): ?>
  <div class="mb-4 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
    <p class="text-sm text-brand-700 font-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
    <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
      <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <!-- Add form -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-fit">
    <h3 class="font-bold text-gray-800 mb-4">Add Service</h3>
    <form action="" method="POST">
      <div class="mb-3">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name</label>
        <input type="text" name="name" required placeholder="e.g. HVAC Repair"
          class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      </div>
      <div class="mb-4">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">Icon</label>
        <select name="icon" class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          <?php foreach ($availableIcons as $iconKey): ?>
            <option value="<?= $iconKey ?>"><?= ucfirst(str_replace('-', ' ', $iconKey)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="add_service" class="w-full h-10 bg-brand-500 hover:bg-brand-700 text-white font-semibold rounded-lg text-sm transition-colors">
        Add Service
      </button>
    </form>
  </div>

  <!-- List -->
  <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
    <?php if (empty($services)): ?>
      <div class="p-12 text-center">
        <p class="text-sm font-semibold text-gray-700">No services yet</p>
        <p class="text-sm text-gray-400 mt-1">Add your first service category using the form.</p>
      </div>
    <?php else: ?>
      <div class="divide-y divide-gray-50">
        <?php foreach ($services as $s): ?>
          <div class="flex items-center gap-3 p-4">
            <span class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
              <?= svg_icon($s['icon'], 'w-5 h-5') ?>
            </span>
            <div class="min-w-0 flex-1">
              <p class="font-medium text-gray-800 truncate"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></p>
              <p class="text-xs text-gray-400"><?= (int) $s['provider_count'] ?> providers · <?= (int) $s['booking_count'] ?> bookings</p>
            </div>
            <button type="button" onclick="document.getElementById('edit-<?= (int) $s['id'] ?>').classList.toggle('hidden')"
              class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
              <?= svg_icon('edit', 'w-4 h-4') ?>
            </button>
            <form action="" method="POST" onsubmit="return confirm('Delete this service?');">
              <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
              <button type="submit" name="delete_service" class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center text-red-400 shrink-0">
                <?= svg_icon('trash', 'w-4 h-4') ?>
              </button>
            </form>
          </div>
          <div id="edit-<?= (int) $s['id'] ?>" class="hidden bg-gray-50 px-4 pb-4">
            <form action="" method="POST" class="flex flex-col sm:flex-row gap-2 pt-3">
              <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
              <input type="text" name="name" value="<?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>" required
                class="flex-1 h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
              <select name="icon" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?php foreach ($availableIcons as $iconKey): ?>
                  <option value="<?= $iconKey ?>" <?= $s['icon'] === $iconKey ? 'selected' : '' ?>><?= ucfirst(str_replace('-', ' ', $iconKey)) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="edit_service" class="h-10 px-4 bg-brand-500 hover:bg-brand-700 text-white font-semibold rounded-lg text-sm transition-colors">
                Save
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php include 'includes/footer.php'; ?>