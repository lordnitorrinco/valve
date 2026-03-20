import { state, tracking } from '../framework/store.js';
import { KILLER_EDUCATION, KILLER_ENGLISH } from '../data/options.js';
import { goTo } from '../framework/router.js';

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

export async function submitForm(button) {
  const { formData } = state;

  const isKilled = KILLER_EDUCATION.has(formData.education)
    || KILLER_ENGLISH.has(formData.englishLevel)
    || formData.willingToTrain === 'no';

  if (isKilled) { goTo('killer'); return; }

  button.disabled = true;
  button.innerHTML = '<div class="spinner"></div> Enviando...';
  button.style.opacity = '0.7';
  button.style.cursor = 'wait';

  try {
    const csrfToken = await fetchCsrfToken();

    const payload = new FormData();
    Object.entries(formData).forEach(([key, value]) => {
      if (value != null) payload.append(key, value);
    });
    if (tracking.utmSource) payload.append('utm_source', tracking.utmSource);
    if (tracking.leadId)    payload.append('lead_id', tracking.leadId);
    if (state.cvFile)       payload.append('cv_file', state.cvFile);

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
    state.csrfToken = null;
    alert('Error al enviar la solicitud. Por favor, inténtalo de nuevo.');
    button.disabled = false;
    button.textContent = 'Sí, acepto';
    button.style.opacity = '';
    button.style.cursor = '';
  }
}
