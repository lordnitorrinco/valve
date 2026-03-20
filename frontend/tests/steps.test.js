import { describe, it, expect, beforeEach, beforeAll, afterEach, vi } from 'vitest';
import { state } from '../src/framework/store.js';

const registerView = vi.fn();
const goTo = vi.fn();

vi.mock('../src/framework/router.js', () => ({
  registerView,
  goTo
}));

vi.mock('../src/services/api.js', () => ({
  submitForm: vi.fn()
}));

const renders = {};

function captureRender(name) {
  const call = registerView.mock.calls.find(c => c[0] === name);
  if (call) renders[name] = call[1];
}

describe('step modules', () => {
  beforeAll(async () => {
    await import('../src/steps/0-intro.js');
    captureRender('intro');

    await import('../src/steps/1-contact.js');
    captureRender('personal');

    await import('../src/steps/2-location.js');
    captureRender('personal2');

    await import('../src/steps/3-education.js');
    captureRender('education');

    await import('../src/steps/4-experience.js');
    captureRender('experience');

    await import('../src/steps/5-consent.js');
    captureRender('consent');

    await import('../src/steps/results.js');
    captureRender('rejected');
    captureRender('killer');
    captureRender('success');

    await import('../src/steps/admin.js');
    captureRender('admin');
  });

  beforeEach(() => {
    document.body.innerHTML = '';
    state.currentView = 'intro';
    state.formData = { phonePrefix: '+34' };
    state.errors = {};
    state.cvFile = null;
    goTo.mockClear();
  });

  describe('0-intro', () => {
    it('registers "intro" view', () => {
      expect(renders.intro).toBeDefined();
    });

    it('render function returns an HTMLElement with intro content', () => {
      const el = renders.intro();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.className).toContain('intro-section');
      expect(el.textContent).toContain('Iniciar proceso de selección');
    });

    it('renders partner logo items', () => {
      const el = renders.intro();
      const logos = el.querySelectorAll('.partner-logo-item');
      expect(logos.length).toBeGreaterThan(0);
    });

    it('has a CTA button', () => {
      const el = renders.intro();
      const btn = el.querySelector('.modern-button');
      expect(btn).not.toBeNull();
      expect(btn.textContent).toContain('Comenzar solicitud');
    });

    it('CTA button navigates to personal', () => {
      const el = renders.intro();
      const btn = el.querySelector('.modern-button');
      btn.click();
      expect(goTo).toHaveBeenCalledWith('personal');
    });

    it('renders two feature cards', () => {
      const el = renders.intro();
      const cards = el.querySelectorAll('.feature-card');
      expect(cards.length).toBe(2);
    });

    it('renders two marquee tracks for infinite scroll', () => {
      const el = renders.intro();
      const tracks = el.querySelectorAll('.marquee-content');
      expect(tracks.length).toBe(2);
    });
  });

  describe('1-contact', () => {
    it('registers "personal" view', () => {
      expect(renders.personal).toBeDefined();
    });

    it('renders form with contact fields', () => {
      const el = renders.personal();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.className).toContain('form-step');
      expect(el.querySelector('form')).not.toBeNull();
      expect(el.textContent).toContain('Datos personales');
    });

    it('has back button and submit button', () => {
      const el = renders.personal();
      expect(el.querySelector('.btn-back')).not.toBeNull();
      expect(el.querySelector('button[type="submit"]')).not.toBeNull();
    });

    it('contains required field groups', () => {
      const el = renders.personal();
      expect(el.querySelector('[data-field="firstName"]')).not.toBeNull();
      expect(el.querySelector('[data-field="lastName"]')).not.toBeNull();
      expect(el.querySelector('[data-field="email"]')).not.toBeNull();
      expect(el.querySelector('[data-field="phone"]')).not.toBeNull();
      expect(el.querySelector('[data-field="gender"]')).not.toBeNull();
    });

    it('form submit with errors shows errors', () => {
      const container = renders.personal();
      document.body.appendChild(container);
      const form = container.querySelector('form');
      form.dispatchEvent(new Event('submit', { cancelable: true }));
      expect(Object.keys(state.errors).length).toBeGreaterThan(0);
    });

    it('form submit without errors navigates to personal2', () => {
      state.formData = {
        phonePrefix: '+34',
        firstName: 'Test',
        lastName: 'User',
        gender: 'hombre',
        email: 'test@test.com',
        phone: '600000000'
      };
      const container = renders.personal();
      document.body.appendChild(container);
      const form = container.querySelector('form');
      form.dispatchEvent(new Event('submit', { cancelable: true }));
      expect(goTo).toHaveBeenCalledWith('personal2');
    });
  });

  describe('2-location', () => {
    it('registers "personal2" view', () => {
      expect(renders.personal2).toBeDefined();
    });

    it('renders location form fields', () => {
      const el = renders.personal2();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.textContent).toContain('Ubicación y disponibilidad');
      expect(el.querySelector('[data-field="countryOfResidence"]')).not.toBeNull();
      expect(el.querySelector('[data-field="nationality"]')).not.toBeNull();
      expect(el.querySelector('[data-field="relocation"]')).not.toBeNull();
      expect(el.querySelector('[data-field="dateOfBirth"]')).not.toBeNull();
    });

    it('shows conditional nationality fields when nationality=otro', () => {
      state.formData.nationality = 'otro';
      const el = renders.personal2();
      expect(el.querySelector('[data-field="nationalityOther"]')).not.toBeNull();
      expect(el.querySelector('[data-field="workPermit"]')).not.toBeNull();
    });

    it('hides conditional fields when nationality is not otro', () => {
      state.formData.nationality = 'española';
      const el = renders.personal2();
      expect(el.querySelector('[data-field="nationalityOther"]')).toBeNull();
    });

    it('form submit navigates to education on valid data', () => {
      state.formData = {
        phonePrefix: '+34',
        countryOfResidence: 'España',
        nationality: 'española',
        relocation: 'si',
        dateOfBirth: '1990-01-01'
      };
      const container = renders.personal2();
      document.body.appendChild(container);
      const form = container.querySelector('form');
      form.dispatchEvent(new Event('submit', { cancelable: true }));
      expect(goTo).toHaveBeenCalledWith('education');
    });

    it('form submit with errors does not navigate', () => {
      state.formData = { phonePrefix: '+34' };
      const container = renders.personal2();
      document.body.appendChild(container);
      const form = container.querySelector('form');
      form.dispatchEvent(new Event('submit', { cancelable: true }));
      expect(goTo).not.toHaveBeenCalledWith('education');
    });
  });

  describe('3-education', () => {
    it('registers "education" view', () => {
      expect(renders.education).toBeDefined();
    });

    it('renders education form', () => {
      const el = renders.education();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.textContent).toContain('Formación');
      expect(el.querySelector('[data-field="education"]')).not.toBeNull();
      expect(el.querySelector('[data-field="englishLevel"]')).not.toBeNull();
    });

    it('shows study area and graduation year when education level requires it', () => {
      state.formData.education = 'Grado Universitario';
      const el = renders.education();
      expect(el.querySelector('[data-field="studyArea"]')).not.toBeNull();
      expect(el.querySelector('[data-field="graduationYear"]')).not.toBeNull();
    });

    it('hides study area for basic education levels', () => {
      state.formData.education = 'ESO';
      const el = renders.education();
      expect(el.querySelector('[data-field="studyArea"]')).toBeNull();
    });

    it('form submit navigates to experience on valid data', () => {
      state.formData = {
        phonePrefix: '+34',
        education: 'FP Grado Superior',
        englishLevel: 'B1',
        graduationYear: '2020',
        studyArea: 'Ingeniería y Arquitectura'
      };
      const container = renders.education();
      document.body.appendChild(container);
      const form = container.querySelector('form');
      form.dispatchEvent(new Event('submit', { cancelable: true }));
      expect(goTo).toHaveBeenCalledWith('experience');
    });
  });

  describe('4-experience', () => {
    it('registers "experience" view', () => {
      expect(renders.experience).toBeDefined();
    });

    it('renders experience form', () => {
      const el = renders.experience();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.textContent).toContain('Experiencia');
      expect(el.querySelector('[data-field="situation"]')).not.toBeNull();
      expect(el.querySelector('[data-field="techYearsExperience"]')).not.toBeNull();
      expect(el.querySelector('[data-field="willingToTrain"]')).not.toBeNull();
    });

    it('shows job role field when situation is Empleado', () => {
      state.formData.situation = 'Empleado';
      const el = renders.experience();
      expect(el.querySelector('[data-field="jobRole"]')).not.toBeNull();
    });

    it('hides job role for non-employed situations', () => {
      state.formData.situation = 'Desempleado';
      const el = renders.experience();
      expect(el.querySelector('[data-field="jobRole"]')).toBeNull();
    });

    it('includes LinkedIn field as optional', () => {
      const el = renders.experience();
      const linkedinGroup = el.querySelector('[data-field="linkedinUrl"]');
      expect(linkedinGroup).not.toBeNull();
      expect(linkedinGroup.textContent).toContain('opcional');
    });

    it('includes CV file upload', () => {
      const el = renders.experience();
      expect(el.querySelector('[data-field="cvFile"]')).not.toBeNull();
    });

    it('form submit navigates to consent on valid data', () => {
      state.formData = {
        phonePrefix: '+34',
        situation: 'Desempleado',
        techYearsExperience: '2',
        willingToTrain: 'si'
      };
      state.cvFile = new File(['x'], 'cv.pdf');
      const container = renders.experience();
      document.body.appendChild(container);
      const form = container.querySelector('form');
      form.dispatchEvent(new Event('submit', { cancelable: true }));
      expect(goTo).toHaveBeenCalledWith('consent');
    });
  });

  describe('5-consent', () => {
    it('registers "consent" view', () => {
      expect(renders.consent).toBeDefined();
    });

    it('renders consent page with authorization text', () => {
      const el = renders.consent();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.textContent).toContain('Autorización');
      expect(el.textContent).toContain('Último paso');
      expect(el.textContent).toContain('Evolve Academy');
    });

    it('has accept and reject buttons', () => {
      const el = renders.consent();
      const acceptBtn = el.querySelector('.modern-button');
      expect(acceptBtn.textContent).toContain('Sí, acepto');
      const rejectBtn = el.querySelector('.btn-reject');
      expect(rejectBtn.textContent).toContain('No acepto');
    });

    it('reject button navigates to rejected', () => {
      const el = renders.consent();
      const rejectBtn = el.querySelector('.btn-reject');
      rejectBtn.click();
      expect(goTo).toHaveBeenCalledWith('rejected');
    });

    it('accept button calls submitForm', async () => {
      const { submitForm } = await import('../src/services/api.js');
      const el = renders.consent();
      const acceptBtn = el.querySelector('.modern-button');
      acceptBtn.click();
      expect(submitForm).toHaveBeenCalled();
    });

    it('has a back button to experience', () => {
      const el = renders.consent();
      const backBtn = el.querySelector('.btn-back');
      expect(backBtn).not.toBeNull();
      backBtn.click();
      expect(goTo).toHaveBeenCalledWith('experience');
    });

    it('renders shield icon in header', () => {
      const el = renders.consent();
      expect(el.innerHTML).toContain('<svg');
    });
  });

  describe('results — rejected', () => {
    it('registers "rejected" view', () => {
      expect(renders.rejected).toBeDefined();
    });

    it('renders rejection page', () => {
      const el = renders.rejected();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.className).toContain('status-page');
      expect(el.textContent).toContain('No es posible continuar');
    });

    it('has a back button to consent', () => {
      const el = renders.rejected();
      const btn = el.querySelector('.modern-button');
      expect(btn.textContent).toContain('Volver al paso anterior');
      btn.click();
      expect(goTo).toHaveBeenCalledWith('consent');
    });
  });

  describe('results — killer', () => {
    it('registers "killer" view', () => {
      expect(renders.killer).toBeDefined();
    });

    it('renders killer page', () => {
      const el = renders.killer();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.className).toContain('status-page');
      expect(el.textContent).toContain('No cumples con los requisitos');
    });
  });

  describe('results — success', () => {
    it('registers "success" view', () => {
      expect(renders.success).toBeDefined();
    });

    it('renders success page', () => {
      vi.useFakeTimers();
      const el = renders.success();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.className).toContain('status-page');
      expect(el.textContent).toContain('Gracias por completar tu aplicación');
      vi.useRealTimers();
    });

    it('has animated check circle', () => {
      vi.useFakeTimers();
      const el = renders.success();
      const circle = el.querySelector('.status-icon-circle.success');
      expect(circle).not.toBeNull();
      expect(circle.style.transform).toBe('scale(0)');
      vi.advanceTimersByTime(300);
      expect(circle.style.transform).toBe('scale(1)');
      vi.useRealTimers();
    });

    it('shows next steps card', () => {
      vi.useFakeTimers();
      const el = renders.success();
      expect(el.textContent).toContain('Próximos pasos');
      vi.useRealTimers();
    });
  });

  describe('admin', () => {
    let originalFetch;

    beforeEach(() => {
      originalFetch = globalThis.fetch;
    });

    afterEach(() => {
      globalThis.fetch = originalFetch;
    });

    it('registers "admin" view', () => {
      expect(renders.admin).toBeDefined();
    });

    it('renders admin page with loading state', () => {
      globalThis.fetch = vi.fn(() => new Promise(() => {}));
      const el = renders.admin();
      expect(el).toBeInstanceOf(HTMLElement);
      expect(el.className).toContain('admin-page');
      expect(el.textContent).toContain('Panel de solicitudes');
      expect(el.querySelector('.spinner')).not.toBeNull();
    });

    it('renders submissions table on success', async () => {
      const submissions = [
        { id: 1, first_name: 'Ana', last_name: 'García', created_at: '2025-01-15T10:00:00Z' },
        { id: 2, first_name: 'Carlos', last_name: 'López', created_at: '2025-01-16T12:00:00Z' }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      await vi.waitFor(() => {
        expect(el.querySelector('.admin-table')).not.toBeNull();
      });
      expect(el.textContent).toContain('Ana García');
      expect(el.textContent).toContain('Carlos López');
      expect(el.querySelector('.admin-stat-num').textContent).toBe('2');
    });

    it('renders empty state when no submissions', async () => {
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions: [] })
      });

      const el = renders.admin();
      await vi.waitFor(() => {
        expect(el.querySelector('.admin-empty')).not.toBeNull();
      });
      expect(el.textContent).toContain('No hay solicitudes');
    });

    it('renders error state on fetch failure', async () => {
      globalThis.fetch = vi.fn().mockRejectedValue(new Error('Network down'));

      const el = renders.admin();
      await vi.waitFor(() => {
        expect(el.querySelector('.admin-error')).not.toBeNull();
      });
      expect(el.textContent).toContain('Error al cargar');
    });

    it('opens detail modal on row click', async () => {
      const submissions = [
        { id: 1, first_name: 'Ana', last_name: 'García', created_at: '2025-01-15T10:00:00Z', email: 'ana@test.com' }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody tr')).not.toBeNull();
      });
      el.querySelector('tbody tr').click();
      expect(document.querySelector('.modal-backdrop')).not.toBeNull();
      expect(document.querySelector('.modal').textContent).toContain('Ana García');
    });

    it('closes modal on close button click', async () => {
      const submissions = [
        { id: 1, first_name: 'Test', last_name: 'User', created_at: '2025-01-15T10:00:00Z' }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody tr')).not.toBeNull();
      });
      el.querySelector('tbody tr').click();
      const closeBtn = document.querySelector('.modal-close');
      closeBtn.click();
      expect(document.querySelector('.modal-backdrop')).toBeNull();
    });

    it('closes modal on backdrop click', async () => {
      const submissions = [
        { id: 1, first_name: 'Test', last_name: 'User', created_at: '2025-01-15T10:00:00Z' }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody tr')).not.toBeNull();
      });
      el.querySelector('tbody tr').click();
      const backdrop = document.querySelector('.modal-backdrop');
      backdrop.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(document.querySelector('.modal-backdrop')).toBeNull();
    });

    it('closes modal on Escape key', async () => {
      const submissions = [
        { id: 1, first_name: 'Test', last_name: 'User', created_at: '2025-01-15T10:00:00Z' }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody tr')).not.toBeNull();
      });
      el.querySelector('tbody tr').click();
      expect(document.querySelector('.modal-backdrop')).not.toBeNull();
      document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
      expect(document.querySelector('.modal-backdrop')).toBeNull();
    });

    it('modal shows CV download button when cv_filename exists', async () => {
      const submissions = [
        { id: 5, first_name: 'A', last_name: 'B', created_at: '2025-01-15', cv_filename: 'cv.pdf' }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody tr')).not.toBeNull();
      });
      el.querySelector('tbody tr').click();
      const cvBtn = document.querySelector('.modal-cv-btn');
      expect(cvBtn).not.toBeNull();
      expect(cvBtn.getAttribute('href')).toBe('/api/submissions/5/cv');
    });

    it('modal shows all field labels', async () => {
      const submissions = [{
        id: 1, first_name: 'A', last_name: 'B', created_at: '2025-01-15',
        email: 'a@b.com', phone: '600000000', phone_prefix: '+34',
        gender: 'hombre', date_of_birth: '1990-01-01',
        linkedin_url: 'https://linkedin.com/in/test'
      }];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody tr')).not.toBeNull();
      });
      el.querySelector('tbody tr').click();
      const modal = document.querySelector('.modal');
      expect(modal.textContent).toContain('Email');
      expect(modal.textContent).toContain('Teléfono');
      expect(modal.textContent).toContain('Género');
      expect(modal.querySelector('a[href="https://linkedin.com/in/test"]')).not.toBeNull();
    });

    it('formatDate returns — for empty date', async () => {
      const submissions = [
        { id: 1, first_name: 'A', last_name: 'B', created_at: null }
      ];
      globalThis.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ submissions })
      });

      const el = renders.admin();
      document.body.appendChild(el);
      await vi.waitFor(() => {
        expect(el.querySelector('tbody')).not.toBeNull();
      });
      const dateCell = el.querySelector('.date-cell');
      expect(dateCell.textContent).toBe('—');
    });
  });
});
