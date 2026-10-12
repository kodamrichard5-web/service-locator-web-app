<?php
/**
 * customer/addresses.php
 * Saved addresses — add, set default, delete. Real feature backed by the
 * addresses table, not just a stub page.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $label = trim($_POST['label'] ?? '');
    $address_line = trim($_POST['address_line'] ?? '');

    if ($label === '') {
        $errors[] = 'Please give this address a label (e.g. Home, Work).';
    }
    if ($address_line === '') {
        $errors[] = 'Please enter the address.';
    }

    if (empty($errors)) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE customer_id = :id');
        $countStmt->execute(['id' => $customer_id]);
        $isFirst = (int) $countStmt->fetchColumn() === 0;

        $insert = $pdo->prepare(
            'INSERT INTO addresses (customer_id, label, address_line, is_default) VALUES (:cid, :label, :line, :is_default)'
        );
        $insert->execute([
            'cid' => $customer_id, 'label' => $label, 'line' => $address_line,
            'is_default' => $isFirst ? 1 : 0,
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    $id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
    if ($id) {
        $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE customer_id = :cid')->execute(['cid' => $customer_id]);
        $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = :id AND customer_id = :cid')
            ->execute(['id' => $id, 'cid' => $customer_id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
    if ($id) {
        $del = $pdo->prepare('DELETE FROM addresses WHERE id = :id AND customer_id = :cid');
        $del->execute(['id' => $id, 'cid' => $customer_id]);
    }
}

$stmt = $pdo->prepare('SELECT * FROM addresses WHERE customer_id = :id ORDER BY is_default DESC, created_at DESC');
$stmt->execute(['id' => $customer_id]);
$addresses = $stmt->fetchAll();

$page_title = 'Saved Addresses';
$active_tab = 'profile';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="profile.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Saved Addresses</h1>
    </header>

    <div class="px-5 mt-5">

      <?php if (!empty($errors)): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (empty($addresses)): ?>
        <div class="bg-brand-50 border border-dashed border-brand-200 rounded-2xl py-8 px-6 flex flex-col items-center text-center mb-5">
          <span class="w-12 h-12 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm">
            <?= svg_icon('pin', 'w-5 h-5 text-brand-600') ?>
          </span>
          <p class="text-sm font-semibold text-gray-700">No saved addresses yet</p>
          <p class="text-xs text-gray-500 mt-1">Add one below so booking a service is faster next time.</p>
        </div>
      <?php else: ?>
        <div class="space-y-3 mb-5">
          <?php foreach ($addresses as $addr): ?>
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                  <p class="font-bold text-gray-900"><?= htmlspecialchars($addr['label'], ENT_QUOTES, 'UTF-8') ?></p>
                  <?php if ((int) $addr['is_default'] === 1): ?>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-brand-100 text-brand-700">Default</span>
                  <?php endif; ?>
                </div>
                <form action="" method="POST" onsubmit="return confirm('Delete this address?');">
                  <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                  <button type="submit" name="delete_address" class="w-7 h-7 rounded-lg hover:bg-red-50 flex items-center justify-center text-red-400">
                    <?= svg_icon('trash', 'w-4 h-4') ?>
                  </button>
                </form>
              </div>
              <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($addr['address_line'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php if ((int) $addr['is_default'] !== 1): ?>
                <form action="" method="POST" class="mt-2">
                  <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                  <button type="submit" name="set_default" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    Set as default
                  </button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Add address -->
      <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-sm font-bold text-gray-800 mb-3">Add New Address</h2>
        <form action="" method="POST">
          <div class="mb-3">
            <label for="label" class="block text-xs font-medium text-gray-600 mb-1.5">Label</label>
            <input type="text" id="label" name="label" placeholder="e.g. Home, Work" required
              class="w-full h-11 rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="address_line" class="block text-xs font-medium text-gray-600 mb-1.5">Address</label>
            <textarea id="address_line" name="address_line" rows="2" placeholder="Enter the full address" required
              class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"></textarea>
          </div>
          <button type="submit" name="add_address"
            class="w-full h-11 bg-brand-500 hover:bg-brand-700 text-white font-bold rounded-xl text-sm transition-colors duration-200">
            Save Address
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>