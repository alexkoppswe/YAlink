// Get the link data from the server and display it on the landing page.
window.onload = async function () {
  const urlParams = new URLSearchParams(window.location.search);
  const id = urlParams.has('id') ? encodeURIComponent(urlParams.get('id')) : null;
  const token = urlParams.has('token') ? encodeURIComponent(urlParams.get('token')) : null;

  if (!id || !token) {
    displayErrorMessage("Nothing here");
    return;
  }

  try {
    const response = await fetch("php/get_link.php?id=" + id + "&token=" + token);

    if (response.ok) {
      const landingContainer = document.getElementById("landing-container");
      landingContainer.style.height = "auto";
      landingContainer.style.opacity = 1;
    }

    const data = await response.json();

    if (typeof data !== "object" || data === null) {
      throw new Error("Invalid JSON data");
    }

    if (data.error) {
      displayErrorMessage(data.error);
      return;
    }

    if (response.ok) {
      const domainUrl = window.location.origin;
      document.getElementById("message").innerText = "Link submitted successfully! Your custom URL is: ";
      document.querySelector("#landingLink a").href = "link.html?id=" + id;
      document.querySelector("#landingLink a").innerText = domainUrl + "/link.html?id=" + id;
      document.querySelector("#password-protected .link-value").innerText = data.password ? "Yes" : "No";
      document.querySelector("#time-limit .link-value").innerText = data.time_limit === null ? "Forever" : data.time_limit;
      document.querySelector("#uses .link-value").innerText = data.uses === "-1" ? "Unlimited" : data.uses;

      //updateUrl();
    }
  } catch (error) {
    displayErrorMessage(error.message);
  }
}

// Other functions
document.getElementById("back-button").addEventListener("click", function () {
  window.location.href = "index.html";
});

function displayErrorMessage(message) {
  const errorMessage = document.getElementById("error-message");
  errorMessage.innerText = message;
  errorMessage.style.display = "block";
}

/* NOT USED! - One time link
  Use if you want the link with the token to 'disappear'
  from the url and history after the first visit.
  This is useful for one-time links, add where shown as commented above.

function updateUrl() {
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.delete('token');
  const newUrl = window.location.pathname + '?' + urlParams.toString();
  history.pushState(null, null, newUrl);
}
*/