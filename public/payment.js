import { getListing, getOrder, getSessionData, getUrlParams, navigateWindow, pay, Swal } from "./script.js";


async function loadOrder(orderId) {
  try {

    const order = await getOrder(orderId);
    const info = getSessionData(`o${orderId}`);

    if (!order || !info) {
      document.body.innerHTML = `<div class="alert alert-danger">Order not found</div>`;
      return;
    }

    if (info.status === 'paid') {
      document.body.innerHTML = `<div class="alert alert-danger">Order Already paid</div>`;
      return;
    }

    document.getElementById('summary-order-id').textContent = orderId;
    document.getElementById('summary-status').textContent = info.status;
    document.getElementById('summary-total').textContent = info.total_amount;
    document.getElementById('summary-date').textContent = info.created_at || 'N/A';

    const itemContainer = document.getElementById('summary-items');
    for (const item of order.items) {
      const listing = await getListing(item.listing_id);

      const listItem = document.createElement('li');
      listItem.className = 'list-group-item d-flex justify-content-between align-items-center';

      listItem.innerHTML = `
    <div>
      <strong>${listing.title}</strong><br>
      Price: R${item.price} &times; ${item.quantity}
    </div>
    <span>Subtotal: R${item.subtotal}</span>
  `;

      itemContainer.appendChild(listItem);
    }
  } catch (err) {
    document.body.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
  }
}
async function loadPaymentPage(orderId) {
  console.log("LOADING PAYEMNT");
  console.log(orderId);
  if (!orderId || orderId === 0) {
    document.body.innerHTML = `
      <div class="alert alert-danger mt-4">
        <h3>Error</h3>
        <p>Invalid order ID. Please select a valid order to proceed.</p>
      </div>
    `;
    return;
  }

  loadOrder(orderId);

  document.getElementById('payment-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    try {
      const cardNumber = document.getElementById('card-number').value;
      const expiry = document.getElementById('expiry').value;
      const cvv = document.getElementById('cvv').value;

      const info = {
        cardNumber: cardNumber,
        expiry: expiry,
        cvv: cvv
      };

      await pay(orderId, info);

      Swal.fire({
        icon: 'success',
        title: 'Payment success',
      }).then((result) => {
        navigateWindow('user?sec=orders');
      });
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Payment failed',
        text: err.message || 'Unknown error',
      });
    }
  });
}


async function initPage() {
  const order_id = getUrlParams().get('order') ?? 0;
  loadPaymentPage(order_id);
}

await initPage();
