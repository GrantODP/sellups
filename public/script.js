export class NotfoundError extends Error { };
export class Unauthorized extends Error { };
import Swal from 'https://cdn.jsdelivr.net/npm/sweetalert2@11/+esm';
export { Swal }

export function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

export async function loadTemplates(container, url = 'templates.html') {
  const res = await fetch(url);
  const html = await res.text();
  container.innerHTML = html;
}

export async function getTemplate(url = 'templates.html') {
  const res = await fetch(url);
  const html = await res.text();
  return html;
}

export async function getResource(uri, method = 'GET', data = null, headers = {}) {
  const url = `/api/v1/${uri}`;
  const is_form = data instanceof FormData;
  const options = {
    method: method.toUpperCase(),
    headers: is_form ? headers : {
      'Content-Type': 'application/json',
      ...headers
    }
  };
  if (data && method.toUpperCase() !== 'GET') {
    options.body = is_form ? data : JSON.stringify(data);
  }
  const response = await fetch(url, options);
  const json = await response.json();

  if (response.status == 404) {
    const message = json.message;
    throw new NotfoundError(message);
  }
  if (response.status == 401) {
    const message = json.message;
    throw new Unauthorized(message);
  }
  if (!response.ok) {
    const message = json.message;
    throw new Error(message);
  }

  return json.data;

}
export async function getCategories() {
  return getResource('categories');
}


export async function getAdImagesLinks(id) {
  // returns links
  return await getResource(`listings/media?id=${id}`);
}
export async function getSeller(id) {
  let seller = await getResource(`sellers?id=${id}`);
  let rating = await getSellerRating(seller.seller_id);
  seller.rating = rating;

  return seller;
}

export async function getSingleAd(slug) {

  return getResource(`listings/${slug}`);
}
export async function getSellerRating(id) {
  return getResource(`sellers/rating?id=${id}`);
}
export async function getSingleAdRating(id) {

  return getResource(`listings/rating?id=${id}`);
}

export function renderStars(container, rating, maxStars = 5) {

  container.innerText = '';
  const fullStars = Math.floor(rating);
  const halfStar = rating % 1 >= 0.5;

  for (let i = 0; i < fullStars; i++) {
    container.innerText += '★'; // full star
  }

  if (halfStar) {
    container.innerText += '☆'; // optional: use a special half-star icon if needed
  }

  for (let i = fullStars + halfStar; i < maxStars; i++) {
    container.innerText += '☆'; // empty star
  }
}

export async function getAdReviews(id) {
  return getResource(`listings/reviews?id=${id}`);
}

export function switchImage(img, container_id) {
  const main = document.getElementById(container_id);
  if (main) {
    main.src = img.src;
    storeSessionData('main-image-src', img.src);
  }
}

export function getUrlParams() {
  const queryString = window.location.search;
  return new URLSearchParams(queryString);
}

export function getAdListings(
  category = 0,
  page = 1,
  limit = 5,
  sort = 'date',
  dir = 'desc'
) {
  const listings = getResource(`listings/category?id=${category}&page=${page}&sort=${sort}&limit=${limit}&dir=${dir}`);
  return listings;
}
export function populateProductImages(images) {
  const main_container = document.getElementById('product-image-container');
  const thumbnails_container = document.getElementById('product-image-thumbnails');
  main_container.innerHTML = '';
  if (!images || images.length === 0) return;

  const main_id = 'main-product-image';
  const main = document.createElement('img');
  const src = `/${images[0].path}`;
  storeSessionData('main-image-src', src);

  main.id = main_id;
  main.src = src;
  main.alt = "Main Product Image";
  main.classList = "img-wrapper";
  main.style.cssText = "max-height: 200px; overflow: hidden;";
  main.addEventListener('click', () => {
    Swal.fire({
      imageUrl: getSessionData('main-image-src'),
      imageAlt: 'Zoomed Product Image',
      imageWidth: '100%',
      imageHeight: 'auto',
      showConfirmButton: false,
      background: '#f8f9fa',
      padding: '1em',

    });
  });
  main_container.appendChild(main);

  images.forEach((img, index) => {
    const thumb = document.createElement('img');
    thumb.classList = "img-thumbnail";
    thumb.style.cssText = "width: 60px; height: auto; cursor: pointer;";
    thumb.src = `/${img.path}`;
    thumb.alt = `Thumbnail ${index + 1} `;
    thumb.onclick = () => switchImage(thumb, main_id);
    thumbnails_container.appendChild(thumb);
  });

}
export function renderErrorPage(container, message) {
  container.innerHTML = `
    <section class="error-page" role="alert" aria-live="assertive">
      <div class="error-content">
        <h3 class="error-title">Oops! Something went wrong.</h3>
        <p class="error-message">${message}</p>
        <button class="back-button" onclick="window.history.back()">Go Back</button>
      </div>
    </section>
  `;
}
export function renderStandardMessage(container, message) {
  container.innerHTML = `
    <section class="standard-page" role="alert" aria-live="assertive">
      <div class="standard-content">
        <p class="standard-message">${message}</p>
      </div>
    </section>
  `;
}

export async function searchListing(input) {
  const listings = await getResource(`listings/search?query=${input}`)
  return listings;
}

export function setOnClick(container_id, action) {
  document.getElementById(container_id).addEventListener("click", (e) => action());
}

export function navigateWindow(page, new_tab = false) {
  return new_tab ? window.open(`/${page}`, '_blank') : window.location.href = `/${page}`;
}

export async function login(email, password) {
  const data = {
    email: email,
    password: password
  }
  return getResource('login', 'POST', data);
}
export async function getUserInfo() {
  return getResource('user');
}

export async function addToCart(listing_id, count) {
  const data = {
    "listing_id": listing_id,
    "count": count
  }
  return getResource('user/cart', 'POST', data);
}
export async function reportAd(listing_id, message) {
  const data = {
    "listing_id": listing_id,
    "message": message
  }
  return getResource('user/report', 'POST', data);
}
export function titleCase(str) {
  return str.toLowerCase().split(' ').map(function (word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
  }).join(' ');
}


export function popup(button) {
  button.addEventListener("click", (e) => {
    Swal.fire('Reported!', 'Your report has been submitted.', 'success');
  });
}

export function storeSessionData(key, data) {
  sessionStorage.setItem(key, JSON.stringify(data));
}
export function getSessionData(key) {
  return JSON.parse(sessionStorage.getItem(key));
}
export function storeLocalData(key, data) {
  localStorage.setItem(key, JSON.stringify(data));
}
export function getLocalData(key) {
  return JSON.parse(localStorage.getItem(key));
}
export async function isLoggedIn() {
  const token = await getResource('auth/status');
  return token == 'valid';

}
export async function getPreview(listing_id) {
  try {
    const preview = await getResource(`listings/preview?id=${listing_id}`);
    return preview;
  } catch (err) {
    return null;
  }
}

export async function getOrders() {
  return getResource(`user/orders`);
}

export async function getOrder(id) {

  const order = await getResource(`user/orders?id=${id}`);
  return order;
}
export async function cancelOrder(id) {
  return getResource(`user/orders?id=${id}`, 'DELETE');
}
export async function getListing(id) {
  const listing = await getResource(`listings?id=${id}`);
  return listing;
}


export async function getCart() {
  return getResource('user/cart', 'GET');
}

export async function removeCartItem(listing_id) {
  return getResource(`user/cart?id=${listing_id}`, 'DELETE');
}

export async function checkout() {
  return getResource(`user/cart/checkout`, 'POST');
}

export async function pay(order_id, pay_info) {
  const data = {
    order_id: order_id,
    payment_meta: pay_info,
  }
  return getResource('user/orders/pay', 'POST', data);
}


export async function updateUserInfo(user_info) {
  const r = await getResource('user', 'PUT', user_info);
  storeLocalData('user', await getUserInfo());
  return r;
}

export async function updatePassword(old_pass, new_pass) {
  const data = {
    old_password: old_pass,
    password: new_pass
  };
  return getResource('user/password', 'PUT', data);
}


//Seller
export async function postAd(data) {
  return getResource('listings', 'POST', data);
}

export async function updateListing(data) {
  return getResource('sellers/listings', 'POST', data);
}
export async function deleteListing(listing_id) {
  return getResource(`sellers/listings?id=${listing_id}`, 'DELETE');
}

export async function uploadLisingImages(listing_id, image_forms) {
  console.assert(image_forms instanceof FormData, "images must be of form data");
  return getResource(`listings/media?id=${listing_id}`, 'POST', image_forms);
}

//reviews
export async function writeReview(listing_id, score, message) {
  const data = {
    listing_id: listing_id,
    rating: score,
    message: message,
  };
  return getResource(`listings/reviews`, 'POST', data);
}

export async function editReview(id, rating, message) {
  const data = {
    review_id: id,
    rating: Math.min(rating, 5),
    message: message,
  };
  return getResource('user', 'PUT', data);
}

export async function getLocations() {
  return getResource('location');
}

export async function createAccount(acc_info) {
  return getResource('user/create', 'POST', acc_info);
}
export async function getSellerListings(id) {
  return getResource(`sellers/listings?id=${id}`);
}
export async function getUserSellerInfo() {
  return getResource(`user/seller`);
}

export async function getHasPaidListing(id) {
  return getResource(`user/orders/listings?id=${id}`);
}

export async function getUserReviews(listing_id = "") {
  const param = listing_id ? `?id=${listing_id}` : '';
  return getResource(`user/reviews${param}`);
}


export function toLocalDateStr(date) {
  const utc = date.replace(" ", "T") + "Z";
  const dateObject = new Date(utc);
  return dateObject;
}
