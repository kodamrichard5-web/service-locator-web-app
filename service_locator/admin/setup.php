<?php
/**
 * admin/setup.php
 * Creates the FIRST admin account. Deliberately not a public registration page —
 * it only works while the admins table is empty, and locks itself the moment
 * the first admin exists. Delete this file after use if you want to be extra safe.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();

if ($adminCount > 0) {
    // Setup already used — never allow creating additional admins from here.
    $page_title = 'Setup Unavailable';
    include 'includes/auth_header.php';
    ?>
    <div class="min-h-screen flex items-center justify-center px-4">
      <div class="max-w-md text-center bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-xl font-bold text-gray-800 mb-2">Setup already completed</h1>
        <p class="text-sm text-gray-500 mb-6">An admin account already exists. For security, this page only works once. Additional admins should be added by an existing admin (feature not yet built) or directly in the database.</p>
        <a href="login.php" class="inline-block bg-brand-500 hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl transition-colors">Go to Login</a>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']  = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    if ($old['name'] === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Re-check under the assumption another admin could be created concurrently.
        $recheck = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        if ($recheck > 0) {
            $errors[] = 'Setup was just completed by someone else. Please log in instead.';
        } else {
            $insert = $pdo->prepare('INSERT INTO admins (name, email, password) VALUES (:name, :email, :password)');
            $insert->execute([
                'name'     => $old['name'],
                'email'    => $old['email'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: login.php?created=1');
            exit;
        }
    }
}

$page_title = 'Admin Setup';
include 'includes/auth_header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-[420px]">

    <div class="flex flex-col items-center text-center mb-6">
      <div class="w-12 h-12 rounded-full bg-brand-500 flex items-center justify-center shrink-0 shadow-md shadow-brand-500/30 mb-2">
        <?= svg_icon('gear', 'w-6 h-6 text-white') ?>
      </div>
      <h1 class="text-2xl font-extrabold text-brand-900">Admin Setup</h1>
      <p class="text-sm text-gray-500">Create the first admin account for ServiceLocator</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg shadow-brand-900/5 p-6 sm:p-8">

      <?php if (!empty($errors)): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="" method="POST" novalidate>
        <div class="mb-4">
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8') ?>" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>
        <div class="mb-4">
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>
        <div class="mb-4">
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
          <input type="password" id="password" name="password" minlength="8" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>
        <div class="mb-6">
          <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" minlength="8" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>
        <button type="submit"
          class="w-full h-12 bg-brand-500 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors duration-200">
          Create Admin Account
        </button>
      </form>
    </div>

    <p class="text-xs text-gray-400 text-center mt-4">This page disables itself automatically once an admin account exists.</p>
  </div>
</div>

</body>
</html>