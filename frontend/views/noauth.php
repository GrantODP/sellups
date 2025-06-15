<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <base href="/public/">
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Ad listings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

  <?php include('navbar.html'); ?>
  <div class="error-page d-flex align-items-center justify-content-center">
    <div class="error-container text-center p-4">
      <h2 class="display-6 error-message mb-3">Unauthorized</h2>
      <p class="lead error-message mb-5">We can't seem to find the page you're looking for.</p>
      <div class="d-flex justify-content-center gap-3">
        <a href="/browse" class="btn btn-glass px-4 py-2">Return Home</a>
      </div>
    </div>



    <script src="js/navbar.js"></script>
</body>

</html>
