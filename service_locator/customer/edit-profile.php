<?php
/**
 * customer/edit-profile.php
 * Edit account details (name/email/phone/address) and change password.
 * Reached by tapping the profile card on customer/profile.php.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];
$errors = [];
$success = null;

$stmt = $pdo->prepare('SELECT name, email, phone, address, photo_url FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $customer_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone === '') {
        $errors[] = 'Please enter your phone number.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $check->execute(['email' => $email, 'id' => $customer_id]);
        if ($check->fetch()) {
            $errors[] = 'That email is already in use by another account.';
        }
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare(
                'UPDATE users SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id'
            );
            $update->execute([
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'address' => $address, 'id' => $customer_id,
            ]);
            $_SESSION['name'] = $name;
            $user = array_merge($user, ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address]);
            $success = 'Your profile has been updated.';
        } catch (PDOException $e) {
            error_log('edit-profile.php update failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your changes. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $pwStmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $pwStmt->execute(['id' => $customer_id]);
    $hashed = $pwStmt->fetchColumn();

    if (!password_verify($current, $hashed)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $update->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => $customer_id]);
        $success = 'Your password has been changed.';
    }
}

$page_title = 'Edit Profile';
$active_tab = 'profile';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="profile.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Edit Profile</h1>
    </header>

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

      <!-- Edit profile -->
      <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
        <div class="flex flex-col items-center text-center mb-5">
          <?php if (!empty($user['photo_url'])): ?>
            <img src="<?= htmlspecialchars($user['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt=""
                 class="w-20 h-20 rounded-full object-cover">
          <?php else: ?>
            <?= initials_avatar($user['name'], 'w-20 h-20', 'text-2xl') ?>
          <?php endif; ?>
          <p class="text-xs text-gray-400 mt-2">Photo upload isn't available yet — coming soon.</p>
        </div>

        <h2 class="text-lg font-bold text-gray-900 mb-4">Account Details</h2>
        <form action="" method="POST" novalidate>
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8') ?>" required
              class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
          </div>
          <div class="mb-5">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Default Address</label>
            <textarea id="address" name="address" rows="2"
              class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
            ><?= htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <p class="text-xs text-gray-400 mt-1.5">For multiple addresses, use <a href="addresses.php" class="text-brand-600 font-medium">Saved Addresses</a>.</p>
          </div>
          <button type="submit" name="update_profile"
            class="w-full h-12 bg-brand-500 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors duration-200">
            Save Changes
          </button>
        </form>
      </div>

      <!-- Change password -->
      <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
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

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>