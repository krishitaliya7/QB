<?php include '../includes/header.php'; ?>
<h2>ATM & Branch Locator</h2>
<p>[Placeholder for Interactive Map - e.g., Google Maps API]</p>
<form>
    <div class="mb-3">
        <label for="location" class="form-label">Enter City or Zip Code</label>
        <input type="text" class="form-control" id="location" required>
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
</form>
<div class="mt-3">
    <label>Filter by Type:</label>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="branches">
        <label class="form-check-label" for="branches">Branches</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="atms">
        <label class="form-check-label" for="atms">ATMs</label>
    </div>
</div>
<h3>Locations Found</h3>
<p>No locations found matching your criteria.</p>
<?php include '../includes/footer.php'; ?>