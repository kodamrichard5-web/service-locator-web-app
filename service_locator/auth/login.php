<?php
/**
 * auth/login.php
 * Single login for both customers and technicians (vendors) — no manual
 * role selection. The email is checked against both the users and vendors
 * tables; whichever one the password matches determines the role.
 */

session_start();
require_once '../db.php';

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
        $account = null;
        $role = null;

        // Try customer first.
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $customerMatch = $stmt->fetch();
        if ($customerMatch && password_verify($password, $customerMatch['password'])) {
            $account = $customerMatch;
            $role = 'customer';
        }

        // Only try vendor if we haven't already found a matching customer account.
        if (!$account) {
            $stmt = $pdo->prepare('SELECT * FROM vendors WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $vendorMatch = $stmt->fetch();
            if ($vendorMatch && password_verify($password, $vendorMatch['password'])) {
                $account = $vendorMatch;
                $role = 'vendor';
            }
        }

        if (!$account) {
            $errors[] = 'Incorrect email or password.';
        } elseif ($role === 'vendor' && $account['status'] !== 'active') {
            $errors[] = 'Your technician account is not active yet. Please contact support.';
        }

        if (empty($errors)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $account['id'];
            $_SESSION['role']    = $role;
            $_SESSION['name']    = $account['name'];

            if ($role === 'vendor') {
                header('Location: ../vendor/dashboard.php');
            } else {
                header('Location: ../customer/home.php');
            }
            exit;
        }
    }
}

$just_registered = isset($_GET['registered']);
$registered_role  = $_GET['role'] ?? 'customer';

$page_title = 'Login';
include '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-[420px]">

    <!-- Header -->
    <div class="flex flex-col items-center text-center mb-6">
      <div class="w-12 h-12 rounded-full bg-brand-500 flex items-center justify-center shrink-0 shadow-md shadow-brand-500/30 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-6 h-6 text-white">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="white"/>
          <circle cx="12" cy="9" r="2.5" fill="#22C55E"/>
        </svg>
      </div>
      <h1 class="text-2xl font-extrabold leading-none mb-2">
        <span class="text-brand-900">Service</span><span class="text-brand-500">Locator</span>
      </h1>
      <p class="text-sm text-gray-500">Find trusted services near you</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg shadow-brand-900/5 p-6 sm:p-8">

      <?php if ($just_registered): ?>
        <div class="mb-5 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3">
          <p class="text-sm text-brand-700 font-medium">
            Account created! Log in as a <?= $registered_role === 'vendor' ? 'technician' : 'customer' ?> to continue.
          </p>
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

      <h2 class="text-2xl font-bold text-brand-900 mb-1">Welcome Back!</h2>
      <p class="text-sm text-gray-500 mb-6">Sign in to continue to ServiceLocator</p>

      <form action="" method="POST" novalidate>

        <div class="mb-4">
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="you@example.com" required
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

      <p class="text-sm text-gray-600 text-center mt-6">
        Don't have an account?
        <a href="register.php" class="font-bold text-brand-500 hover:text-brand-700">Sign Up</a>
      </p>
    </div>
  </div>
</div>

</body>
</html>