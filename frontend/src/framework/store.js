const params = new URLSearchParams(window.location.search);

export const state = {
  currentView: 'intro',
  formData: { phonePrefix: '+34' },
  errors: {},
  cvFile: null,
  csrfToken: null,
  formStartedAt: null
};

export const tracking = {
  utmSource: params.get('utm_source') || '',
  leadId: params.get('id') || ''
};
