/**
 * Global application state (single source of truth).
 *
 * This is a plain reactive object — no framework overhead.
 * Components read and mutate state directly; the router
 * re-renders the current view when navigating between steps.
 */

// Extract tracking parameters from the URL query string
const params = new URLSearchParams(window.location.search);

/**
 * Main application state shared across all views.
 *
 * @property {string}      currentView    Active view/step identifier
 * @property {object}      formData       All form field values keyed by field name
 * @property {object}      errors         Current validation errors keyed by field name
 * @property {File|null}   cvFile         Uploaded CV file reference
 * @property {string|null} csrfToken      Cached CSRF token from the API
 * @property {number|null} formStartedAt  Timestamp when the user entered the first form step
 */
export const state = {
  currentView: 'intro',
  formData: { phonePrefix: '+34' },
  errors: {},
  cvFile: null,
  csrfToken: null,
  formStartedAt: null
};

/**
 * UTM tracking data extracted from URL parameters.
 * Forwarded to the backend alongside form data.
 */
export const tracking = {
  utmSource: params.get('utm_source') || '',
  leadId: params.get('id') || ''
};
