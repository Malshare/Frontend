<?php
// Soft 404 page — returns HTTP 200 but indicates a not-found resource to users
http_response_code(200);
header('X-Soft-404: 1');
header('X-Robots-Tag: noindex, follow');

// Include standard site header/footer for consistent look
if (file_exists(dirname(__FILE__) . '/header.php')) {
    include dirname(__FILE__) . '/header.php';
}
if (file_exists(dirname(__FILE__) . '/nav.php')) {
    include dirname(__FILE__) . '/nav.php';
}
?>
<!DOCTYPE html>
<div class="container" style="margin-top: 30px;">
  <div class="page-header">
    <h1>Page Not Found</h1>
  </div>

  <p>Sorry — the page you requested could not be found. Try one of the options below or run a search.</p>

  <ul>
    <li><a href="index.php">Homepage</a></li>
    <li><a href="search.php">Search samples</a></li>
    <li><a href="upload.php">Upload a sample</a></li>
    <li><a href="sampleshare.php">Browse recent samples</a></li>
  </ul>

  <form action="search.php" method="get" class="form-inline" style="margin-top: 15px;">
    <div class="form-group">
      <label for="query" class="sr-only">Search</label>
      <input type="text" name="query" id="query" class="form-control" placeholder="Search by hash or term" />
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>

  <hr />
  <p>If you believe this is an error, please <a href="mailto:admin@malshare.com">contact the site administrator</a>.</p>
</div>

<?php
if (file_exists(dirname(__FILE__) . '/footer.php')) {
    include dirname(__FILE__) . '/footer.php';
}

// End of soft_404.php
