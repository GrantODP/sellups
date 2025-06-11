import {
  toLocalDateStr,
  renderErrorPage,
  getAdImagesLinks,
  getAdReviews,
  getCookie,
  getSeller,
  getSingleAd,
  getSingleAdRating,
  loadTemplates,
  populateProductImages,
  renderStars,
  NotfoundError,
  renderStandardMessage,
  addToCart,
  storeSessionData,
  getSessionData,
  isLoggedIn,
  reportAd,
  getUserReviews,
  getHasPaidListing,
  writeReview,
  navigateWindow,
} from './script.js';

import { marked } from 'https://cdn.jsdelivr.net/npm/marked@5.1.0/lib/marked.esm.js';
import Swal from 'https://cdn.jsdelivr.net/npm/sweetalert2@11/+esm';
const slug = getCookie("ad_slug");



async function eval_product(id) {
  console.log("Evaluating");
  const container = document.getElementById('product-data');
  let button = container.querySelector('#eval_btn');
  button.innerText = 'evaluating';
  button.disabled = true;
  const eval_ad = await fetch(`/api/v1/listings/evaluate?id=${id}`)
    .then(response => {
      if (!response.ok) throw new Error('Failed to evaluate product');
      return response.json();
    });
  console.log(eval_ad);
  container.querySelector('#eval-body').innerHTML = marked.parse(eval_ad.data);

  button.disabled = false;
  button.innerText = 'Evaluate';
}


async function renderAdScore(id) {
  const container = document.getElementById('product-data');
  try {
    const score = await getSingleAdRating(id);
    console.log(score);
    const star_body = container.querySelector('#ascore-body');
    renderStars(star_body, score.rating);
  } catch (err) {
    console.log(err);
    renderErrorPage(container, err.message);
  }
}
async function renderSeller(id) {
  const container = document.getElementById('seller-info');
  try {
    const seller = await getSeller(id);
    console.log(seller);
    container.querySelector('#sel-name').innerText = seller.name;
    container.querySelector('#rate-count').innerText = seller.rating.count + " reviews";
    container.querySelector('#contact').innerText = "Phone number: " + seller.contact;
    container.querySelector('#verification').innerText = "Verification: " + seller.verification;

    const rate_body = container.querySelector('#rate-body');
    renderStars(rate_body, seller.rating.rating);
  } catch (err) {

    renderErrorPage(container, err.message);
  }
}

function renderSingleReview(review_data, container) {
  const review = document.createElement('div');
  review.innerHTML = `
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <h6 class="mb-1">${review_data.name}</h6>
          <small class="text-muted">${review_data.created_at}</small>
        </div>
        <div>
          <span class="badge bg-warning text-dark fs-6">${review_data.rating}⭐</span>
        </div>
      </div>
      <p class="mb-0">${review_data.message}</p>
    </div>
  </div>
`;

  container.appendChild(review);

}
function showUserReview(review) {
  const container = document.getElementById('user-review');
  container.classList.replace('d-none', 'd-block');

  document.getElementById('review-rating').textContent = `${review.score}`;
  document.getElementById('review-message').textContent = review.message;
  document.getElementById('review-date').textContent = review.created_at;
  const edit = document.getElementById('review-edit-btn');


  window.currentReview = review;
  edit.addEventListener('click', (e) => {
    document.getElementById('review-write-h').textContent = "Edit Review";
    showWriteReview(review);
  })
}

function showWriteReview(review = {}) {
  const ad = getSessionData('ad');
  const formContainer = document.getElementById('write-review');
  formContainer.classList.replace('d-none', 'd-block');

  const rating = formContainer.querySelector('#rating');
  const message = formContainer.querySelector('#message');
  rating.value = review.score || 1;
  message.value = review.message || "";

  formContainer.addEventListener('submit', async function (e) {
    e.preventDefault()
    try {
      await writeReview(ad.listing_id, rating.value, message.value);
      Swal.fire({
        title: 'Success',
        text: 'Your review has been submitted.',
        icon: 'success',
        confirmButtonText: 'OK'
      }).then(async function () {
        formContainer.classList.replace('d-block', 'd-none');
        await renderUpdatableInfo(ad);
      });

    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: err.message,
      });
    }
  });
}


async function loadUserReviews(listing_id) {
  try {
    const review = await getUserReviews(listing_id);

    if (review) {
      return showUserReview(review);
    }
    console.log("No reviews");
    const paid_order = await getHasPaidListing(listing_id);

    if (paid_order) {
      showWriteReview();
    }
  } catch (err) {
    console.log(err);

  }
}


async function renderReviews(id) {
  try {

    await loadUserReviews(id);
    const reviews = await getAdReviews(id);
    const container = document.getElementById('reviews-container');
    container.innerHTML = '';
    reviews.forEach(element => {
      renderSingleReview(element, container);
    });
  } catch (err) {
    const container = document.getElementById('reviews-container');
    if (err instanceof NotfoundError) {
      renderStandardMessage(container, "No reviews");
      return;
    }
    renderErrorPage(container, err.message);
  }
}
async function renderAdImages(id) {
  try {
    const images = await getAdImagesLinks(id);
    console.log(images);
    populateProductImages(images);
  } catch (err) {
    const container = document.getElementById('product-image-container');
    if (err instanceof NotfoundError) {
      renderStandardMessage(container, "No images");
      return;
    }
    renderErrorPage(container, err.message);
  }
}
async function setReportAd() {

  const logged_in = await isLoggedIn();
  if (!logged_in) {
    Swal.fire({
      icon: 'info',
      title: 'Must log in first'
    });
    return
  }
  const { value: message } = await Swal.fire(
    {
      icon: "question",
      input: "textarea",
      inputPlaceholder: "Why are you reporting?",
      showCancelButton: true
    }
  );

  const listing = getSessionData('ad').listing_id;
  if (message && listing) {
    try {

      await reportAd(listing, message);
      Swal.fire({
        icon: 'success',
        title: 'Submitted report'
      });
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Error reporting ad'
      });
    }
  }

}
async function setAddToCart() {

  const logged_in = await isLoggedIn();

  if (!logged_in) {
    Swal.fire({
      icon: 'info',
      title: 'Must log in first'
    });
    return
  }
  const { value: count } = await Swal.fire(
    {
      title: "Select how many to add to cart",
      input: "select",
      inputOptions: {
        1: '1',
        2: '2',
        3: '3',
        4: '4',
        5: '5',
        6: '6',
        7: '7',
        8: '8',
        9: '9',
        10: '10'
      },
    }
  );

  const listing = getSessionData('ad').listing_id;
  if (count && listing) {
    try {
      await addToCart(listing, count);

      Swal.fire({
        icon: 'success',
        title: 'Added to cart'
      });
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Error adding to cart'
      });
    }
  }

}
async function renderUpdatableInfo(ad) {
  await renderAdScore(ad.listing_id);
  await renderSeller(ad.seller_id);
  await renderReviews(ad.listing_id);

}
async function renderAd(slug) {
  const container = document.getElementById('page-body');
  const ad = await getSingleAd(slug);
  storeSessionData('ad', ad);
  document.title = ad.title;
  await loadTemplates(container, '../frontend/views/ad_tempalte.html');

  container.querySelector('#ptitle').innerText = document.title;
  container.querySelector('#date-post').innerText = `Posted: ${toLocalDateStr(ad.date).toLocaleDateString()}`;
  container.querySelector('#ad-descp-body').innerText = ad.description;
  container.querySelector('#price').innerText = "R" + ad.price;
  container.querySelector('#eval_btn').onclick = () => { eval_product(ad.listing_id) };
  container.querySelector('#add-cart-btn').onclick = setAddToCart;
  container.querySelector('#report-ad').onclick = setReportAd;

  await renderUpdatableInfo(ad);
  await renderAdImages(ad.listing_id);






}
await renderAd(slug);
