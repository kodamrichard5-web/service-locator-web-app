<?php
/**
 * admin/login.php
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';

$errors = [];
$old_email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $old_email;
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            $errors[] = 'Incorrect email or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: dashboard.php');
            exit;
        }
    }
}

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$justCreated = isset($_GET['created']);

$page_title = 'Admin Login';
include 'includes/auth_header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-[400px]">

    <div class="flex flex-col items-center text-center mb-6">
      <div class="w-12 h-12 rounded-full bg-brand-500 flex items-center justify-center shrink-0 shadow-md shadow-brand-500/30 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-6 h-6 text-white">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="white"/>
          <circle cx="12" cy="9" r="2.5" fill="#22C55E"/>
        </svg>
      </div>
      <h1 class="text-xl font-extrabold leading-none mb-2">
        <span class="text-brand-900">Service</span><span class="text-brand-500">Locator</span>
      </h1>
      <p class="text-sm text-gray-500">Admin Panel</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg shadow-brand-900/5 p-6 sm:p-8">

      <?php if ($justCreated): ?>
        <div class="mb-5 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
          <p class="text-sm text-brand-700 font-medium">Admin account created. Log in to continue.</p>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($adminCount === 0): ?>
        <div class="mb-5 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
          <p class="text-sm text-yellow-800">No admin account exists yet. <a href="setup.php" class="font-semibold underline">Create one here</a>.</p>
        </div>
      <?php endif; ?>

      <form action="" method="POST" novalidate>
        <div class="mb-4">
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="admin@servicelocator.com" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>
        <div class="mb-6">
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>
        <button type="submit"
          class="w-full h-12 bg-brand-500 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
          Login
        </button>
      </form>
    </div>
  </div>
</div>

</body>
</html>