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

  <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <h2 class="m-2">Create New Ad Post</h2>
    <hr class="mb-4">

    <form id="postAdForm">
      <div class="row g-3 m-2">
        <div class="col-12">
          <label for="title" class="form-label">Title</label>
          <input type="text" class="form-control" id="title" name="title"
            placeholder="e.g., BMW S2, Vintage Armchair, etc." required>
          <div class="invalid-feedback">
            Please enter a title for your ad.
          </div>
        </div>

        <div class="col-md-6">
          <label for="category" class="form-label">Category</label>
          <select class="form-select" id="category" name="cat_id" required>
            <option selected disabled value="">Choose...</option>
          </select>
          <div class="invalid-feedback">
            Please select a category.
          </div>
        </div>

        <div class="col-md-6">
          <label for="price" class="form-label">Price (R)</label>
          <div class="input-group">
            <span class="input-group-text">R</span>
            <input type="number" class="form-control" id="price" name="price" placeholder="e.g., 20000" min="0"
              step="any" required>
          </div>
          <div class="invalid-feedback">
            Please enter a valid price.
          </div>
        </div>

        <div class="col-md-6">
          <label for="province" class="form-label">Province</label>
          <input type="text" class="form-control" id="province" name="province" placeholder="e.g., Western Cape"
            required>
          <div class="invalid-feedback">
            Please enter the province.
          </div>
        </div>

        <div class="col-md-6">
          <label for="city" class="form-label">City / Town</label>
          <input type="text" class="form-control" id="city" name="city" placeholder="e.g., Stellenbosch" required>
          <div class="invalid-feedback">
            Please enter the city or town.
          </div>
        </div>

        <div class="col-12">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" id="description" name="description" rows="5"
            placeholder="Provide details about your item or service..." required></textarea>
          <div class="invalid-feedback">
            Please provide a description.
          </div>
        </div>
      </div>


      <button class="btn btn-primary btn-lg m-2" type="submit">Post Ad</button>
      <button class="btn btn-outline-secondary btn-lg m-2" type="reset">Clear Form</button>
    </form>

    <script type="module" src="js/post_ad.js"> </script>
    <script src="js/navbar.js"></script>

  </main>
</body>

</html>
