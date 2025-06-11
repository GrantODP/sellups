<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <base href="/public/">
  <title>User Profile</title>
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
      <!-- Sidebar -->
      <nav class="col-12 col-md-3 col-lg-2 bg-dark text-white p-3">
        <h4 class="text-center mb-4">User Panel</h4>
        <ul class="nav nav-pills flex-column w-100 side">
          <li class="nav-item mb-2">
            <button id="btn-profile" type="button"
              class="btn btn-link nav-link text-white text-start p-2 w-100">Profile</button>
          </li>
          <li class="nav-item mb-2">
            <button id="btn-orders" type="button"
              class="btn btn-link nav-link text-white text-start p-2 w-100">Orders</button>
          </li>
          <li class="nav-item mb-2">
            <button id="btn-cart" type="button"
              class="btn btn-link nav-link text-white text-start p-2 w-100">Cart</button>
          </li>
          <li class="nav-item mb-2">
            <a id="btn-seller" href="/seller"
              class="btn btn-link nav-link text-white text-start p-2 w-100">Seller info</a>
          </li>
          <li class="nav-item mb-2">
            <button id="btn-logout" href=""
              class="btn btn-link nav-link text-white text-start p-2 w-100">Logout</button>
          </li>
        </ul>
      </nav>

      <!-- Main Content -->
      <main id="main-content" class="col-12 col-md-9 col-lg-10 px-3 py-4">
        <div id="section-profile" class="content-section d-none">
        </div>

        <div id="section-orders" class="content-section d-none">
          <h3 class="mb-4">My Orders</h3>
          <div id="orders-accordion" class="accordion">
          </div>
          <div id="no-orders-message" class="d-none">
            <div class="alert alert-info text-center mt-4 p-4 shadow-sm">
              <h3>You have no orders at the moment.</h3>
              <p>Start exploring our products!</p>
              <a href="/browse" class="btn btn-primary mt-3">Browse Products</a>
            </div>
          </div>
        </div>

        <div id="section-cart" class="content-section d-none">
          <h3 class="mb-4">Your Shopping Cart</h3>
          <div id="cart-items-container" class="row g-3">
          </div>
          <div id="cart-summary-checkout" class="mt-4 p-3 bg-light border rounded shadow-sm d-flex justify-content-between align-items-center d-none">
            <h4 class="mb-0">Cart Total: <span id="cart-total-display" class="text-success">R0.00</span></h4>
            <button id="checkout-btn" class="btn btn-success btn-lg">
              <i class="bi bi-credit-card me-2"></i> Proceed to Checkout
            </button>
          </div>
          <div id="empty-cart-message" class="d-none">
            <div class="text-center my-5 p-4 shadow-sm alert alert-light">
              <h3 class="mb-3">Your cart is empty 🛒</h3>
              <p class="lead">Looks like you haven't added anything yet. Let's find some great deals!</p>
              <a href="/browse" class="btn btn-primary btn-lg mt-3">Start Shopping Now</a>
            </div>
          </div>
        </div>

        <div id="section-edit-profile" class="content-section d-none">
          <h3>Change Profile Picture</h3>
          <form id="change-picture-form" class="mb-4" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="images" class="form-label">Profile Picture</label>
              <input type="file" id="profile-picture" name="images" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Upload Picture</button>
          </form>

          <h3>Edit Profile</h3>
          <form id="edit-profile-form" class="mb-4">
            <div class="mb-3">
              <label for="contact" class="form-label">Contact</label>
              <input type="tel" id="contact" name="contact" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </div>

        <div id="section-change-password" class="content-section d-none">
          <h3>Change Password</h3>
          <form id="change-password-form">
            <div class="mb-3">
              <label for="current-password" class="form-label">Current Password</label>
              <input type="password" id="current-password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="new-password" class="form-label">New Password</label>
              <input type="password" id="new-password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="confirm-password" class="form-label">Confirm New Password</label>
              <input type="password" id="confirm-password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
          </form>
        </div>

        <div id="section-order-detail" class="content-section d-none">
        </div>

      </main>
    </div>
  </div>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
    crossorigin="anonymous"></script>
  <script type="module" src="user.js"></script>
  <script src="js/navbar.js"></script>
</body>

</html>
