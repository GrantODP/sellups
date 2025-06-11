import { checkout, getCart, getListing, getLocalData, storeLocalData, getOrder, getOrders, getSessionData, getUrlParams, getUserInfo, isLoggedIn, navigateWindow, removeCartItem, storeSessionData, updatePassword, updateUserInfo, getResource } from "./script.js";
import Swal from 'https://cdn.jsdelivr.net/npm/sweetalert2@11/+esm';

// Helper function to show a specific section and hide others
function showSection(sectionId) {
  document.querySelectorAll('.content-section').forEach(section => {
    section.classList.add('d-none'); // Hide all sections
  });
  document.getElementById(sectionId).classList.remove('d-none'); // Show the target section
}

async function loadSection(section) {
  if (section === 'user-info') {
    showSection('section-profile');
    await loadUser();
  } else if (section === 'orders') {
    showSection('section-orders');
    await loadOrders();
  } else if (section === 'cart') {
    showSection('section-cart');
    await loadCart();
  } else if (section === 'edit-profile') {
    showSection('section-edit-profile');
    await loadEditProfileForm(); // Only load form data
  } else if (section === 'change-password') {
    showSection('section-change-password');
  }
}

async function loadUser() {
  const profileContainer = document.getElementById('section-profile');
  profileContainer.innerHTML = ''; // Clear previous content

  try {
    const user = getLocalData("user") ?? await getUserInfo();
    console.log(user);
    storeLocalData('user', user);

    const userCard = document.createElement('div');
    userCard.className = 'card p-4 shadow-sm';

    userCard.innerHTML = `
            <h4 class="mb-3">User Profile</h4>
            <div class="mb-2"><strong>Name:</strong> ${user.name}</div>
            <div class="mb-2"><strong>Email:</strong> ${user.email}</div>
            <div class="mb-3"><strong>Contact:</strong> ${user.contact}</div>
            
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" id="edit-profile-btn">Edit Profile</button>
                <button class="btn btn-secondary" id="change-password-btn">Change Password</button>
            </div>
        `;

    profileContainer.appendChild(userCard);

    document.getElementById('edit-profile-btn').addEventListener('click', () => {
      loadSection('edit-profile');
    });

    document.getElementById('change-password-btn').addEventListener('click', () => {
      loadSection('change-password');
    });

  } catch (err) {
    profileContainer.innerHTML = `<div class="alert alert-danger">Failed to load user info</div>`;
  }
}

async function loadOrders() {
  const ordersAccordionContainer = document.getElementById('orders-accordion');
  const noOrdersMessage = document.getElementById('no-orders-message');

  ordersAccordionContainer.innerHTML = '';
  noOrdersMessage.classList.add('d-none');

  const orders = await getOrders();

  if (!orders || orders.length === 0) {
    noOrdersMessage.classList.remove('d-none');
    return;
  }

  const grouped = {};
  orders.forEach(order => {
    if (!grouped[order.status]) grouped[order.status] = [];
    grouped[order.status].push(order);
  });

  const customOrder = { 'pending': 1, 'paid': 2 };
  const sortedStatuses = Object.keys(grouped).sort((a, b) => {
    const orderA = customOrder[a] || 99;
    const orderB = customOrder[b] || 99;
    if (orderA !== orderB) {
      return orderA - orderB;
    }
    return a.localeCompare(b);
  });

  sortedStatuses.forEach((status) => {
    const group = grouped[status];
    const collapseId = `collapse-${status.replace(/\s+/g, '-')}`;

    // Determine badge color based on status
    let statusBadgeClass = '';
    switch (status.toLowerCase()) {
      case 'pending': statusBadgeClass = 'text-bg-warning'; break;
      case 'paid': statusBadgeClass = 'text-bg-success'; break;
      case 'shipped': statusBadgeClass = 'text-bg-info'; break;
      case 'delivered': statusBadgeClass = 'text-bg-primary'; break;
      case 'cancelled': statusBadgeClass = 'text-bg-danger'; break;
      default: statusBadgeClass = 'text-bg-secondary';
    }

    const sectionHtml = `
            <div class="accordion-item shadow-sm">
                <h2 class="accordion-header" id="heading-${collapseId}">
                    <button class="accordion-button bg-light fw-bold text-dark fs-5 py-3 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                        <span class="me-2">${status.charAt(0).toUpperCase() + status.slice(1)} Orders</span> 
                        <span class="badge ${statusBadgeClass} rounded-pill">${group.length}</span>
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse" aria-labelledby="heading-${collapseId}">
                    <div class="accordion-body bg-white py-3 px-3" id="${collapseId}-body">
                        </div>
                </div>
            </div>
        `;
    ordersAccordionContainer.insertAdjacentHTML('beforeend', sectionHtml); // Add the accordion item

    const body = document.getElementById(`${collapseId}-body`); // Get the newly added body
    group.forEach(order => {
      const orderDiv = document.createElement('div');
      orderDiv.className = 'card mb-3 p-3 shadow-sm border';

      orderDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Order ID: <span class="text-primary">#${order.order_id}</span></h5>
                    <span class="badge ${statusBadgeClass} fs-6">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                </div>
                <p class="mb-1"><strong>Total:</strong> R${parseFloat(order.total_amount).toFixed(2)}</p>
                <p class="mb-3 text-muted small">Placed on: ${new Date(order.created_at).toLocaleDateString()} at ${new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>
                
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary view-order-btn" data-id="${order.order_id}">View Details</button>
                    ${order.status.toLowerCase() === 'pending'
          ? `<button class="btn btn-sm btn-success pay-order-btn" data-id="${order.order_id}">Pay Now</button>`
          : ''}
                    ${order.status.toLowerCase() === 'paid' && order.can_be_cancelled // Assuming you have a 'can_be_cancelled' flag
          ? `<button class="btn btn-sm btn-danger cancel-order-btn" data-id="${order.order_id}">Cancel Order</button>`
          : ''}
                </div>
            `;

      body.appendChild(orderDiv);
      storeSessionData(`o${order.order_id}`, order);
    });
  });

  document.querySelectorAll('.view-order-btn').forEach(button => {
    button.addEventListener('click', async function (e) {
      const orderId = parseInt(e.target.dataset.id);
      await loadOrderDetail(orderId);
    });
  });

  document.querySelectorAll('.pay-order-btn').forEach(button => {
    button.addEventListener('click', function (e) {
      const orderId = parseInt(e.target.dataset.id);
      navigateWindow(`pay?order=${orderId}`, true);
    });
  });

  document.querySelectorAll('.cancel-order-btn').forEach(button => {
    button.addEventListener('click', async function (e) {
      const orderId = parseInt(e.target.dataset.id);
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to cancel this order?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No, keep it'
      });

      if (result.isConfirmed) {
        // Implement your API call to cancel the order here
        // For example: await getResource(`cancel-order/${orderId}`, 'POST');
        Swal.fire('Cancelled!', 'Your order has been cancelled.', 'success');
        loadOrders(); // Reload orders to reflect the change
      }
    });
  });
}

export async function loadOrderDetail(orderId) {
  showSection('section-order-detail');
  const container = document.getElementById('section-order-detail');
  container.innerHTML = ''; // Clear previous content

  const order = await getOrder(orderId);
  const order_info = getSessionData(`o${orderId}`);

  if (!order || !order_info) {
    container.innerHTML = '<div class="alert alert-danger">Order not found.</div>';
    return;
  }

  let statusBadgeClass = '';
  switch (order_info.status.toLowerCase()) {
    case 'pending': statusBadgeClass = 'text-bg-warning'; break;
    case 'paid': statusBadgeClass = 'text-bg-success'; break;
    case 'shipped': statusBadgeClass = 'text-bg-info'; break;
    case 'delivered': statusBadgeClass = 'text-bg-primary'; break;
    case 'cancelled': statusBadgeClass = 'text-bg-danger'; break;
    default: statusBadgeClass = 'text-bg-secondary';
  }

  const header = document.createElement('div');
  header.className = 'card p-4 mb-4 shadow-sm';
  header.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Order <span class="text-primary">#${order_info.order_id}</span></h3>
            <span class="badge ${statusBadgeClass} fs-5">${order_info.status.charAt(0).toUpperCase() + order_info.status.slice(1)}</span>
        </div>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Total Amount:</strong> <span class="fs-5 text-success">R${parseFloat(order_info.total_amount).toFixed(2)}</span></p>
                <p class="mb-0 text-muted">Order Placed: ${new Date(order_info.created_at).toLocaleString()}</p>
            </div>
            <div class="col-md-6 text-md-end">
                </div>
        </div>
    `;
  container.appendChild(header);

  const itemListContainer = document.createElement('div');
  itemListContainer.innerHTML = '<h4 class="mb-3">Items in Order</h4>';
  itemListContainer.className = 'mb-4';
  const itemList = document.createElement('div');
  itemList.className = 'row g-3';

  for (const item of order.items) {
    const listing = await getListing(item.listing_id);

    const card = document.createElement('div');
    card.className = 'col-12';
    card.innerHTML = `
            <div class="card p-3 shadow-sm d-flex flex-row align-items-center">
                <div class="flex-grow-1">
                    <h5 class="mb-1">${listing.title}</h5>
                    <p class="mb-1 text-muted">Price: R${parseFloat(item.price).toFixed(2)} x ${item.quantity}</p>
                    <p class="mb-0 fw-bold">Subtotal: R${parseFloat(item.subtotal).toFixed(2)}</p>
                </div>
                <div class="ms-auto">
                    <a href="/ads/${listing.slug}" target="_blank" class="btn btn-sm btn-outline-info">View Listing</a>
                </div>
            </div>
        `;
    itemList.appendChild(card);
  }

  itemListContainer.appendChild(itemList);
  container.appendChild(itemListContainer);


  const actionButtonsDiv = document.createElement('div');
  actionButtonsDiv.className = 'd-flex justify-content-end gap-2 mt-4';

  if (order_info.status.toLowerCase() === 'pending') {
    const payBtn = document.createElement('button');
    payBtn.className = 'btn btn-success btn-lg';
    payBtn.textContent = 'Complete Payment';
    payBtn.onclick = () => navigateWindow(`pay?order=${orderId}`);
    actionButtonsDiv.appendChild(payBtn);
  }

  const backBtn = document.createElement('button');
  backBtn.className = 'btn btn-secondary btn-lg';
  backBtn.textContent = 'Back to All Orders';
  backBtn.onclick = () => loadSection('orders');

  actionButtonsDiv.appendChild(backBtn);
  container.appendChild(actionButtonsDiv);
}

async function loadCart() {
  const cartItemsContainer = document.getElementById('cart-items-container');
  const cartSummaryCheckout = document.getElementById('cart-summary-checkout');
  const emptyCartMessage = document.getElementById('empty-cart-message');
  const cartTotalDisplay = document.getElementById('cart-total-display');
  const checkoutButton = document.getElementById('checkout-btn'); // Get the pre-existing button

  cartItemsContainer.innerHTML = '';
  cartSummaryCheckout.classList.add('d-none');
  emptyCartMessage.classList.add('d-none');
  const cart = await getCart();
  const cartItems = cart.cart_items;

  if (!cartItems || Object.keys(cartItems).length === 0) {
    emptyCartMessage.classList.remove('d-none'); // Show empty message
    return;
  }

  let total = 0;

  for (const [listingId, quantity] of Object.entries(cartItems)) {
    const listing = await getListing(parseInt(listingId));
    const price = parseFloat(listing.price);
    const subtotal = price * quantity;
    total += subtotal;

    const itemCol = document.createElement('div');
    itemCol.className = 'col-12';

    itemCol.innerHTML = `
            <div class="card mb-2 p-3 shadow-sm d-flex flex-row align-items-center">
                <div class="flex-grow-1">
                    <h5 class="mb-1">${listing.title}</h5>
                    <p class="mb-1 text-muted">Price: R${price.toFixed(2)} x ${quantity}</p>
                    <p class="mb-0 fw-bold">Subtotal: R${subtotal.toFixed(2)}</p>
                </div>
                <div class="ms-auto d-flex flex-column gap-2">
                    <button class="btn btn-sm btn-outline-info view-item-btn" data-slug="${listing.slug}">View Item</button>
                    <button class="btn btn-sm btn-outline-danger remove-item-btn" data-id="${listing.listing_id}">Remove</button>
                </div>
            </div>
        `;

    cartItemsContainer.appendChild(itemCol);
    storeSessionData(`c${listing.listing_id}`, { ...listing, quantity });
  }

  cartTotalDisplay.textContent = `R${total.toFixed(2)}`;
  cartSummaryCheckout.classList.remove('d-none');

  // Attach event handlers (ensure they are attached after elements are added)
  document.querySelectorAll('.view-item-btn').forEach(button => {
    button.addEventListener('click', async (e) => {
      const slug = e.target.dataset.slug;
      navigateWindow(`ads/${slug}`);
    });
  });

  document.querySelectorAll('.remove-item-btn').forEach(button => {
    button.addEventListener('click', async (e) => {
      const listingId = parseInt(e.target.dataset.id);
      await removeCartItem(listingId);
      await loadCart(); // Reload cart view
    });
  });

}

async function loadEditProfileForm() {
  const user = await getUserInfo();
  document.getElementById('contact').value = user.contact;


  document.getElementById('change-picture-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
      await getResource("user/profile-pic", "POST", formData);
      await Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Images uploaded successfully!',
        timer: 2000,
        showConfirmButton: false
      });
      loadSection('ads');

    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: err.message,
      });
    }
  });
}



async function logout() {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'This action will log you out.',
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Logout',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;

  try {
    await getResource('logout', 'POST');
    localStorage.clear();
    Swal.fire('Logged out!', '', 'success');
    navigateWindow('browse');;
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
}

function initPage() {
  document.getElementById('btn-profile').onclick = () => loadSection('user-info');
  document.getElementById('btn-orders').onclick = () => loadSection('orders');
  document.getElementById('btn-cart').onclick = () => loadSection('cart');
  document.getElementById('btn-logout').onclick = () => logout();

  document.getElementById('edit-profile-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const updated = {
      contact: document.getElementById('contact').value,
    };
    try {
      await updateUserInfo(updated);
      Swal.fire('Success', 'Profile updated successfully', 'success');
      loadSection('user-info');
    } catch (err) {
      Swal.fire('Error', err.message || 'Failed to update profile', 'error');
    }
  });

  document.getElementById('change-password-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const current = document.getElementById('current-password').value;
    const newPass = document.getElementById('new-password').value;
    const confirm = document.getElementById('confirm-password').value;

    if (newPass !== confirm) {
      Swal.fire('Error', 'New password and Confirm are not the same', 'error');
      return;
    }

    try {
      await updatePassword(current, newPass);
      Swal.fire('Success', 'Password updated successfully', 'success');
      document.getElementById('current-password').value = '';
      document.getElementById('new-password').value = '';
      document.getElementById('confirm-password').value = '';
      loadSection('user-info');
    } catch (err) {
      Swal.fire('Error', err.message || 'Failed to update password', 'error');
    }
  });

  document.getElementById('checkout-btn').addEventListener('click', async () => {
    try {
      await checkout();
      Swal.fire({
        icon: 'success',
        title: 'Order created',
        text: 'Your order has been placed successfully!',
        showConfirmButton: false,
        timer: 2000
      });
      loadCart(); // Reload cart to show it's now empty
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Failed to create order',
        text: err.message || 'An error occurred while processing your order.',
      });
    }
  });


  const sec = getUrlParams().get('sec') ?? 'user-info';
  loadSection(sec);
}

document.addEventListener("DOMContentLoaded", async function () {
  const isIn = await isLoggedIn();
  if (!isIn) {
    navigateWindow('login');
  }
  initPage();
});
