import { getCategories, isLoggedIn, navigateWindow, postAd, Swal } from '../script.js';


async function populateCategoriesDropdown() {
  const categorySelect = document.getElementById("category");
  if (!categorySelect) {
    console.error("Category select dropdown not found!");
    return;
  }


  try {
    const categories = await getCategories(); // Use your existing function
    if (categories && categories.length > 0) {
      categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.cat_id;
        option.textContent = cat.name;
        categorySelect.appendChild(option);
      });
    } else {
      const noCategoryOption = document.createElement('option');
      noCategoryOption.textContent = "No categories available";
      noCategoryOption.value = "";
      noCategoryOption.disabled = true;
      categorySelect.appendChild(noCategoryOption);
    }
  } catch (error) {
    console.error("Error fetching or populating categories:", error);
    categorySelect.innerHTML = '<option selected disabled value="">Error loading categories</option>';
  }
}

(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})();

const postAdForm = document.getElementById('postAdForm');

if (postAdForm) {
  postAdForm.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!this.checkValidity()) {
      this.classList.add('was-validated');
      return;
    }

    const formData = new FormData(this);
    const jsonData = {};

    formData.forEach((value, key) => {
      jsonData[key] = value;
    });

    postAd(jsonData);

    Swal.fire({
      icon: 'success',
      title: 'Ad posted'
    }).then((r) => {
      navigateWindow('user');
    });
  });
}

async function loadPage() {
  const log_in = await isLoggedIn();
  if (!log_in) {
    navigateWindow('login');
  }
  populateCategoriesDropdown();
}

// Call the function to populate categories when the DOM is ready
document.addEventListener('DOMContentLoaded', async function () {
  await loadPage();
});
