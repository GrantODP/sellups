<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="/public/">
  <title>Ad listings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<style>
  .lift-up {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .lift-up:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  }

  @media (max-width: 575.98px) {
    .mobile-search-input {
      margin-bottom: 0.5rem;
    }
  }
</style>

<body class="bg-light">

  <?php include('navbar.html'); ?>

  <form class="row g-2 my-3 mx-auto px-3" role="search" id="search-bar" style="max-width: 960px;">
    <div class="col-12 col-md-11"> <input class="form-control border border-dark mobile-search-input" type="search" name="q" placeholder="Search ads...">
    </div>
    <div class="col-12 col-md-1"> <button class="btn btn-success w-100" type="submit">Search</button>
    </div>
  </form>

  <div class="container my-5">
    <div class="card text-center">
      <div class="card-body" id="page-body">
        <div class="container-fluid mt-3">
          <div id="products-page-container" class="row">

            <nav id="category-container" class="col-12 col-md-3 mb-4 order-md-1 order-2">
              <div class="bg-light rounded p-3 w-100 h-auto">
                <h5 class="mb-3 fw-bold text-start">Categories</h5>
                <ul class="list-group btn-group-vertical w-100" id="category-list">
                </ul>
              </div>
            </nav>

            <div class="col-12 col-md-9 mt-0 order-md-2 order-1">
              <div id="category-name" class="mb-3 fw-bold text-start"></div>
              <div id="products-container" class="row"> </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include('footer.html'); ?>
  <script type="module" src="all.js"></script>
  <script src="js/navbar.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
</body>

</html>
