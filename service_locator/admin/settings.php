<?php
/**
 * admin/settings.php
 * Manage the logged-in admin's own name/email and password.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$admin_id = $_SESSION['admin_id'];
$errors = [];
$success = null;

$stmt = $pdo->prepare('SELECT name, email FROM admins WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: ../logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM admins WHERE email = :email AND id != :id LIMIT 1');
        $check->execute(['email' => $email, 'id' => $admin_id]);
        if ($check->fetch()) {
            $errors[] = 'That email is already in use.';
        }
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE admins SET name = :name, email = :email WHERE id = :id');
        $update->execute(['name' => $name, 'email' => $email, 'id' => $admin_id]);
        $_SESSION['admin_name'] = $name;
        $admin = ['name' => $name, 'email' => $email];
        $success = 'Profile updated.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $pwStmt = $pdo->prepare('SELECT password FROM admins WHERE id = :id LIMIT 1');
    $pwStmt->execute(['id' => $admin_id]);
    $hashed = $pwStmt->fetchColumn();

    if (!password_verify($current, $hashed)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
        $update->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => $admin_id]);
        $success = 'Password changed.';
    }
}

$page_title = 'Settings';
$page_subtitle = 'Manage your admin account';
$active_admin_tab = 'settings';
include 'includes/header.php';
?>

<div class="max-w-xl space-y-4">

  <?php if ($success): ?>
    <div class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
      <p class="text-sm text-brand-700 font-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
      <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
        <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-bold text-gray-800 mb-4">Profile</h3>
    <form action="" method="POST">
      <div class="mb-3">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($admin['name'], ENT_QUOTES, 'UTF-8') ?>" required
          class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      </div>
      <div class="mb-4">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?>" required
          class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      </div>
      <button type="submit" name="update_profile" class="h-10 px-5 bg-brand-500 hover:bg-brand-700 text-white font-semibold rounded-lg text-sm transition-colors">
        Save Changes
      </button>
    </form>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-bold text-gray-800 mb-4">Change Password</h3>
    <form action="" method="POST">
      <div class="mb-3">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">Current Password</label>
        <input type="password" name="current_password" required
          class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      </div>
      <div class="mb-3">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">New Password</label>
        <input type="password" name="new_password" minlength="8" required
          class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      </div>
      <div class="mb-4">
        <label class="block text-xs font-medium text-gray-600 mb-1.5">Confirm New Password</label>
        <input type="password" name="confirm_password" minlength="8" required
          class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
      </div>
      <button type="submit" name="change_password" class="h-10 px-5 border border-brand-500 text-brand-600 hover:bg-brand-50 font-semibold rounded-lg text-sm transition-colors">
        Update Password
      </button>
    </form>
  </div>

</div>

<?php include 'includes/footer.php'; ?>