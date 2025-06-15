<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <base href="/public/">
  <title>Seller Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
</head>
<style>
  .side .nav-link:hover {
    background-color: grey;
  }
</style>

<body>
  <?php include('navbar.html'); ?>

  <div class="container-fluid">

    <div class="row flex-column flex-md-row min-vh-100">
      <nav class="col-12 col-md-3 col-lg-2 bg-dark text-white p-3">
        <h4 class="text-center mb-4">Seller Panel</h4>
        <ul class="nav nav-pills flex-column w-100 side">
          <li class="nav-item mb-2">
            <button id="btn-profile" type="button"
              class="btn btn-link nav-link text-white text-start p-2 w-100">
              Profile
            </button>
          </li>
          <li class="nav-item mb-2">
            <button id="btn-ads" type="button"
              class="btn btn-link nav-link text-white text-start p-2 w-100">
              Listed Ads
            </button>
          </li>
          <li class="nav-item mb-2">
            <button id="btn-orders" type="button"
              class="btn btn-link nav-link text-white text-start p-2 w-100">Orders</button>
          </li>
          <li class="nav-item mb-2">
            <a id="btn-post-ad" href="/post-ad" target="_blank"
              class="btn btn-link nav-link text-white text-start p-2 w-100">
              Post Ad
            </a>
          </li>
        </ul>
      </nav>

      <main id="main-content" class="col-12 col-md-9 col-lg-10 px-3 py-4">
        <div id="seller-info-section" class="content-section">
        </div>

        <div id="seller-ads-section" class="content-section d-none">
        </div>

        <div id="seller-orders-section" class="content-section d-none">
          <div class="accordion" id="ordersAccordion">
            <p id="no-orders-message" class="text-muted text-center d-none">You have no orders yet.</p>
          </div>
        </div>

        <div id="edit-listing-section" class="content-section d-none">
        </div>


      </main>
    </div>
  </div>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
    crossorigin="anonymous"></script>
  <script type="module" src="js/seller.js"></script>
  <script src="js/navbar.js"></script>
</body>

</html>
