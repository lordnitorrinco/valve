/**
 * API communication layer — handles CSRF tokens and form submission.
 *
 * Flow:
 *  1. Fetch a CSRF token from GET /api/csrf-token (cached in state)
 *  2. On submit, build a FormData payload with all fields + CV file
 *  3. Send POST /api/submit with the CSRF token in a custom header
 *  4. Handle killer logic (education/english level disqualification)
 *  5. Navigate to success/killer/error result views
 */

import { state, tracking } from '../framework/store.js';
import { KILLER_EDUCATION, KILLER_ENGLISH } from '../data/options.js';
import { goTo } from '../framework/router.js';

/**
 * Fetch a CSRF token from the backend.
 * Returns the cached token if one already exists.
 *
 * @returns {Promise<string>} The CSRF token string
 */
async function fetchCsrfToken() {
  if (state.csrfToken) return state.csrfToken;
  try {
    const res = await fetch('/api/csrf-token');
    const data = await res.json();
    state.csrfToken = data.token;
    return data.token;
  } catch {
    return '';
  }
}

/**
 * Submit the completed admission form to the backend.
 *
 * Before sending:
 *  - Checks killer conditions (low education, low English, unwilling to train)
 *  - Disables the submit button and shows a spinner
 *
 * The payload includes:
 *  - All form fields from state.formData
 *  - UTM tracking parameters (utm_source, lead_id)
 *  - CV file attachment (if uploaded)
 *  - Honeypot field value (should be empty for real users)
 *
 * On failure, invalidates the CSRF token so a fresh one is fetched on retry.
 *
 * @param {HTMLButtonElement} button  The submit button element (for UI feedback)
 */
export async function submitForm(button) {
  const { formData } = state;

  // Check disqualification ("killer") conditions
  const isKilled = KILLER_EDUCATION.has(formData.education)
    || KILLER_ENGLISH.has(formData.englishLevel)
    || formData.willingToTrain === 'no';

  if (isKilled) { goTo('killer'); return; }

  // Disable button and show loading state
  button.disabled = true;
  button.innerHTML = '<div class="spinner"></div> Enviando...';
  button.style.opacity = '0.7';
  button.style.cursor = 'wait';

  try {
    const csrfToken = await fetchCsrfToken();

    // Build multipart form data payload
    const payload = new FormData();
    Object.entries(formData).forEach(([key, value]) => {
      if (value != null) payload.append(key, value);
    });
    if (tracking.utmSource) payload.append('utm_source', tracking.utmSource);
    if (tracking.leadId)    payload.append('lead_id', tracking.leadId);
    if (state.cvFile)       payload.append('cv_file', state.cvFile);

    // Include honeypot field (should be empty for legitimate submissions)
    const hpField = document.querySelector('input[name="website"]');
    if (hpField) payload.append('website', hpField.value);

    const response = await fetch('/api/submit', {
      method: 'POST',
      body: payload,
      headers: { 'X-CSRF-Token': csrfToken }
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    goTo('success');
  } catch (error) {
    console.error('Submit error:', error);
    state.csrfToken = null; // Force fresh token on retry
    alert('Error al enviar la solicitud. Por favor, inténtalo de nuevo.');
    button.disabled = false;
    button.textContent = 'Sí, acepto';
    button.style.opacity = '';
    button.style.cursor = '';
  }
}
