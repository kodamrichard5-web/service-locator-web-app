<?php
/**
 * customer/booking.php
 * Booking form for a chosen vendor; inserts a new row into bookings on submit.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$vendor_id = filter_input(INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

if (!$vendor_id) {
    header('Location: home.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT v.id, v.name, v.price, v.service_id, s.name AS service_name
     FROM vendors v
     JOIN services s ON s.id = v.service_id
     WHERE v.id = :id AND v.status = 'active'
     LIMIT 1"
);
$stmt->execute(['id' => $vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: home.php');
    exit;
}

$errors = [];
$old = [
    'booking_date' => $_POST['booking_date'] ?? '',
    'booking_time' => $_POST['booking_time'] ?? '',
    'address'      => $_POST['address'] ?? '',
    'notes'        => $_POST['notes'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_date = trim($old['booking_date']);
    $booking_time = trim($old['booking_time']);
    $address      = trim($old['address']);
    $notes        = trim($old['notes']);

    if ($booking_date === '') {
        $errors[] = 'Please choose a date.';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Please choose a date that is today or later.';
    }

    if ($booking_time === '') {
        $errors[] = 'Please choose a time.';
    }

    if ($address === '') {
        $errors[] = 'Please enter the service address.';
    }

    if (empty($errors)) {
        try {
            $insert = $pdo->prepare(
                "INSERT INTO bookings (customer_id, vendor_id, service_id, booking_date, booking_time, address, notes, status)
                 VALUES (:customer_id, :vendor_id, :service_id, :booking_date, :booking_time, :address, :notes, 'pending')"
            );
            $insert->execute([
                'customer_id'  => $_SESSION['user_id'],
                'vendor_id'    => $vendor['id'],
                'service_id'   => $vendor['service_id'],
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'address'      => $address,
                'notes'        => $notes !== '' ? $notes : null,
            ]);

            header('Location: bookings.php');
            exit;
        } catch (PDOException $e) {
            error_log('booking.php insert failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong creating your booking. Please try again.';
        }
    }
}

// Prefill address from the customer's saved profile address, if any and not already typed.
if ($old['address'] === '') {
    $userStmt = $pdo->prepare('SELECT address FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $_SESSION['user_id']]);
    $old['address'] = (string) ($userStmt->fetchColumn() ?: '');
}

$page_title = 'Book ' . $vendor['name'];
$active_tab = 'home';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-4xl bg-white min-h-screen shadow-sm">

    <!-- Header -->
    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="vendor.php?id=<?= (int) $vendor['id'] ?>" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Confirm Booking</h1>
    </header>

    <div class="px-5 mt-4">

      <!-- Summary -->
      <div class="bg-brand-50 rounded-2xl p-4 flex items-center gap-3 mb-6">
        <?= initials_avatar($vendor['name'], 'w-12 h-12', 'text-sm') ?>
        <div class="min-w-0">
          <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-xs text-gray-600"><?= htmlspecialchars($vendor['service_name'], ENT_QUOTES, 'UTF-8') ?> &bull; $<?= number_format((float) $vendor['price'], 2) ?></p>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="" method="POST" novalidate class="pb-6">
        <input type="hidden" name="vendor_id" value="<?= (int) $vendor['id'] ?>">

        <div class="mb-4">
          <label for="booking_date" class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
          <input type="date" id="booking_date" name="booking_date"
            value="<?= htmlspecialchars($old['booking_date'], ENT_QUOTES, 'UTF-8') ?>"
            min="<?= date('Y-m-d') ?>" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>

        <div class="mb-4">
          <label for="booking_time" class="block text-sm font-medium text-gray-700 mb-1.5">Time</label>
          <input type="time" id="booking_time" name="booking_time"
            value="<?= htmlspecialchars($old['booking_time'], ENT_QUOTES, 'UTF-8') ?>" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>

        <div class="mb-6">
          <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Service Address</label>
          <textarea id="address" name="address" rows="3" required
            placeholder="Enter the address where the service is needed"
            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
          ><?= htmlspecialchars($old['address'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="mb-6">
          <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Job Details <span class="text-gray-400 font-normal">(optional)</span></label>
          <textarea id="notes" name="notes" rows="2" maxlength="255"
            placeholder="e.g. Kitchen sink leak repair"
            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
          ><?= htmlspecialchars($old['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
          <p class="text-xs text-gray-400 mt-1">A short description helps the technician prepare — shown as the job title on their end.</p>
        </div>

        <button type="submit"
          class="w-full h-14 bg-brand-500 hover:bg-brand-700 text-white font-bold text-lg rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
          Confirm Booking
        </button>
      </form>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>