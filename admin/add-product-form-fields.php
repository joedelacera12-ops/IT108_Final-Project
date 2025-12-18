<!-- Basic Product Information -->
<div class="mb-3">
  <label for="productName" class="form-label">Product Name *</label>
  <input type="text" class="form-control" id="productName" name="productName" placeholder="e.g., Fresh Organic Tomatoes" required>
</div>

<div class="row mb-3">
  <div class="col-md-6">
    <label for="productType" class="form-label">Product Type *</label>
    <select class="form-select" id="productType" name="productType" required>
      <option value="">Select Product Type</option>
      <option value="farmer">Farm Product</option>
      <option value="fisher">Sea Product</option>
    </select>
  </div>
  <div class="col-md-6" id="categoryField">
    <label for="productCategory" class="form-label">Category *</label>
    <select class="form-select" id="productCategory" name="productCategory" required>
      <option value="">Select Product Type First</option>
    </select>
    <!-- Hidden field to store the auto-assigned category -->
    <input type="hidden" id="autoCategory" name="autoCategory" value="">
  </div>
</div>

<!-- Seller Information -->
<div class="row mb-3">
  <div class="col-md-6">
    <label for="sellerEmail" class="form-label">Seller Email *</label>
    <input type="email" class="form-control" id="sellerEmail" name="sellerEmail" readonly>
  </div>
  <div class="col-md-6">
    <label for="sellerPhone" class="form-label">Seller Phone *</label>
    <input type="text" class="form-control" id="sellerPhone" name="sellerPhone" readonly>
  </div>
</div>

<div class="mb-3">
  <label for="productDescription" class="form-label">Product Description *</label>
  <textarea class="form-control" id="productDescription" name="productDescription" rows="3" placeholder="Describe your product, including quality, freshness, growing methods, etc." required></textarea>
</div>

<!-- Pricing and Quantity -->
<div class="row mb-3">
  <div class="col-md-4">
    <label for="price" class="form-label">Price per Unit (₱) *</label>
    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
  </div>
  <div class="col-md-4">
    <label for="unit" class="form-label">Unit of Measurement *</label>
    <select class="form-select" id="unit" name="unit" required>
      <option value="">Select Unit</option>
      <option value="kg">Kilogram (kg)</option>
      <option value="g">Gram (g)</option>
      <option value="lb">Pound (lb)</option>
      <option value="piece">Piece</option>
      <option value="dozen">Dozen</option>
      <option value="bunch">Bunch</option>
      <option value="bag">Bag</option>
      <option value="box">Box</option>
      <option value="crate">Crate</option>
    </select>
  </div>
  <div class="col-md-4">
    <label for="stock" class="form-label">Available Stock *</label>
    <input type="number" class="form-control" id="stock" name="stock" min="0" required>
  </div>
</div>

<!-- Product Images -->
<div class="mb-3">
  <label for="productImages" class="form-label">Upload Product Images</label>
  <input type="file" class="form-control" id="productImages" name="productImages[]" multiple accept="image/*">
  <div class="form-text">Upload up to 5 images. First image will be the main product image.</div>
  <div id="imagePreview" class="row g-2 mt-2"></div>
</div>

<!-- Quality and Certifications -->
<div class="row mb-3">
  <div class="col-md-6">
    <label for="organic" class="form-label">Organic Certified</label>
    <select class="form-select" id="organic" name="organic">
      <option value="0">No</option>
      <option value="1">Yes</option>
    </select>
  </div>
  <div class="col-md-6">
    <label for="fresh" class="form-label">Fresh Product</label>
    <select class="form-select" id="fresh" name="fresh">
      <option value="1">Yes</option>
      <option value="0">No</option>
    </select>
  </div>
</div>

<!-- Terms and Conditions -->
<div class="mb-3 form-check">
  <input class="form-check-input" type="checkbox" id="productTerms" name="productTerms" required>
  <label class="form-check-label" for="productTerms">
    I confirm that all information provided is accurate and I agree to the <a href="#" class="text-success" onclick="showSellerTerms(); return false;">Seller Terms</a> and <a href="#" class="text-success" onclick="showMarketplaceGuidelines(); return false;">Marketplace Guidelines</a> *
  </label>
</div>

<!-- Hidden field for product status -->
<input type="hidden" id="productStatus" name="productStatus" value="active">