import {
  getUserInfo,
  isLoggedIn,
  login,
  navigateWindow,
  storeLocalData,
} from './script.js';




async function userLogin() {
  console.log("login page");
  document.getElementById('login-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    try {
      const formData = new FormData(this);
      const data = Object.fromEntries(formData.entries());
      await login(data.email, data.password);
      await storeUserInfo();
      navigateWindow('ads')
    } catch (err) {
      console.log(err);
      document.getElementById('message').innerText = err.message;
    }
  });

}
async function storeUserInfo() {
  try {
    const user = await getUserInfo();
    console.log(user);
    storeLocalData('user', user);
  } catch (err) {
    console.log("Failed getting user info");
  }
}

const isIn = await isLoggedIn();
if (isIn) {
  navigateWindow('user');
}
await userLogin();


