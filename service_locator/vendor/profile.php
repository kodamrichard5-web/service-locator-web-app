<?php
/**
 * vendor/profile.php
 * View + edit the logged-in vendor's business profile, change password, logout.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_vendor();

$vendor_id = $_SESSION['user_id'];
$errors = [];
$success = null;

$stmt = $pdo->prepare('SELECT name, email, phone, service_id, bio, price, rating, is_verified, latitude, longitude FROM vendors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: ../logout.php');
    exit;
}

$services = $pdo->query('SELECT id, name FROM services ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $bio        = trim($_POST['bio'] ?? '');
    $price      = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $latitude   = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT) ?: null;
    $longitude  = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT) ?: null;

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone === '') {
        $errors[] = 'Please enter your phone number.';
    }
    if (!$service_id) {
        $errors[] = 'Please select the service you provide.';
    }
    if ($price === false || $price < 0) {
        $errors[] = 'Please enter a valid price.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM vendors WHERE email = :email AND id != :id LIMIT 1');
        $check->execute(['email' => $email, 'id' => $vendor_id]);
        if ($check->fetch()) {
            $errors[] = 'That email is already in use by another account.';
        }
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare(
                'UPDATE vendors SET name = :name, email = :email, phone = :phone,
                 service_id = :service_id, bio = :bio, price = :price,
                 latitude = :latitude, longitude = :longitude WHERE id = :id'
            );
            $update->execute([
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'service_id' => $service_id, 'bio' => $bio, 'price' => $price,
                'latitude' => $latitude, 'longitude' => $longitude,
                'id' => $vendor_id,
            ]);
            $_SESSION['name'] = $name;
            $vendor = array_merge($vendor, [
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'service_id' => $service_id, 'bio' => $bio, 'price' => $price,
                'latitude' => $latitude, 'longitude' => $longitude,
            ]);
            $success = 'Your profile has been updated.';
        } catch (PDOException $e) {
            error_log('vendor/profile.php update failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your changes. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $pwStmt = $pdo->prepare('SELECT password FROM vendors WHERE id = :id LIMIT 1');
    $pwStmt->execute(['id' => $vendor_id]);
    $hashed = $pwStmt->fetchColumn();

    if (!password_verify($current, $hashed)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE vendors SET password = :password WHERE id = :id');
        $update->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => $vendor_id]);
        $success = 'Your password has been changed.';
    }
}

$page_title = 'Profile';
$active_tab = 'profile';
include '../includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  #locationPicker { height: 260px; border-radius: 0.75rem; z-index: 0; }
</style>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl">

    <div class="px-5 pt-6">
      <div class="bg-white rounded-2xl shadow-sm py-6 px-5 flex flex-col items-center text-center">
        <?= initials_avatar($vendor['name'], 'w-20 h-20', 'text-2xl') ?>
        <h1 class="text-2xl font-extrabold text-brand-900 mt-3"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($vendor['email'], ENT_QUOTES, 'UTF-8') ?></p>

        <div class="flex items-center gap-2 mt-4">
          <div class="inline-flex items-center gap-1.5 bg-brand-50 text-brand-700 text-sm font-semibold px-4 py-1.5 rounded-full">
            <?= svg_icon('star', 'w-4 h-4 text-yellow-400') ?>
            <?= number_format((float) $vendor['rating'], 1) ?> rating
          </div>
          <?php if ((int) $vendor['is_verified'] === 1): ?>
            <div class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-sm font-semibold px-4 py-1.5 rounded-full">
              <?= svg_icon('check', 'w-4 h-4') ?> Verified
            </div>
          <?php else: ?>
            <div class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-500 text-sm font-semibold px-4 py-1.5 rounded-full">
              Pending Verification
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="px-5 mt-6 pb-6 space-y-6">

      <?php if ($success): ?>
        <div class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
          <p class="text-sm text-brand-700 font-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Business details -->
      <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Business Details</h2>
        <form action="" method="POST" novalidate>
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($vendor['email'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($vendor['phone'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1.5">Service You Provide</label>
            <select id="service_id" name="service_id" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
              <?php foreach ($services as $service): ?>
                <option value="<?= (int) $service['id'] ?>" <?= (int) $vendor['service_id'] === (int) $service['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label for="price" class="block text-sm font-medium text-gray-700 mb-1.5">Price (GHC)</label>
            <input type="number" id="price" name="price" min="0" step="0.01" value="<?= htmlspecialchars((string) $vendor['price'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-5">
            <label for="bio" class="block text-sm font-medium text-gray-700 mb-1.5">Bio</label>
            <textarea id="bio" name="bio" rows="4" placeholder="Tell customers about your experience and what makes you a great choice..."
              class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
            ><?= htmlspecialchars($vendor['bio'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Business Location</label>
            <p class="text-xs text-gray-500 mb-2">Tap the map to drop a pin at your business location. Customers will see this on your profile.</p>
            <div id="locationPicker"></div>
            <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars((string) ($vendor['latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars((string) ($vendor['longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <p id="locationStatus" class="text-xs text-gray-400 mt-1.5">
              <?= ($vendor['latitude'] && $vendor['longitude']) ? 'Pin set — tap the map again to move it.' : 'No location set yet.' ?>
            </p>
          </div>

          <button type="submit" name="update_profile"
            class="w-full h-12 bg-brand-500 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors duration-200">
            Save Changes
          </button>
        </form>
      </div>

      <!-- Change password -->
      <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Change Password</h2>
        <form action="" method="POST" novalidate>
          <div class="mb-4">
            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
            <input type="password" id="current_password" name="current_password" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
            <input type="password" id="new_password" name="new_password" minlength="8" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-5">
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <button type="submit" name="change_password"
            class="w-full h-12 border border-brand-500 text-brand-600 hover:bg-brand-50 font-bold rounded-xl transition-colors duration-200">
            Update Password
          </button>
        </form>
      </div>

      <!-- Logout -->
      <a href="../logout.php"
         class="block w-full text-center border border-red-200 text-red-600 hover:bg-red-50 font-bold py-3.5 rounded-xl transition-colors duration-200">
        Log Out
      </a>

    </div>
  </div>
</div>

<script>
  const savedLat = <?= $vendor['latitude'] ? (float) $vendor['latitude'] : 'null' ?>;
  const savedLng = <?= $vendor['longitude'] ? (float) $vendor['longitude'] : 'null' ?>;
  const defaultCenter = [5.6037, -0.1870]; // Accra, Ghana — used when no pin is set yet

  const map = L.map('locationPicker').setView(
    savedLat && savedLng ? [savedLat, savedLng] : defaultCenter,
    savedLat && savedLng ? 14 : 7
  );

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19,
  }).addTo(map);

  let marker = null;
  if (savedLat && savedLng) {
    marker = L.marker([savedLat, savedLng]).addTo(map);
  }

  map.on('click', (e) => {
    const { lat, lng } = e.latlng;
    if (marker) {
      marker.setLatLng(e.latlng);
    } else {
      marker = L.marker(e.latlng).addTo(map);
    }
    document.getElementById('latitude').value = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);
    document.getElementById('locationStatus').textContent = 'Pin set — tap the map again to move it.';
  });
</script>

<?php include '../includes/vendor_footer.php'; ?>