
import {
  getAdListings,
  getCategories,
  getCookie,
  getPreview,
  getTemplate,
  getUrlParams,
  loadTemplates,
  navigateWindow,
  NotfoundError,
  renderErrorPage,
  renderStandardMessage,
  searchListing,
  titleCase,
}
  from './script.js';

async function renderCategories() {

  const categories = await getCategories();
  const container = document.getElementById("category-list")
  const category_name = getCookie('cat_name') ?? "";
  const name_container = document.getElementById("category-name");
  name_container.innerText = category_name;

  categories.forEach(cat => {

    const button = document.createElement('a');
    button.textContent = cat.name;
    button.className = "btn btn-outline-primary border border-dark  text-start";
    button.href = `/ads?category=${cat.cat_id}`
    button.addEventListener("click", () => {
      updateCatHeader(cat.name);
    })
    container.appendChild(button);

  });
}

async function populateListings(listings) {
  const container = document.getElementById("products-container");
  container.innerHTML = "";
  if (!listings || listings.length === 0) {
    container.innerHTML = `<p>No listings available.</p>`;
    return;
  }
  const template_html = await getTemplate('../frontend/views/ad_article.html');
  const template = document.createElement('div');
  template.innerHTML = template_html.trim();


  const previews = await Promise.all(listings.map(listing =>
    getPreview(listing.listing_id)
      .catch(err => {
        console.error(`Error fetching preview for listing ${listing.listing_id}:`, err);
        return null;  // Return null if error, so rendering still works
      })
      .then(preview => ({
        listing,
        preview
      }))
  ));

  for (const { listing, preview } of previews) {
    const ad_article = template.cloneNode(true);

    if (preview) {
      ad_article.querySelector('img').src = `/${preview.path}`;
    }

    const date = new Date(listing.date_posted);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };

    ad_article.querySelector('a').href = `/ads/${listing.slug}`;
    ad_article.querySelector('.list-title').textContent = listing.title;
    ad_article.querySelector('.list-price').textContent = `R${listing.price}`;
    ad_article.querySelector('.list-description').textContent = listing.description;
    ad_article.querySelector('.list-date').textContent = `Posted on: ${date.toLocaleDateString(undefined, options)}`;
    ad_article.querySelector('.list-location').textContent = `Location: ${titleCase(listing.province)}, ${titleCase(listing.city)}`;

    container.appendChild(ad_article);
  }
}


async function renderListings() {
  const params = getUrlParams();


  const query = params.get('q') ?? '';
  const id = params.get('category') ?? 0;
  const sort_val = params.get('sort') ?? 'date';
  const sort_dir = params.get('dir') ?? 'desc';
  const page = params.get('page') ?? 1;
  const limit = params.get('limit') ?? 10;
  try {

    let listings;
    if (query) {
      listings = await searchListing(query)
    }
    else {

      listings = await getAdListings(id, page, limit, sort_val, sort_dir);
    }
    console.log(listings);
    await populateListings(listings);
  }
  catch (err) {
    const container = document.getElementById("products-container")
    if (err instanceof NotfoundError) {
      renderStandardMessage(container, err.message);
    }
    else {
      renderErrorPage(container, err.message);
    }
  }

}
async function renderPage() {
  initSearch();
  // const container = document.getElementById('page-body');
  // await loadTemplates(container, '../frontend/views/ad_listings_template.html');

  await renderCategories();
  await renderListings();
}

async function initSearch() {
  document.getElementById("search-bar").addEventListener("submit", async function (e) {
    e.preventDefault();
    const form_data = new FormData(this);
    const search = form_data.get("q").trim();
    if (!search) {
      return;
    }

    updateCatHeader(`Search for "${search}`);
    navigateWindow(`ads?q=${search}`);
  });
}
function updateCatHeader(text = '') {
  const name_container = document.getElementById("category-name");
  name_container.innerText = text;
  document.cookie = `cat_name=${text}`;
}

document.addEventListener("DOMContentLoaded", (e) => {
  renderPage();
});
