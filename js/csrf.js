export async function fetchCSRFToken() {
  try {
    const response = await fetch("php/token.php", {
      method: 'HEAD',
      credentials: 'include',
      referrerPolicy: "strict-origin-when-cross-origin"
    });
    if (!response.ok) {
      throw new Error(`HTTP error: ${response.status}`);
    }

    const csrfToken = response.headers.get('X-CSRF-Token');
    if (!csrfToken) {
      throw new Error('No CSRF token found in response');
    }

    const tokenRegex = /^[a-fA-F0-9]{64}$/;
    if (!tokenRegex.test(csrfToken)) {
      throw new Error('Invalid CSRF token format');
    }
    
    return csrfToken;
  } catch (error) {
    console.error(error.message);
    return;
  }
}