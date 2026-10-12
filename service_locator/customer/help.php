<?php
/**
 * customer/help.php
 * Static FAQ + contact info. No live-chat/ticketing system exists, so this
 * doesn't pretend to be one.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$faqs = [
    [
        'q' => 'How do I book a service?',
        'a' => 'From the Home tab, tap a service category, choose a technician, and tap "Book Now" to pick a date, time, and address.',
    ],
    [
        'q' => 'How do I pay?',
        'a' => "You'll pay your technician directly (cash or mobile money) once the job is done. Online payment isn't available yet.",
    ],
    [
        'q' => 'Can I cancel a booking?',
        'a' => 'Once a technician accepts your booking, cancellations happen through the technician directly for now — message them from the Chat button on your booking.',
    ],
    [
        'q' => 'How do I leave a review?',
        'a' => 'Once a booking is marked Completed, a "Rate Service" button appears on that booking in My Bookings.',
    ],
    [
        'q' => 'How do I change my password?',
        'a' => 'Go to Profile → tap your name at the top → Change Password.',
    ],
];

$page_title = 'Help & Support';
$active_tab = 'profile';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-2 flex items-center gap-3">
      <a href="profile.php" aria-label="Back" class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <h1 class="text-xl font-extrabold text-brand-900">Help &amp; Support</h1>
    </header>

    <div class="px-5 mt-6 pb-6">

      <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Frequently Asked Questions</h2>
      <div class="space-y-2 mb-8">
        <?php foreach ($faqs as $i => $faq): ?>
          <details class="bg-white border border-gray-100 rounded-xl shadow-sm px-4 py-3 group">
            <summary class="flex items-center justify-between cursor-pointer list-none">
              <span class="text-sm font-semibold text-gray-800 pr-3"><?= htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="text-gray-400 group-open:rotate-180 transition-transform shrink-0"><?= svg_icon('chevron-down', 'w-4 h-4') ?></span>
            </summary>
            <p class="text-sm text-gray-600 mt-2 leading-relaxed"><?= htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8') ?></p>
          </details>
        <?php endforeach; ?>
      </div>

      <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Still Need Help?</h2>
      <div class="bg-brand-50 rounded-2xl p-5">
        <p class="text-sm text-gray-700 mb-3">Reach out and we'll get back to you as soon as we can.</p>
        <a href="mailto:support@servicelocator.app" class="flex items-center gap-2 text-sm font-semibold text-brand-700 mb-2">
          <?= svg_icon('message', 'w-4 h-4') ?> support@servicelocator.app
        </a>
      </div>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>