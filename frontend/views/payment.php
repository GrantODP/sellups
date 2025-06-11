<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <base href="/public/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout & Payment</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>

  <?php include('navbar.html'); ?>
  <div id="payment-page" class="container py-4">
    <h2 class="mb-4">Complete Your Payment</h2>

    <!-- Order Summary Section -->
    <div id="order-summary" class="m-4">
      <h4>Order Summary</h4>
      <p><strong>Order ID:</strong> <span id="summary-order-id"></span></p>
      <p><strong>Status:</strong> <span id="summary-status"></span></p>
      <p><strong>Total:</strong> R<span id="summary-total"></span></p>
      <p><strong>Date:</strong> <span id="summary-date"></span></p>

      <div id="summary-items" class="row g-3 mt-3"></div>
    </div>

    <!-- Payment Section -->
    <div class="card p-4">
      <h4 class="mb-3">Payment Details</h4>
      <form id="payment-form">
        <div class="mb-3">
          <label for="card-number" class="form-label">Card Number</label>
          <input type="text" class="form-control" id="card-number" placeholder="1234 5678 9012 3456" required>
        </div>
        <div class="mb-3">
          <label for="expiry" class="form-label">Expiry Date</label>
          <input type="text" class="form-control" id="expiry" placeholder="MM/YY" required>
        </div>
        <div class="mb-3">
          <label for="cvv" class="form-label">CVV</label>
          <input type="text" class="form-control" id="cvv" placeholder="123" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Pay Now</button>
      </form>
    </div>
  </div>

  <script type="module" src="payment.js"></script>
  <script src="js/navbar.js"></script>
</body>

</html>
