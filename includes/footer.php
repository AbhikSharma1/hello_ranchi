<footer class="main-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand mb-3">Hello<span>Ranchi</span></div>
        <p data-lang="footer_about" style="font-size:0.85rem;color:rgba(255,255,255,0.6);line-height:1.7;">
          HelloRanchi — Ranchi ka #1 local business directory. Sab kuch ek jagah.
        </p>
        <div class="social-icons mt-3">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-youtube"></i></a>
          <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h6 data-lang="footer_quick">Quick Links</h6>
        <a href="index.php" data-lang="nav_home">Home</a>
        <a href="listings.php" data-lang="nav_listings">Listings</a>
        <a href="categories.php" data-lang="nav_categories">Categories</a>
        <a href="about.php" data-lang="nav_about">About</a>
        <a href="contact.php" data-lang="nav_contact">Contact</a>
      </div>

      <div class="col-lg-3 col-md-6 col-6">
        <h6 data-lang="footer_cats">Top Categories</h6>
        <a href="listings.php?cat=1"><i class="fas fa-utensils me-1" style="color:var(--primary);font-size:0.75rem;"></i> Restaurants</a>
        <a href="listings.php?cat=2"><i class="fas fa-user-md me-1" style="color:var(--primary);font-size:0.75rem;"></i> Doctors</a>
        <a href="listings.php?cat=3"><i class="fas fa-hotel me-1" style="color:var(--primary);font-size:0.75rem;"></i> Hotels</a>
        <a href="listings.php?cat=4"><i class="fas fa-graduation-cap me-1" style="color:var(--primary);font-size:0.75rem;"></i> Schools</a>
        <a href="listings.php?cat=5"><i class="fas fa-dumbbell me-1" style="color:var(--primary);font-size:0.75rem;"></i> Gyms</a>
        <a href="listings.php?cat=6"><i class="fas fa-cut me-1" style="color:var(--primary);font-size:0.75rem;"></i> Salons</a>
      </div>

      <div class="col-lg-3 col-md-6">
        <h6 data-lang="footer_contact">Humse Milo</h6>
        <p style="font-size:0.83rem;color:rgba(255,255,255,0.6);line-height:1.9;margin:0;">
          <i class="fas fa-map-marker-alt me-2" style="color:var(--primary);"></i> Harmu, Ranchi, Jharkhand 834001<br>
          <i class="fas fa-phone me-2" style="color:var(--primary);"></i> +91 90060 42011<br>
          <i class="fas fa-envelope me-2" style="color:var(--primary);"></i> hello@helloranchi.in<br>
          <i class="fas fa-clock me-2" style="color:var(--primary);"></i> Mon–Sat: 9am – 7pm
        </p>
      </div>
    </div>

    <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center">
      <span data-lang="footer_copy">© 2025 HelloRanchi. Sab rights reserved.</span>
      <span>Made with <i class="fas fa-heart" style="color:var(--primary);"></i> in Ranchi</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo (preg_match('#/(admin|auth|public)/#', str_replace('\\','/',$_SERVER['PHP_SELF']))) ? '../' : './'; ?>assets/js/lang.js"></script>
</body>
</html>
