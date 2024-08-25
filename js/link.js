/* ===// JavaScript File Structure //===
  1. Constants and Variables
  2. Event Handlers
  3. Helper Functions
  4. Main Functions
  5. Form submission
  =================================== */

import { fetchCSRFToken } from './csrf.js';

const linkContainer = document.getElementById("link-container");
const passwordForm = document.getElementById("passwordForm");
const passwordInput = document.querySelector("#passwordForm input[name='password']");

window.onpageshow = function(event) {
  if (event.persisted) {
    passwordInput.value = '';
  }
};

// Get the identifier from the URL
window.onload = async function () {
  const urlParams = new URLSearchParams(window.location.search);
  const id = urlParams.has('id') ? encodeURIComponent(urlParams.get('id')) : null;

  if (!id) {
    displayErrorMessage("Nothing here");
    return;
  }

  if(!sessionStorage.getItem('unlocked')) {
    sessionStorage.clear();
  }

  try {
    const response = await fetch("php/link.php?id=" + id);
    const data = await response.text();
    const jsonData = JSON.parse(data);

    if (typeof jsonData !== "object" || jsonData === null) {
      throw new Error("Invalid JSON data");
    }

    if (jsonData.error) {
      displayErrorMessage(jsonData.error);
      return;
    }

    if (response.ok) {
      if (jsonData.password === true && (sessionStorage.getItem('unlocked') !== 'true' && !jsonData.unlocked)) {
        showPasswordForm(id);
        return;
      }

      if (jsonData.password === false || (sessionStorage.getItem('unlocked') === 'true' || jsonData.unlocked)) {
        showLinkContent(jsonData);
        return;
      }
    }
  } catch (error) {
    if (error.name === 'SyntaxError') {
      displayErrorMessage("Error parsing JSON data");
    } else if (error.name === 'TypeError') {
      displayErrorMessage("Network error: " + error.message);
    } else {
      displayErrorMessage(error.message);
    }
  }
};

passwordInput.addEventListener('invalid', function () {
  if (passwordInput.validity.valueMissing) {
    passwordInput.setCustomValidity('This field cannot be left blank.');
  }
  passwordInput.setCustomValidity('');
});

document.getElementById("back-button").addEventListener("click", function () {
  window.location.href = "index.html";
});

// Helper functions
function displayErrorMessage(message) {
  const errorMessage = document.getElementById("error-message");
  errorMessage.innerText = message;
  errorMessage.style.display = "block";
  return;
}

function showPasswordForm(id) {
  passwordForm.style.height = "auto";
  passwordForm.style.opacity = 1;

  document.getElementById("message").innerText = "Please enter the password to access the link";
  document.getElementById("link-id").value = id;
}

// Show the link content
function showLinkContent(data) {
  if (data.error) {
    displayErrorMessage(data.error);
    return;
  }

  if(data.time_limit !== "Expired" && data.uses !== "0") {
    linkContainer.style.height = "auto";
    linkContainer.style.opacity = 1;
    
    document.getElementById("message").innerText = "Here is your link";
    document.querySelector("#link a").href = data.url;
    document.querySelector("#link a").innerText = data.url;
    document.querySelector("#time-limit .link-value").innerText = data.time_limit === null ? "Forever" : data.time_limit;
    document.querySelector("#uses .link-value").innerText = data.uses === "-1" ? "Unlimited" : data.uses;
  }
}

// Form submission for password
passwordForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  const password = passwordInput.value.trim();
  const csrfToken = await fetchCSRFToken();

  if (password && password.length < 4) {
    displayErrorMessage("Password must be at least 4 characters long");
    return false;
  }

  const formData = new FormData();
  formData.append("id", passwordForm.querySelector("input[name='id']").value);
  formData.append("password", password);
  formData.append("csrf_token", csrfToken);

  try {
    const response = await fetch("php/link.php", { method: "POST", body: formData });
    const data = await response.text();
    const jsonData = JSON.parse(data);

    passwordInput.value = '';

    if (jsonData.error) {
      displayErrorMessage(jsonData.error);
      return;
    }

    if (response.ok && jsonData.unlocked) {
      sessionStorage.setItem('unlocked', 'true');

      displayErrorMessage('');
      passwordForm.style.height = "0";
      passwordForm.style.opacity = 0;

      window.history.pushState(null, '', window.location.href);
      
      showLinkContent(jsonData);
    }
  } catch (error) {
    if (error.name === 'SyntaxError') {
      displayErrorMessage("Error parsing JSON data");
    } else if (error.name === 'TypeError') {
      displayErrorMessage("Network error: " + error.message);
    } else {
      displayErrorMessage(error.message);
    }
  }
});