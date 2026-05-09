<?php
require_once './config/db.php';
require_once './includes/functions.php';
include './includes/header.php';
?>

<div style="background:linear-gradient(135deg,var(--dark),var(--primary));padding:55px 0;text-align:center;color:#fff;">
  <div class="container">
    <h1 class="fw-bold mb-2" style="font-size:2rem;" data-lang="nav_about">Hamare Baare Mein</h1>
    <p style="opacity:0.85;font-size:1rem;">Ranchi ka apna local business directory</p>
  </div>
</div>

<div class="container" style="padding:55px 15px;">
  <div class="row g-5 align-items-center mb-5">
    <div class="col-md-6">
      <h2 class="fw-bold mb-3" style="color:var(--dark);">HelloRanchi Kya Hai?</h2>
      <p style="color:#4a5568;line-height:1.8;">HelloRanchi Ranchi ka #1 local business directory hai. Yahan aap Ranchi ke best restaurants, doctors, hotels, schools, gyms, salons aur bahut kuch dhundh sakte ho — bilkul free mein.</p>
      <p style="color:#4a5568;line-height:1.8;">Hamaara mission hai ki Ranchi ke har business ko online laaya jaaye aur local logon ko unki zaroorat ki cheez aasaani se mil sake.</p>
      <div class="d-flex gap-3 mt-4 flex-wrap">
        <div style="text-align:center;padding:16px 24px;background:var(--primary-light);border-radius:12px;border:1.5px solid var(--border);">
          <div style="font-size:1.6rem;font-weight:800;color:var(--primary);">5000+</div>
          <div style="font-size:0.78rem;color:var(--muted);">Listings</div>
        </div>
        <div style="text-align:center;padding:16px 24px;background:var(--primary-light);border-radius:12px;border:1.5px solid var(--border);">
          <div style="font-size:1.6rem;font-weight:800;color:var(--primary);">1 Lakh+</div>
          <div style="font-size:0.78rem;color:var(--muted);">Users</div>
        </div>
        <div style="text-align:center;padding:16px 24px;background:var(--primary-light);border-radius:12px;border:1.5px solid var(--border);">
          <div style="font-size:1.6rem;font-weight:800;color:var(--primary);">30+</div>
          <div style="font-size:0.78rem;color:var(--muted);">Areas</div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div style="background:var(--primary-light);border-radius:20px;padding:36px;border:1.5px solid var(--border);">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">Hum Kya Offer Karte Hain</h5>
        <?php
        $features = [
          ['fas fa-search','Free Business Search','Ranchi ke kisi bhi business ko free mein dhundho'],
          ['fas fa-star','Ratings & Reviews','Real users ke honest reviews padho'],
          ['fab fa-whatsapp','WhatsApp Connect','Directly business owner se WhatsApp pe baat karo'],
          ['fas fa-map-marker-alt','Area-wise Filter','Apne area ke businesses dhundho'],
          ['fas fa-mobile-alt','Mobile Friendly','Phone pe bhi perfectly kaam karta hai'],
          ['fas fa-shield-alt','Verified Listings','Sab listings admin se approve hoti hain'],
        ];
        foreach ($features as [$icon, $title, $desc]):
        ?>
        <div class="d-flex gap-3 mb-3">
          <div style="width:38px;height:38px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="<?= $icon ?>" style="color:#fff;font-size:0.85rem;"></i>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:0.9rem;color:var(--dark);"><?= $title ?></div>
            <div style="font-size:0.78rem;color:var(--muted);"><?= $desc ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Team -->
  <div class="text-center mb-4">
    <h2 class="fw-bold" style="color:var(--dark);">Hamari Team</h2>
    <p style="color:var(--muted);font-size:0.9rem;">Jo log HelloRanchi ko banate hain</p>
  </div>
  <div class="row g-4 justify-content-center">
    <?php
    $team = [
      ['Arjun Singh', 'Founder & CEO', 'A'],
      ['Priya Kumari', 'Marketing Head', 'P'],
      ['Rahul Oraon', 'Tech Lead', 'R'],
    ];
    foreach ($team as [$name, $role, $init]):
    ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="text-center p-4" style="background:#fff;border-radius:16px;border:1.5px solid var(--border);">
        <div style="width:70px;height:70px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.6rem;font-weight:800;color:#fff;"><?= $init ?></div>
        <div class="fw-bold" style="color:var(--dark);"><?= $name ?></div>
        <div style="font-size:0.8rem;color:var(--muted);"><?= $role ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include './includes/footer.php'; ?>
