<?php
/**
 * customer/home.php
 * Customer home / dashboard page. All content is DB-driven — no demo data.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

// Popular Services — 8 categories from the services table.
$services = $pdo->query('SELECT id, name, icon FROM services ORDER BY id ASC LIMIT 8')->fetchAll();

// Top Rated Professionals — active vendors with rating > 4.
$topProfessionals = $pdo->query(
    "SELECT v.id, v.name, v.rating, v.price, s.name AS service_name
     FROM vendors v
     JOIN services s ON s.id = v.service_id
     WHERE v.rating > 4 AND v.status = 'active'
     ORDER BY v.rating DESC
     LIMIT 5"
)->fetchAll();

// Services Near You — active vendors with a pinned location, for the map.
$mappedVendors = $pdo->query(
    "SELECT v.id, v.name, v.latitude, v.longitude, v.price, s.name AS service_name
     FROM vendors v
     JOIN services s ON s.id = v.service_id
     WHERE v.status = 'active' AND v.latitude IS NOT NULL AND v.longitude IS NOT NULL
     LIMIT 100"
)->fetchAll();
$nearbyCount = (int) $pdo->query("SELECT COUNT(*) FROM vendors WHERE status = 'active'")->fetchColumn();

$page_title = 'Home';
$active_tab = 'home';
include '../includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  #nearbyMap { z-index: 0; }
  .no-scrollbar::-webkit-scrollbar { display: none; }
  .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div class="min-h-screen pb-24 bg-brand-50/40">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-4xl min-h-screen relative">

    <!-- Hero: greeting + search fused into one gradient block -->
    <div class="relative overflow-hidden pb-9 pt-6 px-5 text-white" style="background: linear-gradient(160deg, #22C55E 0%, #16A34A 55%, #15803D 100%);">
      <div class="pointer-events-none absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/10"></div>
      <div class="pointer-events-none absolute top-16 -left-10 w-28 h-28 rounded-full bg-white/10"></div>

      <div class="relative z-10 flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-extrabold leading-tight">Hello,</h1>
          <p class="text-brand-50/90 text-sm mt-0.5">How can we help you today?</p>
        </div>
        <button type="button" aria-label="Notifications" class="relative w-11 h-11 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/25 flex items-center justify-center shrink-0">
          <?= svg_icon('bell', 'w-5 h-5 text-white') ?>
          <span class="absolute top-1.5 right-2 w-2.5 h-2.5 rounded-full bg-red-400 border-2 border-brand-600"></span>
        </button>
      </div>

      <div class="relative z-10 mt-5 flex items-center gap-2.5 bg-white/15 backdrop-blur-sm border border-white/20 rounded-2xl px-3 py-2.5">
        <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
          <?= svg_icon('shield', 'w-4 h-4 text-white') ?>
        </span>
        <p class="text-xs text-white/95 leading-snug">Trusted professionals &bull; Book in minutes &bull; 24/7 Support</p>
      </div>
    </div>

    <!-- Search: floats up over the hero/body seam -->
    <div class="px-5 -mt-5 relative z-20">
      <form action="search.php" method="GET" class="relative">
        <span class="absolute inset-y-0 left-4 flex items-center text-brand-600 pointer-events-none">
          <?= svg_icon('search', 'w-5 h-5') ?>
        </span>
        <input
          type="text" name="q"
          placeholder="Search for services, professionals..."
          class="w-full h-12 rounded-2xl border-0 bg-white pl-11 pr-12 text-sm text-gray-700 placeholder-gray-400 shadow-[0_8px_24px_-6px_rgba(15,23,42,0.18)] focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
        <button type="submit" aria-label="Filters" class="absolute inset-y-0 right-2 my-1.5 w-9 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600 hover:bg-brand-100 transition-colors">
          <?= svg_icon('filter', 'w-4 h-4') ?>
        </button>
      </form>
    </div>

    <!-- Categories: horizontal scrolling pills -->
    <section class="mt-6">
      <div class="flex items-center justify-between mb-3 px-5">
        <h3 class="text-base font-bold text-gray-900">Popular Services</h3>
        <a href="#" class="text-xs font-semibold text-brand-600 flex items-center gap-1 hover:text-brand-700">
          See All <span aria-hidden="true">&rarr;</span>
        </a>
      </div>

      <?php if (empty($services)): ?>
        <p class="text-sm text-gray-500 text-center py-6 px-5">No service categories have been added yet.</p>
      <?php else: ?>
        <div class="flex gap-3 overflow-x-auto no-scrollbar px-5 pb-1 snap-x snap-mandatory">
          <?php foreach ($services as $service): ?>
            <a href="category.php?service_id=<?= (int) $service['id'] ?>"
               class="snap-start shrink-0 flex items-center gap-2.5 bg-white rounded-2xl shadow-[0_2px_10px_rgba(15,23,42,0.05)] ring-1 ring-gray-100 py-2.5 pl-2.5 pr-4 hover:ring-brand-200 hover:shadow-[0_6px_16px_rgba(15,23,42,0.09)] transition-all duration-200">
              <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(145deg, #ECFDF5 0%, #D1FAE5 100%);">
                <?= svg_icon($service['icon'], 'w-4.5 h-4.5 text-brand-700') ?>
              </span>
              <span class="text-xs font-semibold text-gray-800 whitespace-nowrap"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Services Near You: promoted to prime position, always visible -->
    <section class="mt-7 px-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-brand-100 flex items-center justify-center">
            <?= svg_icon('pin', 'w-4 h-4 text-brand-700') ?>
          </span>
          Services Near You
        </h3>
      </div>

      <div class="relative rounded-3xl overflow-hidden ring-1 ring-gray-100 shadow-[0_4px_20px_rgba(15,23,42,0.08)]">
        <?php if (empty($mappedVendors)): ?>
          <div class="relative map-pattern h-56 flex flex-col items-center justify-center text-center px-6">
            <span class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center mb-2 shadow-[0_2px_8px_rgba(15,23,42,0.08)]">
              <?= svg_icon('pin', 'w-5 h-5 text-gray-400') ?>
            </span>
            <p class="text-xs text-gray-500">Map will populate once professionals set their location</p>
          </div>
        <?php else: ?>
          <div id="nearbyMap" class="h-64 w-full"></div>
          <div class="absolute top-3 left-3 z-[400] bg-white/95 backdrop-blur-sm rounded-xl px-3 py-1.5 shadow-[0_2px_10px_rgba(15,23,42,0.1)] flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
            <span class="text-xs font-semibold text-gray-800">
              <?= (int) $nearbyCount ?> professional<?= $nearbyCount === 1 ? '' : 's' ?> nearby
            </span>
          </div>
        <?php endif; ?>
      </div>

      <?php if (empty($mappedVendors)): ?>
        <p class="text-sm text-gray-500 mt-2.5">
          <?php if ($nearbyCount > 0): ?>
            <?= (int) $nearbyCount ?> professional<?= $nearbyCount === 1 ? '' : 's' ?> available near you.
          <?php else: ?>
            0 professionals available near you. Check back soon.
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </section>

    <!-- Top Rated Professionals: swipeable carousel -->
    <section class="mt-8">
      <div class="flex items-center justify-between mb-3 px-5">
        <h3 class="text-base font-bold text-gray-900">Top Rated Professionals</h3>
        <a href="#" class="text-xs font-semibold text-brand-600 flex items-center gap-1 hover:text-brand-700">
          See All <span aria-hidden="true">&rarr;</span>
        </a>
      </div>

      <?php if (empty($topProfessionals)): ?>
        <div class="mx-5 bg-brand-50/60 border border-dashed border-brand-200 rounded-3xl py-10 px-6 flex flex-col items-center text-center">
          <span class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center mb-3 shadow-[0_2px_10px_rgba(15,23,42,0.06)]">
            <?= svg_icon('briefcase', 'w-6 h-6 text-brand-600') ?>
          </span>
          <p class="text-sm font-semibold text-gray-700">No professionals yet.</p>
          <p class="text-sm text-gray-500 mt-0.5">Be the first to book!</p>
        </div>
      <?php else: ?>
        <div class="flex gap-3.5 overflow-x-auto no-scrollbar px-5 pb-1 snap-x snap-mandatory">
          <?php foreach ($topProfessionals as $pro): ?>
            <div class="snap-start shrink-0 w-56 bg-white rounded-2xl shadow-[0_2px_10px_rgba(15,23,42,0.05)] ring-1 ring-gray-100 p-4 hover:shadow-[0_8px_20px_rgba(15,23,42,0.1)] transition-shadow duration-200">
              <div class="flex items-center gap-3 mb-3">
                <div class="ring-2 ring-brand-50 rounded-full shrink-0">
                  <?= initials_avatar($pro['name'], 'w-12 h-12', 'text-sm') ?>
                </div>
                <div class="min-w-0">
                  <p class="font-bold text-gray-900 truncate text-sm"><?= htmlspecialchars($pro['name'], ENT_QUOTES, 'UTF-8') ?></p>
                  <p class="text-[11px] text-gray-500 truncate"><?= htmlspecialchars($pro['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
              </div>
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-xs font-semibold px-1.5 py-0.5 rounded-md">
                  <?= svg_icon('star', 'w-3.5 h-3.5 text-yellow-400') ?>
                  <?= number_format((float) $pro['rating'], 1) ?>
                </span>
                <a href="vendor.php?id=<?= (int) $pro['id'] ?>" class="bg-brand-500 hover:bg-brand-600 active:scale-95 text-white text-xs font-semibold px-3.5 py-2 rounded-xl shadow-[0_4px_12px_rgba(34,197,94,0.35)] transition-all duration-150">
                  View
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</div>

<?php if (!empty($mappedVendors)): ?>
<script>
  const vendors = <?= json_encode(array_map(function ($v) {
      return [
          'id' => (int) $v['id'],
          'name' => $v['name'],
          'service' => $v['service_name'],
          'price' => (float) $v['price'],
          'lat' => (float) $v['latitude'],
          'lng' => (float) $v['longitude'],
      ];
  }, $mappedVendors)) ?>;

  const nearbyMap = L.map('nearbyMap', { zoomControl: false, dragging: true, scrollWheelZoom: false });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(nearbyMap);

  const bounds = L.latLngBounds(vendors.map(v => [v.lat, v.lng]));
  vendors.forEach(v => {
    L.marker([v.lat, v.lng]).addTo(nearbyMap)
      .bindPopup(
        '<strong>' + v.name.replace(/</g, '&lt;') + '</strong><br>' +
        v.service.replace(/</g, '&lt;') + ' &middot; GHC ' + v.price.toFixed(2) + '<br>' +
        '<a href="vendor.php?id=' + v.id + '" style="color:#16A34A;font-weight:600;">View Profile</a>'
      );
  });
  nearbyMap.fitBounds(bounds.pad(0.2));
  if (vendors.length === 1) {
    nearbyMap.setZoom(13);
  }

  // Ensure Leaflet recalculates tile layout once the container is fully laid out/visible.
  setTimeout(() => nearbyMap.invalidateSize(), 200);
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>