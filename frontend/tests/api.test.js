vi.mock('../src/framework/router.js', () => ({
  goTo: vi.fn(),
  registerView: vi.fn(),
}));

import { goTo } from '../src/framework/router.js';
import { submitForm } from '../src/services/api.js';
import { state, tracking } from '../src/framework/store.js';

describe('api — submitForm', () => {
  let button;

  beforeEach(() => {
    state.formData = { phonePrefix: '+34' };
    state.csrfToken = null;
    state.cvFile = null;
    state.errors = {};
    tracking.utmSource = '';
    tracking.leadId = '';

    button = document.createElement('button');
    button.textContent = 'Sí, acepto';

    document.body.innerHTML = '';

    globalThis.fetch = vi.fn();
    vi.spyOn(window, 'alert').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
    goTo.mockClear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('navigates to killer when education is disqualifying', async () => {
    state.formData.education = 'ESO';
    await submitForm(button);
    expect(goTo).toHaveBeenCalledWith('killer');
    expect(fetch).not.toHaveBeenCalled();
  });

  it('navigates to killer when english level is disqualifying', async () => {
    state.formData.education = 'Grado Universitario';
    state.formData.englishLevel = 'A1';
    await submitForm(button);
    expect(goTo).toHaveBeenCalledWith('killer');
  });

  it('navigates to killer when unwilling to train', async () => {
    state.formData.education = 'Grado Universitario';
    state.formData.englishLevel = 'B2';
    state.formData.willingToTrain = 'no';
    await submitForm(button);
    expect(goTo).toHaveBeenCalledWith('killer');
  });

  it('submits successfully with fresh CSRF token', async () => {
    state.formData = {
      phonePrefix: '+34',
      education: 'Grado Universitario',
      englishLevel: 'B2',
      willingToTrain: 'si',
      firstName: 'Test',
      nullField: null,
    };
    tracking.utmSource = 'google';
    tracking.leadId = 'abc';
    state.cvFile = new File(['pdf'], 'cv.pdf');
    document.body.innerHTML = '<input name="website" value="">';

    globalThis.fetch = vi.fn()
      .mockResolvedValueOnce({ json: () => Promise.resolve({ token: 'tok123' }) })
      .mockResolvedValueOnce({ ok: true });

    await submitForm(button);

    expect(fetch).toHaveBeenCalledTimes(2);
    expect(fetch).toHaveBeenNthCalledWith(1, '/api/csrf-token');
    expect(goTo).toHaveBeenCalledWith('success');
    expect(state.csrfToken).toBe('tok123');
  });

  it('uses cached CSRF token and skips token fetch', async () => {
    state.formData = {
      phonePrefix: '+34',
      education: 'Grado Universitario',
      englishLevel: 'B2',
      willingToTrain: 'si',
    };
    state.csrfToken = 'cached-token';

    globalThis.fetch = vi.fn().mockResolvedValueOnce({ ok: true });

    await submitForm(button);

    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('/api/submit', expect.objectContaining({
      method: 'POST',
      headers: { 'X-CSRF-Token': 'cached-token' },
    }));
    expect(goTo).toHaveBeenCalledWith('success');
  });

  it('handles CSRF token fetch failure gracefully', async () => {
    state.formData = {
      phonePrefix: '+34',
      education: 'Grado Universitario',
      englishLevel: 'B2',
      willingToTrain: 'si',
    };

    globalThis.fetch = vi.fn()
      .mockRejectedValueOnce(new Error('network'))
      .mockResolvedValueOnce({ ok: true });

    await submitForm(button);

    expect(goTo).toHaveBeenCalledWith('success');
  });

  it('handles submit HTTP error and re-enables button', async () => {
    state.formData = {
      phonePrefix: '+34',
      education: 'Grado Universitario',
      englishLevel: 'B2',
      willingToTrain: 'si',
    };

    globalThis.fetch = vi.fn()
      .mockResolvedValueOnce({ json: () => Promise.resolve({ token: 't' }) })
      .mockResolvedValueOnce({ ok: false, status: 500 });

    await submitForm(button);

    expect(console.error).toHaveBeenCalled();
    expect(window.alert).toHaveBeenCalled();
    expect(state.csrfToken).toBeNull();
    expect(button.disabled).toBe(false);
    expect(button.textContent).toBe('Sí, acepto');
    expect(button.style.opacity).toBe('');
    expect(button.style.cursor).toBe('');
  });

  it('disables button and shows spinner during submission', async () => {
    state.formData = {
      phonePrefix: '+34',
      education: 'Grado Universitario',
      englishLevel: 'B2',
      willingToTrain: 'si',
    };

    let capturedButtonState;
    globalThis.fetch = vi.fn()
      .mockResolvedValueOnce({ json: () => Promise.resolve({ token: 't' }) })
      .mockImplementationOnce(() => {
        capturedButtonState = {
          disabled: button.disabled,
          opacity: button.style.opacity,
          cursor: button.style.cursor,
        };
        return Promise.resolve({ ok: true });
      });

    await submitForm(button);

    expect(capturedButtonState.disabled).toBe(true);
    expect(capturedButtonState.opacity).toBe('0.7');
    expect(capturedButtonState.cursor).toBe('wait');
  });
});
