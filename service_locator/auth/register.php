<?php
/**
 * auth/register.php
 * Registration for both customers and technicians (vendors).
 * No demo/seed accounts — every row is created here by a real user.
 */

session_start();
require_once '../db.php';

$errors = [];
$old = [
    'account_type' => $_POST['account_type'] ?? 'customer',
    'name'         => trim($_POST['name'] ?? ''),
    'email'        => trim($_POST['email'] ?? ''),
    'phone'        => trim($_POST['phone'] ?? ''),
    'service_id'   => $_POST['service_id'] ?? '',
];

// Fetch services for the Technician tab's dropdown.
$services = $pdo->query('SELECT id, name FROM services ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $old['account_type'] === 'vendor' ? 'vendor' : 'customer';
    $name     = $old['name'];
    $email    = $old['email'];
    $phone    = $old['phone'];
    $password = $_POST['password'] ?? '';
    $service_id = $account_type === 'vendor' ? (int) ($old['service_id'] ?: 0) : null;

    if ($name === '') {
        $errors[] = 'Please enter your full name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone === '') {
        $errors[] = 'Please enter your phone number.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($account_type === 'vendor' && $service_id <= 0) {
        $errors[] = 'Please select the service you provide.';
    }

    if (empty($errors)) {
        $table = $account_type === 'vendor' ? 'vendors' : 'users';

        $check = $pdo->prepare("SELECT id FROM {$table} WHERE email = :email LIMIT 1");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            if ($account_type === 'vendor') {
                $stmt = $pdo->prepare(
                    'INSERT INTO vendors (name, email, password, phone, service_id, bio, price, rating, is_verified, status)
                     VALUES (:name, :email, :password, :phone, :service_id, :bio, 0.00, 0.0, 0, :status)'
                );
                $stmt->execute([
                    'name'       => $name,
                    'email'      => $email,
                    'password'   => $hashed,
                    'phone'      => $phone,
                    'service_id' => $service_id,
                    'bio'        => '',
                    'status'     => 'active',
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password, phone, address, role)
                     VALUES (:name, :email, :password, :phone, :address, :role)'
                );
                $stmt->execute([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $hashed,
                    'phone'    => $phone,
                    'address'  => '',
                    'role'     => 'customer',
                ]);
            }

            header('Location: login.php?registered=1&role=' . urlencode($account_type));
            exit;
        } catch (PDOException $e) {
            error_log('register.php insert failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }
    }
}

$page_title = 'Create Account';
include '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-[440px]">

    <!-- Header -->
    <div class="flex flex-col items-center text-center mb-6">
      <div class="flex items-center gap-2.5 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-9 h-9 shrink-0">
          <defs>
            <linearGradient id="pinGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#4ADE80"/>
              <stop offset="100%" stop-color="#16A34A"/>
            </linearGradient>
          </defs>
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="url(#pinGrad)"/>
          <circle cx="12" cy="9" r="2.6" fill="white"/>
        </svg>
        <h1 class="text-2xl font-extrabold text-brand-500">ServiceLocator</h1>
      </div>
      <h2 class="text-3xl font-extrabold text-brand-900 mb-1">Create Account</h2>
      <p class="text-sm text-gray-500">Join as a customer or offer your services as a technician</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg shadow-brand-900/5 p-6 sm:p-7">

      <?php if (!empty($errors)): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="flex items-center gap-2 bg-brand-50 p-1.5 rounded-full mb-6">
        <button type="button" data-tab="customer"
          class="tab-btn flex-1 py-2.5 rounded-full text-sm font-semibold transition-colors">
          Customer
        </button>
        <button type="button" data-tab="vendor"
          class="tab-btn flex-1 py-2.5 rounded-full text-sm font-semibold transition-colors">
          Technician
        </button>
      </div>

      <form action="" method="POST" novalidate id="registerForm">
        <input type="hidden" name="account_type" id="account_type" value="<?= htmlspecialchars($old['account_type'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-4">
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Enter your full name" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>

        <div class="mb-4">
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>"
            placeholder="you@example.com" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>

        <div class="mb-4">
          <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
          <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>"
            placeholder="+1 (555) 123-4567" required
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>

        <div class="mb-4" id="serviceField">
          <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1.5">Service You Provide</label>
          <select id="service_id" name="service_id"
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
            <option value="">Select a service</option>
            <?php foreach ($services as $service): ?>
              <option value="<?= (int) $service['id'] ?>" <?= (string) $old['service_id'] === (string) $service['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-6">
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
          <input type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8"
            class="w-full h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
        </div>

        <button type="submit"
          class="w-full h-12 bg-brand-500 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
          Create Account
        </button>
      </form>

      <p class="text-sm text-gray-600 text-center mt-6">
        Already have an account?
        <a href="login.php" class="font-bold text-brand-500 hover:text-brand-700">Login</a>
      </p>
    </div>
  </div>
</div>

<script>
  const tabButtons = document.querySelectorAll('.tab-btn');
  const accountTypeInput = document.getElementById('account_type');
  const serviceField = document.getElementById('serviceField');
  const serviceSelect = document.getElementById('service_id');

  function setTab(type) {
    accountTypeInput.value = type;
    tabButtons.forEach(btn => {
      const active = btn.dataset.tab === type;
      btn.classList.toggle('bg-brand-500', active);
      btn.classList.toggle('text-white', active);
      btn.classList.toggle('shadow-sm', active);
      btn.classList.toggle('text-gray-500', !active);
    });
    if (type === 'vendor') {
      serviceField.classList.remove('hidden');
      serviceSelect.setAttribute('required', 'required');
    } else {
      serviceField.classList.add('hidden');
      serviceSelect.removeAttribute('required');
    }
  }

  tabButtons.forEach(btn => btn.addEventListener('click', () => setTab(btn.dataset.tab)));
  setTab(accountTypeInput.value === 'vendor' ? 'vendor' : 'customer');
</script>

<?php // No footer nav on auth pages — just close the body/html tags. ?>
</body>
</html>