

const BASE_API = "/api/v1/admin";

function storeSessionData(key, data) {
  sessionStorage.setItem(key, JSON.stringify(data));
}

function getSessionData(key) {
  return JSON.parse(sessionStorage.getItem(key));
}

async function getResource(uri, method = 'GET', data = null, headers = {}, overwrite_url = false) {
  let url = '';
  if (overwrite_url) {
    url = uri;
  } else {

    url = `${BASE_API}/${uri}`;
  }
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

  if (!response.ok) {
    const message = json.message;
    throw new Error(message);
  }

  return json.data;

}

function navigateWindow(page) {
  return window.location.href = `/${page}`;
}

function showSection(id) {
  const sections = document.querySelectorAll('.admin-section');
  sections.forEach(section => {
    section.style.display = section.id === id ? 'block' : 'none';
  });


  if (id = 'categories') loadCats();
}
async function loadCats() {
  const cats = await getResource('/api/v1/categories', 'GET', null, {}, true);
  storeSessionData('cats', cats);
}
async function searchUser() {
  console.log('searching user');
  const container = document.getElementById('user-list');
  const query = document.getElementById('user-search').value.toLowerCase().trim();
  container.innerHTML = '';

  try {
    if (query) {
      const user = await getResource(`users?email=${query}`);
      displayUser(container, user);
      return;
    } else {
      const users = await getResource('users');
      users.forEach(user => {
        displayUser(container, user);
      });
    }
  }
  catch (err) {
    console.error('Failed to load users:', err);
    Swal.fire('Error', `Could not load users: ${err.message}`, 'error');
  }
}


async function searchListing() {
  console.log('searching user');
  const query = document.getElementById('list-search').value.toLowerCase().trim();
  if (query) {
    await displayListings(query);
    return;
  }
}
async function searchSeller() {
  const query = document.getElementById('seller-search').value.toLowerCase().trim();
  if (query) {
    displaySeller(query);
    return;
  }
}
async function displayListings(query) {
  const container = document.getElementById('listing-list');
  container.innerHTML = '';
  try {

    const listings = await getResource(`/api/v1/listings/search?query=${query}`, 'GET', null, {}, true);

    if (!listings) {
      Swal.fire('Not found', "Listing(s) not found", 'info');
      return;
    }

    listings.forEach((ad, index) => {
      const card = document.createElement('div');
      card.className = 'col-md-4';
      card.innerHTML = `
        <div class="card shadow-sm m-2">
          <div class="card-body">
            <h5 class="card-title">${ad.title}</h5>
            <p class="card-text">
              <strong>Id:</strong> ${ad.listing_id}<br>
              <strong>Seller ID:</strong> ${ad.seller_id}<br>
              <strong>Price:</strong> ${ad.price}<br>
              <strong>Location:</strong> ${ad.province}, ${ad.city}<br>
              <strong>Date:</strong> ${ad.date_posted}<br>
            </p>
            <a class="btn btn-success btn-sm" target= '#' href=/ads/${ad.slug}>View</a>
            <button class="btn btn-danger btn-sm" onclick="deleteListing(${ad.listing_id})">Delete</button>
          </div>
        </div>
      `;

      container.appendChild(card);
    });


  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}

async function displayUser(container, user) {
  console.log(user);
  if (user === undefined) {
    return;
  }

  if (!user) {
    Swal.fire('Not found', "User not found", 'info');
    return;
  }


  const card = document.createElement('div');
  card.className = 'col-md-4';
  card.innerHTML = `
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">${user.name}</h5>
            <p class="card-text">
              <strong>Id:</strong> ${user.id}<br>
              <strong>Email:</strong> ${user.email}<br>
              <strong>Contact:</strong> ${user.contact}
            </p>
            <div class="mb-2 d-flex align-items-center gap-2">
              <select class="form-select form-select-sm w-50" id="role-select-${user.id}">
                <option value="" selected disabled>Select role</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
              <button class="btn btn-primary btn-sm" onclick="applyRoleChange(${user.id})">Change Role</button>
            </div>
            <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">Delete</button>
          </div>
        </div>
      `;

  container.appendChild(card);

}



async function deleteUser(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'This action will permanently delete the user.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete user',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;
  try {
    await getResource(`users?id=${id}`, 'DELETE');
    Swal.fire('Deleted!', 'User was successfully deleted.', 'success');
    displayUser(null, undefined);
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}

async function displaySeller(uid = '') {
  const container = document.getElementById('sellers-list');
  container.innerHTML = '';
  if (!uid)
    return;
  try {
    const seller = await getResource(`sellers?uid=${uid}`);
    if (!seller) {
      Swal.fire('Not found', "User not found", 'info');
      return;
    }
    const card = document.createElement('div');
    card.className = 'col-md-4';

    card.innerHTML = `
        <div class="card shadow-sm">
          <div class="card-body">
            <p class="card-text">
              <strong>Seller Id:</strong> ${seller.seller_id}<br>
              <strong>Verification:</strong> ${seller.verification}<br>
              <strong>Start Selling:</strong> ${seller.created_at}
            </p>
          </div>
        </div>
      `;

    container.appendChild(card);

  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}

async function deleteListing(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'This action will permanently delete the listing.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete listing',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;
  try {
    await getResource(`listings?id=${id}`, 'DELETE');
    Swal.fire('Deleted!', 'Listing was successfully deleted.', 'success');
    searchListing();
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}


async function verifySeller() {
  const status = document.getElementById('verify-status').value;
  const sid = document.getElementById('verify-id').value;
  try {
    await getResource('seller/verification', 'POST', { id: sid, status: status });
    Swal.fire('Updated!', `Updated seller to ${status}`, 'success');
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}
async function updatePassword() {
  const uid = document.getElementById('passwordUserId').value;
  const password = document.getElementById('newPassword').value;

  if (!password || !uid) {

    Swal.fire('Empty fields', "Missing fields", 'warn');
    return;
  }
  try {
    await getResource('user/password', 'POST', { id: uid, password: password });
    Swal.fire('Updated!', `Updated password`, 'success');
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}
async function addCategory() {
  const name = document.getElementById('catName').value;
  const descp = document.getElementById('catDescp').value;
  const id = document.getElementById('catId').value;

  if (!name || !descp) {
    Swal.fire('Empty fields', "Missing fields", 'warn');
    return;
  }
  if (id) {
    Swal.fire('Id', "Id must be empty", 'warn');
    return;
  }
  try {
    await getResource('categories', 'POST', { name: name, descp: descp });
    Swal.fire('Updated!', `Added category`, 'success');
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}
async function updateCategory() {
  const name = document.getElementById('catName').value;
  const descp = document.getElementById('catDescp').value;
  const id = document.getElementById('catId').value;

  if (!name || !descp || !id) {
    Swal.fire('Empty fields', "Missing fields", 'warn');
    return;
  }
  try {
    await getResource('categories', 'PUT', { id: id, name: name, descp: descp });
    Swal.fire('Updated!', `Updated category`, 'success');
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }

}
async function fillCategory() {
  const name = document.getElementById('catName');
  const descp = document.getElementById('catDescp');
  const id = document.getElementById('catId').value;

  const categories = getSessionData('cats');

  if (!categories || !Array.isArray(categories)) {
    return;
  }

  const selected = categories.find(cat => String(cat.cat_id) === id);

  if (selected) {
    name.value = selected.name || '';
    descp.value = selected.description || '';
  } else {
    name.value = '';
    descp.value = '';
    console.warn('Category not found for ID:', id);
  }
}

function applyRoleChange(user_id) {
  const select = document.getElementById(`role-select-${user_id}`);
  const selected = select.value;
  console.log('changeRoles');
  if (!selected) {
    Swal.fire('No Role Selected', 'Please select a role before applying changes.', 'warning');
    return;
  }

  changeRole(user_id, selected);
}

async function changeRole(user_id, role) {
  const path = `?id=${user_id}`;

  try {
    if (role === 'admin') {
      await getResource(path, 'POST');
      Swal.fire('Updated!', `Promoted user ${user_id} to admin`, 'success');
    } else if (role === 'user') {
      await getResource(path, 'DELETE');
      Swal.fire('Updated!', `Demoted user ${user_id} to user`, 'success');
    } else {
      Swal.fire('Info', `Unsupported role: ${role}`, 'info');
    }
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}

async function isLoggedIn() {
  const token = await getResource('/api/v1/auth/status', 'GET', null, {}, true);
  return token == 'valid';

}

document.addEventListener("DOMContentLoaded", async function () {
  const isIn = await isLoggedIn();
  if (!isIn) {
    navigateWindow('login');
  }


  document.getElementById('searchSeller').addEventListener('submit', async function (e) {
    e.preventDefault();
    await searchSeller();
  });
  document.getElementById('searchUser').addEventListener('submit', async function (e) {
    e.preventDefault();
    await searchUser();
  });
  document.getElementById('searchListing').addEventListener('submit', async function (e) {
    e.preventDefault();
    await searchListing();
  });
  showSection('users');
});


