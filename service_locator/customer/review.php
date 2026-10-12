<?php
/**
 * customer/review.php
 * Rate + review a completed booking. One review per booking, enforced by a
 * unique constraint on reviews.booking_id.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];
$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT b.id, b.status, v.id AS vendor_id, v.name AS vendor_name, s.name AS service_name
     FROM bookings b
     JOIN vendors v  ON v.id = b.vendor_id
     JOIN services s ON s.id = b.service_id
     WHERE b.id = :id AND b.customer_id = :cid LIMIT 1"
);
$stmt->execute(['id' => $booking_id, 'cid' => $customer_id]);
$booking = $stmt->fetch();

if (!$booking || $booking['status'] !== 'completed') {
    header('Location: bookings.php');
    exit;
}

$existingStmt = $pdo->prepare('SELECT id FROM reviews WHERE booking_id = :id LIMIT 1');
$existingStmt->execute(['id' => $booking_id]);
if ($existingStmt->fetch()) {
    header('Location: bookings.php');
    exit;
}

$errors = [];
$old_rating = (int) ($_POST['rating'] ?? 5);
$old_comment = $_POST['comment'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');

    if (!$rating || $rating < 1 || $rating > 5) {
        $errors[] = 'Please select a rating from 1 to 5 stars.';
    }

    if (empty($errors)) {
        try {
            $insert = $pdo->prepare(
                'INSERT INTO reviews (booking_id, customer_id, vendor_id, rating, comment)
                 VALUES (:booking_id, :customer_id, :vendor_id, :rating, :comment)'
            );
            $insert->execute([
                'booking_id'  => $booking_id,
                'customer_id' => $customer_id,
                'vendor_id'   => $booking['vendor_id'],
                'rating'      => $rating,
                'comment'     => $comment !== '' ? $comment : null,
            ]);

            // Recompute this vendor's average rating from all their reviews.
            $avgStmt = $pdo->prepare('SELECT AVG(rating) FROM reviews WHERE vendor_id = :vid');
            $avgStmt->execute(['vid' => $booking['vendor_id']]);
            $avg = (float) $avgStmt->fetchColumn();
            $updateVendor = $pdo->prepare('UPDATE vendors SET rating = :rating WHERE id = :id');
            $updateVendor->execute(['rating' => round($avg, 1), 'id' => $booking['vendor_id']]);

            header('Location: bookings.php');
            exit;
        } catch (PDOException $e) {
            error_log('review.php insert failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong submitting your review. Please try again.';
        }
    }
}

$page_title = 'Rate Service';
$active_tab = 'bookings';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-4xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="bookings.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Rate Service</h1>
    </header>

    <div class="px-5 mt-6 flex flex-col items-center text-center">
      <?= initials_avatar($booking['vendor_name'], 'w-16 h-16', 'text-xl') ?>
      <p class="font-bold text-gray-900 mt-3"><?= htmlspecialchars($booking['vendor_name'], ENT_QUOTES, 'UTF-8') ?></p>
      <p class="text-sm text-gray-500"><?= htmlspecialchars($booking['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
      <p class="text-sm text-gray-600 mt-3">How was your experience?</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
        <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
          <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="POST" class="px-5 mt-6 pb-6">
      <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">

      <div class="flex items-center justify-center gap-2 mb-6" id="starPicker">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <button type="button" data-value="<?= $i ?>" class="star-btn text-4xl leading-none transition-colors <?= $i <= $old_rating ? 'text-yellow-400' : 'text-gray-200' ?>">★</button>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="rating" id="ratingInput" value="<?= $old_rating ?>">

      <div class="mb-6">
        <label for="comment" class="block text-sm font-medium text-gray-700 mb-1.5">Your Review <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea id="comment" name="comment" rows="4" placeholder="Tell others about your experience..."
          class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
        ><?= htmlspecialchars($old_comment, ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <button type="submit"
        class="w-full h-14 bg-brand-500 hover:bg-brand-700 text-white font-bold text-lg rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
        Submit Review
      </button>
    </form>

  </div>
</div>

<script>
  const starButtons = document.querySelectorAll('.star-btn');
  const ratingInput = document.getElementById('ratingInput');

  function paintStars(value) {
    starButtons.forEach(btn => {
      const active = parseInt(btn.dataset.value, 10) <= value;
      btn.classList.toggle('text-yellow-400', active);
      btn.classList.toggle('text-gray-200', !active);
    });
  }

  starButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const value = parseInt(btn.dataset.value, 10);
      ratingInput.value = value;
      paintStars(value);
    });
  });
</script>

<?php include '../includes/footer.php'; ?>