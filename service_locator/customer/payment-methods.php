<?php
/**
 * customer/payment-methods.php
 * Honest placeholder: there's no payment gateway wired into ServiceLocator
 * yet (flagged the same way on admin/payments.php). This page says so plainly
 * rather than showing a fake "Add Card" form that doesn't actually charge anyone.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$page_title = 'Payment Methods';
$active_tab = 'profile';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="profile.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Payment Methods</h1>
    </header>

    <div class="px-5 mt-6">
      <div class="bg-brand-50 border border-dashed border-brand-200 rounded-2xl py-10 px-6 flex flex-col items-center text-center">
        <span class="w-14 h-14 rounded-full bg-white flex items-center justify-center mb-3 shadow-sm">
          <?= svg_icon('credit-card', 'w-6 h-6 text-brand-600') ?>
        </span>
        <p class="text-base font-semibold text-gray-700">Online payments aren't set up yet</p>
        <p class="text-sm text-gray-500 mt-1.5 max-w-xs">
          For now, you'll pay your technician directly (cash or mobile money) when the job is done.
          Card and mobile money payment through the app is coming soon.
        </p>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>