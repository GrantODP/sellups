
import { Swal, navigateWindow, getSellerListings, getUserSellerInfo, isLoggedIn, getUrlParams, getLocalData, storeLocalData, updateListing, uploadLisingImages, getResource, storeSessionData, getSessionData, deleteListing } from "../script.js";


async function loadSection(section) {
  // Hide all content sections first
  document.querySelectorAll('.content-section').forEach(sec => sec.classList.add('d-none'));

  try {
    await getSellerInfo();
    const seller = getLocalData('seller');

    if (!seller && section !== 'post-ad') { // Allow 'post-ad' even if not a seller
      document.getElementById('main-content').innerHTML = `<p>You are not a seller yet. Please post an ad to become a seller.</p>`;
      return;
    }

    if (section === 'info') {
      document.getElementById('seller-info-section').classList.remove('d-none');
      await loadSellerInfo();
    } else if (section === 'ads') {
      document.getElementById('seller-ads-section').classList.remove('d-none');
      await loadAds();
    } else if (section === 'orders') {
      document.getElementById('seller-orders-section').classList.remove('d-none');
      await loadOrders();
    }
  } catch (error) {
    console.error("Failed to load section:", error);
    document.getElementById('main-content').innerHTML = `<p class="text-danger">Failed to load content. Please try again later.</p>`;
  }
}

async function getSellerInfo() {
  const seller = await getUserSellerInfo();
  storeLocalData('seller', seller);
}

async function loadSellerInfo() {
  const container = document.getElementById('seller-info-section');
  const seller = getLocalData('seller');

  if (!seller) {
    container.innerHTML = `<p>You are not a seller yet. Please post an ad to become a seller.</p>`;
    return;
  }

  container.innerHTML = `
      <div class="seller-info">
        <h3>Seller Profile</h3>
        <p><strong>Name:</strong> ${seller.name}</p>
        <p><strong>Contact:</strong> ${seller.contact}</p>
        <p><strong>Verification status:</strong> ${seller.verification}</p>
        <p><strong>Selling from:</strong> ${new Date(seller.created_at).toLocaleDateString()}</p>
      </div>
    `;
}

async function getSellerOrders(id = 0) {
  if (id) {
    return getResource(`sellers/orders?id=${id}`);
  }
  return getResource('sellers/orders');
}
function getStatusBadgeClass(status) {
  switch (status.toLowerCase()) {
    case 'pending': return 'text-bg-warning'; // Yellow badge
    case 'paid': return 'text-bg-success';    // Green badge
    case 'shipped': return 'text-bg-info';    // Light blue badge
    case 'delivered': return 'text-bg-primary';// Dark blue badge
    case 'cancelled': return 'text-bg-danger'; // Red badge
    default: return 'text-bg-secondary';       // Grey badge for unknown/default
  }
}
async function loadOrders() {
  const ordersAccordionContainer = document.getElementById('ordersAccordion');
  // const noOrdersMessage = document.getElementById('no-orders-message');

  ordersAccordionContainer.innerHTML = ''; // Clear previous orders
  // noOrdersMessage.classList.add('d-none'); // Hide no orders message by default

  const orders = await getSellerOrders();
  console.log(orders);

  if (!orders || orders.length === 0) {
    noOrdersMessage.classList.remove('d-none');
    return;
  }

  // Group orders by status
  const groupedOrders = {};
  orders.forEach(order => {
    if (!groupedOrders[order.status]) {
      groupedOrders[order.status] = [];
    }
    groupedOrders[order.status].push(order);
  });

  console.log("grouped");
  // Define custom order for statuses
  const customOrder = { 'pending': 1, 'paid': 2, 'shipped': 3, 'delivered': 4, 'cancelled': 5 };
  const sortedStatuses = Object.keys(groupedOrders).sort((a, b) => {
    const orderA = customOrder[a] || 99; // Default to a high number for unknown statuses
    const orderB = customOrder[b] || 99;
    if (orderA !== orderB) {
      return orderA - orderB;
    }
    return a.localeCompare(b); // Alphabetical sort for statuses with same custom order
  });

  // Populate the accordion sections
  sortedStatuses.forEach(status => {
    const group = groupedOrders[status];
    const collapseId = `collapse-${status.replace(/\s+/g, '-')}`;
    const statusBadgeClass = getStatusBadgeClass(status); // Reusing existing helper

    const sectionHtml = `
      <div class="accordion-item shadow-sm">
          <h2 class="accordion-header" id="heading-${collapseId}">
              <button class="accordion-button bg-light fw-bold text-dark fs-5 py-3 collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                  <span class="me-2">${status.charAt(0).toUpperCase() + status.slice(1)} Orders</span> 
                  <span class="badge ${statusBadgeClass} rounded-pill">${group.length}</span>
              </button>
          </h2>
          <div id="${collapseId}" class="accordion-collapse collapse" aria-labelledby="heading-${collapseId}" data-bs-parent="#ordersAccordion">
              <div class="accordion-body bg-white py-3 px-3" id="${collapseId}-body">
                  </div>
          </div>
      </div>
    `;
    ordersAccordionContainer.insertAdjacentHTML('beforeend', sectionHtml);

    // Get the newly added body element for this status group
    const body = document.getElementById(`${collapseId}-body`);

    // Iterate through orders in this group and append them
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
          ? `<button class="btn btn-sm btn-success mark-paid-btn" data-id="${order.order_id}">Mark as Paid</button>`
          : ''}
              ${(order.status.toLowerCase() === 'paid' || order.status.toLowerCase() === 'pending') && order.can_be_cancelled
          ? `<button class="btn btn-sm btn-danger cancel-order-btn" data-id="${order.order_id}">Cancel Order</button>`
          : ''}
              ${order.status.toLowerCase() === 'paid'
          ? `<button class="btn btn-sm btn-primary mark-delivered-btn" data-id="${order.order_id}">Mark as Delivered</button>`
          : ''}
          </div>
      `;
      body.appendChild(orderDiv);
      storeSessionData(`order-${order.order_id}`, order); // Store order in session for later retrieval

      // Attach event listeners to the buttons for the current order
      orderDiv.querySelector('.view-order-btn')?.addEventListener('click', () => viewOrderDetails(order.order_id));
      orderDiv.querySelector('.mark-paid-btn')?.addEventListener('click', () => updateOrderStatus(order.order_id, 'paid'));
      orderDiv.querySelector('.cancel-order-btn')?.addEventListener('click', () => updateOrderStatus(order.order_id, 'cancelled'));
      orderDiv.querySelector('.mark-delivered-btn')?.addEventListener('click', () => updateOrderStatus(order.order_id, 'delivered'));
    });
  });
}

// Function to handle updating order status (example, you'll need backend endpoints)
async function updateOrderStatus(orderId, newStatus) {
  try {
    const response = await getResource("sellers/orders", "POST", {
      id: orderId,
      status: newStatus
    })

    await Swal.fire({
      icon: 'success',
      title: 'Success',
      text: `Order #${orderId} status updated to ${newStatus}!`,
      timer: 2000,
      showConfirmButton: false
    });
    loadOrders(); // Reload orders to reflect changes

  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message,
    });
  }
}

// Function to view order details (you can implement a modal or new section for this)
async function viewOrderDetails(orderId) {
  let order = getSessionData(`order-${orderId}`); // Try to get from session (from 'all orders' list)

  // If not found in session, or if the session data is incomplete (e.g., lacks 'items'), fetch the single order
  if (!order || !order.items) {
    try {
      const singleOrderData = await getSellerOrders(orderId); // This fetches the { order_id: 8, items: [...], ... } structure
      if (singleOrderData) {
        // Merge the single order data with any existing data from the 'all orders' list
        // This assumes basic order info (status, total_amount, created_at, buyer_id) is consistent or can be derived.
        order = { ...order, ...singleOrderData }; // Merge existing order with new details
        storeSessionData(`order-${order.order_id}`, order); // Store the merged, more complete order
      }
    } catch (error) {
      console.error("Error fetching single order details:", error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to fetch order details. Please try again.',
      });
      return;
    }
  }

  if (order) {
    // Safely access properties, providing defaults if they might be missing
    const listingTitle = order.listing_title || 'N/A';
    const buyerName = order.buyer_name || 'N/A';
    const buyerContact = order.buyer_contact || 'N/A';
    const status = order.status || 'unknown'; // Ensure status is present
    const createdAt = order.created_at ? new Date(order.created_at).toLocaleString() : 'N/A';
    const totalAmount = order.total_amount ? parseFloat(order.total_amount).toFixed(2) : parseFloat(order.total).toFixed(2); // Use 'total_amount' or 'total'

    let itemsHtml = '<p>No items found for this order.</p>';
    if (order.items && order.items.length > 0) {
      itemsHtml = `
        <table class="table table-bordered table-sm mt-3">
          <thead>
            <tr>
              <th>Listing ID</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            ${order.items.map(item => `
              <tr>
                <td>${item.listing_id}</td>
                <td>${item.quantity}</td>
                <td>R${parseFloat(item.price).toFixed(2)}</td>
                <td>R${parseFloat(item.subtotal).toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }


    Swal.fire({
      title: `Order Details #${order.order_id}`,
      html: `
        <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(status)}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></p>
        <p><strong>Total Amount:</strong> R${totalAmount}</p>
        <p><strong>Placed on:</strong> ${createdAt}</p>
        
        <h5>Items:</h5>
        ${itemsHtml}
      `,
      width: '600px', // Adjust width for table
      showCloseButton: true,
      focusConfirm: false,
    });
  } else {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Order details not found.',
    });
  }
}

async function loadAds() {
  const container = document.getElementById('seller-ads-section');
  container.innerHTML = '';
  const seller = getLocalData('seller');

  if (!seller) {
    container.innerHTML = `<p>You are not a seller yet. Please post an ad to become a seller.</p>`;
    return;
  }
  const ads = await getSellerListings(seller.seller_id);
  if (!ads || ads.length === 0) {
    container.innerHTML = `<p>You have no ads posted yet.</p>`;
    return;
  }

  container.innerHTML = ads.map(ad => `
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">${ad.title}</h5>
          <p class="card-text">${ad.description}</p>
          <p class="card-text">
            <small class="text-muted">Posted on: ${new Date(ad.date_posted).toLocaleDateString()}</small>
          </p>
          <a href="/ads/${ad.slug}" target="_blank" class="btn btn-primary me-2">View</a>
          <button class="btn btn-secondary edit-btn" data-ad-id="${ad.listing_id}">Edit</button>
          <button type="button" class="btn btn-danger delete-btn" data-ad-id="${ad.listing_id}">Delete Listing</button>
        </div>
      </div>
    `).join('');
  document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', () => {
      const id = button.getAttribute('data-ad-id');
      const listing = ads.find(ad => ad.listing_id == id);
      if (listing) {
        loadListingEdit(listing);
      }
    });
  });

  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', async () => {
      const id = button.getAttribute('data-ad-id');
      await deleteSellerListing(id);

    });
  });
}

async function deleteSellerListing(id) {
  try {
    const result = await Swal.fire({
      title: 'Are you sure?',
      text: "This listing will be permanently deleted.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
      await deleteListing(id); // your actual deletion function

      await Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: 'Your listing has been deleted.',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false,
        willClose: () => {
          loadAds(); // reload ads after success alert
        }
      });
    }
  } catch (err) {
    console.error(err);
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: 'Something went wrong while deleting the listing.',
    });
  }
}

function loadListingEdit(listing) {
  const container = document.getElementById('edit-listing-section');
  document.querySelectorAll('.content-section').forEach(sec => sec.classList.add('d-none'));
  container.classList.remove('d-none');

  container.innerHTML = `
    <h2>Edit Listing</h2>

    <form id="edit-listing-form" class="mb-4">
      <input type="hidden" name="listing_id" value="${listing.listing_id}">

      <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title" required value="${listing.title}">
      </div>

      <div class="mb-3">
        <label for="price" class="form-label">Price (ZAR)</label>
        <input type="number" step="0.01" class="form-control" id="price" name="price" required value="${listing.price}">
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="4" required>${listing.description}</textarea>
      </div>

      <button type="submit" class="btn btn-primary">Save Info</button>
    </form>

    <form id="upload-images-form" enctype="multipart/form-data" method="post">
      <input type="hidden" name="listing_id" value="${listing.listing_id}">

      <div class="mb-3">
        <label for="imageInput" class="form-label">Upload Images</label>
        <input class="form-control" id="imageInput" type="file" name="images[]" accept="image/*" multiple>
        <small class="form-text text-muted">You can upload multiple images.</small>
      </div>

      <button type="submit" class="btn btn-secondary">Upload Images</button>
    </form>

    <button type="button" class="btn btn-link mt-3" id="cancel-edit">Cancel</button>
  `;

  document.getElementById('cancel-edit').addEventListener('click', () => {
    loadSection('ads');
  });

  document.getElementById('edit-listing-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
      await updateListing(formData);
      await Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Listing info updated successfully!',
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

  document.getElementById('upload-images-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
      await uploadLisingImages(listing.listing_id, formData);

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


function initPage() {
  document.getElementById('btn-profile').onclick = () => loadSection('info');
  document.getElementById('btn-ads').onclick = () => loadSection('ads');
  document.getElementById('btn-orders').onclick = () => loadSection('orders'); // Added event listener for Orders button

  const sec = getUrlParams().get('sec') ?? 'info';

  loadSection(sec);

}

document.addEventListener("DOMContentLoaded", async function () {
  const isIn = await isLoggedIn();
  if (!isIn) {
    navigateWindow('login');
  }

  initPage();
});
