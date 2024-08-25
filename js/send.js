/* ===// JavaScript File Structure //===
  1. Variable Declarations
  2. Event Listeners
  3. Custom Input Validation
  4. Helper Functions
  5. Form Submission
  =================================== */

import { fetchCSRFToken } from './csrf.js';

const errorMessage = document.getElementById("error-message");
const urlInput = document.getElementById("url");
const passwordInput = document.getElementById("password");
const usesInput = document.getElementById("uses");
const timeLimitInput = document.getElementById("time-limit");
const toggleAdvancedBtn = document.getElementById("toggle-advanced-btn");
const toggleAdvancedContent = document.getElementById("advancePreMenu");
const advancedSettings = document.getElementById("advanced-settings");
const csrfTokenInput = document.getElementById("csrf-token");
const togglePasswordBtn = document.getElementById("toggle-password-btn");
const linkForm = document.getElementById("link-form");

window.onpageshow = function(event) {
  if (event.persisted) {
    passwordInput.value = '';
  }
};

document.addEventListener('DOMContentLoaded', async function() {
  try {
    const csrfToken = await fetchCSRFToken();
    if (csrfTokenInput) {
      csrfTokenInput.value = csrfToken;
    }
  } catch (error) {
    console.error(error.message);
  }
});

toggleAdvancedContent.addEventListener('click', toggleAdvancedSettings);
togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
usesInput.addEventListener('input', validateUsesInput);
linkForm.addEventListener('submit', submitForm);

// Custom input validation message
urlInput.addEventListener('invalid', function () {
  if (urlInput.validity.valueMissing) {
    urlInput.setCustomValidity('This field cannot be left blank.');
  } else if (urlInput.validity.patternMismatch) {
    urlInput.setCustomValidity('URL should start with http:// or https://');
  }
});

urlInput.addEventListener('input', function () {
  urlInput.setCustomValidity('');
});

usesInput.addEventListener('invalid', function () {
  if (usesInput.validity.valueMissing) {
    usesInput.setCustomValidity('This field cannot be left blank.');
  } else if (usesInput.validity.patternMismatch) {
    usesInput.setCustomValidity('Numbers 0-21 only');
  }
  usesInput.setCustomValidity('');
});

// Helper functions
function displayErrorMessage(message) {
  errorMessage.innerText = message;
  errorMessage.style.display = "block";
}

function clearErrorMessage() {
  errorMessage.innerText = '';
}

function toggleAdvancedSettings() {
  advancedSettings.classList.toggle("collapsed");
  toggleAdvancedBtn.classList.toggle("rotate");
}

function togglePasswordVisibility() {
  const passwordInput = document.getElementById("password");
  const togglePasswordBtn = document.getElementById("toggle-password-btn");
  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    togglePasswordBtn.title = "Hide password";
    togglePasswordBtn.innerText = "👁️‍🗨️";
  } else if (passwordInput.type === "text") {
    passwordInput.type = "password";
    togglePasswordBtn.title = "Show password";
    togglePasswordBtn.innerText = "👁️";
  }
}

function sanitizeInput(input) {
  const cleanedInput = input.trim();
  return cleanedInput;
}

function validateUsesInput() {
  const inputValue = parseInt(usesInput.value.trim(), 10);
  if (usesInput.value.length > 2) {
    usesInput.value = usesInput.value.slice(0, 2);
  }
  usesInput.value = usesInput.value.replace(/[^0-9]/g, '');
  if (isNaN(inputValue) || inputValue < 0 || inputValue > 21 && inputValue !== null) {
    displayErrorMessage("Enter a number between 0 and 21");
    return false;
  }
  clearErrorMessage();
  return true;
}

// Form submission
function submitForm(event) {
  event.preventDefault();
  clearErrorMessage();
  const url = sanitizeInput(urlInput.value);
  const password = sanitizeInput(passwordInput.value);
  const uses = usesInput.value;
  const timeLimit = sanitizeInput(timeLimitInput.value);
  const csrfToken = sanitizeInput(csrfTokenInput.value);

  try {
    new URL(url);
  } catch (error) {
    displayErrorMessage("Invalid URL");
    return false;
  }

  if (password && password.length < 4) {
    displayErrorMessage("Password must be at least 4 characters long");
    return false;
  }

  const formData = new FormData();
  formData.append("url", url);
  formData.append("password", password);
  formData.append("time_limit", timeLimit);
  formData.append("uses", uses);

  const headers = new Headers();
  headers.append("X-CSRF-Token", csrfToken);

  fetch("php/submit.php", {
    method: "POST",
    headers: headers,
    body: formData
  })
  .then(response => {
    /*if (!response.ok) {
      throw new Error(`Error: ${response.statusText}`);
    }*/
    return response.text();
  })
  .then(data => {
    try {
      const parsedData = JSON.parse(data);
      if (parsedData.error) {
        displayErrorMessage(parsedData.error);
      } else if (parsedData.identifier) {
        clearErrorMessage();
        
        const encodedIdentifier = encodeURIComponent(parsedData.identifier);
        window.location.href = `landing.html?id=${encodedIdentifier}&token=${csrfToken}`;
      } else {
        displayErrorMessage("Invalid data");
      }
    } catch (error) {
      console.error('Error parsing JSON:', error);
      console.log(data);
    }
  })
  .catch(error => {
    console.error('There was a problem with the fetch operation: ' + error);
  });

  return false;
}