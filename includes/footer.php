    <!-- Footer -->
    <footer class="bg-light border-top mt-auto py-3" style="max-width: 100vw; overflow-x: hidden; box-sizing: border-box;">
      <div class="container-fluid" style="max-width: 100vw; overflow-x: hidden; box-sizing: border-box;">
        <div class="row" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden; box-sizing: border-box;">
          <div class="col-12 text-center" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden; box-sizing: border-box;">
            <h5><strong class="text-success">AgriSea</strong> Marketplace</h5>
            <p class="text-muted small mb-2">Empowering Filipino farmers and fishers by connecting them directly with conscious consumers.</p>
          </div>
        </div>
        <div class="row" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden; box-sizing: border-box;">
          <div class="col-md-12 text-center" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden; box-sizing: border-box;">
            <small class="text-muted">© 2025 AgriSea Marketplace. All rights reserved.</small>
          </div>
        </div>
      </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($additional_js)): ?>
      <?php foreach ($additional_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
      <?php endforeach; ?>
    <?php endif; ?>
  </body>
</html>