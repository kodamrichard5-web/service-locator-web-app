<?php
/**
 * customer/terms.php
 * Static Terms of Service + Privacy summary. Generic starter text — not
 * reviewed by a lawyer. Swap in your real terms before going live.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$page_title = 'Terms & Privacy';
$active_tab = 'profile';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="profile.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Terms &amp; Privacy</h1>
    </header>

    <div class="px-5 mt-6 pb-6 space-y-5 text-sm text-gray-600 leading-relaxed">

      <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
        <p class="text-yellow-800 text-xs">This is generic placeholder text, not reviewed by a lawyer. Replace it with your actual terms before launching to real customers.</p>
      </div>

      <div>
        <h2 class="font-bold text-gray-800 mb-1.5">1. Using ServiceLocator</h2>
        <p>ServiceLocator connects customers with independent local technicians. We don't employ technicians directly — each provider on the platform manages their own bookings, pricing, and service quality.</p>
      </div>

      <div>
        <h2 class="font-bold text-gray-800 mb-1.5">2. Bookings & Payment</h2>
        <p>Booking a service through the app is a request sent to a technician, who may accept or decline it. Payment currently happens directly between you and the technician (cash or mobile money) — the app does not process payments.</p>
      </div>

      <div>
        <h2 class="font-bold text-gray-800 mb-1.5">3. Reviews</h2>
        <p>Reviews may only be left for bookings you've actually completed. Reviews should reflect genuine experiences; we may remove reviews that violate this.</p>
      </div>

      <div>
        <h2 class="font-bold text-gray-800 mb-1.5">4. Your Data</h2>
        <p>We store the account details you provide (name, email, phone, address) to operate the service — matching you with technicians, processing bookings, and enabling messaging. We don't sell your data to third parties.</p>
      </div>

      <div>
        <h2 class="font-bold text-gray-800 mb-1.5">5. Account Responsibility</h2>
        <p>You're responsible for keeping your password secure and for the accuracy of the information you provide.</p>
      </div>

      <div>
        <h2 class="font-bold text-gray-800 mb-1.5">6. Changes</h2>
        <p>These terms may be updated from time to time. Continued use of the app after a change means you accept the updated terms.</p>
      </div>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>