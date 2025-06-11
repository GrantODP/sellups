import {
  createAccount,
  navigateWindow
} from '../script.js';

async function setCreateAccount() {
  document.getElementById('account-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    try {
      const formData = new FormData(this);
      const data = Object.fromEntries(formData.entries());
      await createAccount(data);
      navigateWindow('login')
    } catch (err) {
      console.log(err);
      document.getElementById('message').innerText = err.message;
    }
  });

}
async function loadPage() {
  setCreateAccount();
}

addEventListener('DOMContentLoaded', async function () {
  await loadPage();
})
