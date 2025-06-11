

document.addEventListener("DOMContentLoaded", async function () {
  const is_dark = true;
  const profile_pic = is_dark ? "profile_light.png" : "profile_dark.png";
  const userLink = document.getElementById('user-link');
  const user = JSON.parse(localStorage.getItem('user'));
  if (user) {
    const img = user.profile_pic || `${profile_pic}`
    const html = `<img src= "/media/${img}" alt = "Profile Picture"
    class=""rounded - circle me - 3" width="32" height="32">
      <span id = "user-link-name" class="align-middle" > ${user.name ?? "User"}</span >`

    userLink.innerHTML = html;

    console.log(user.profile_pic);
  }
  else {
    const html = `<img src = "/media/${profile_pic}" alt = "Profile Picture"
    class=""rounded - circle me - 3" width="32" height="32">
      <span id = user-link-name" class="align-middle" > ${"User"}</span > `

    userLink.innerHTML = html;
  }
});
