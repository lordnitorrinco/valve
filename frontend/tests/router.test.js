import { registerView, initApp, goTo } from '../src/framework/router.js';
import { state } from '../src/framework/store.js';

describe('router', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="app"></div>';
    state.currentView = 'intro';
    state.errors = {};
    state.formStartedAt = null;
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('registerView + initApp', () => {
    it('creates progress bar, content area, and honeypot field', () => {
      initApp();
      const app = document.getElementById('app');
      expect(app.querySelector('.progress-bar-wrap')).not.toBeNull();
      expect(app.querySelector('.content-area')).not.toBeNull();
      const hp = app.querySelector('input[name="website"]');
      expect(hp).not.toBeNull();
      expect(hp.type).toBe('text');
      expect(hp.style.position).toBe('absolute');
    });

    it('registerView stores a view that goTo can render', () => {
      const render = vi.fn(() => document.createElement('div'));
      registerView('rv-test', render);
      initApp();
      goTo('rv-test');
      expect(render).toHaveBeenCalled();
    });
  });

  describe('goTo', () => {
    it('updates state.currentView and clears errors', () => {
      registerView('g1', () => document.createElement('div'));
      initApp();
      state.errors = { foo: 'bar' };
      goTo('g1');
      expect(state.currentView).toBe('g1');
      expect(state.errors).toEqual({});
    });

    it('returns early for unregistered view', () => {
      initApp();
      goTo('nonexistent');
      expect(state.currentView).toBe('nonexistent');
      expect(document.querySelector('.content-area').children.length).toBe(0);
    });

    it('sets formStartedAt on first form step entry', () => {
      registerView('personal', () => document.createElement('div'));
      initApp();
      expect(state.formStartedAt).toBeNull();
      goTo('personal');
      expect(state.formStartedAt).toBeGreaterThan(0);
    });

    it('does not override existing formStartedAt', () => {
      registerView('personal', () => document.createElement('div'));
      registerView('personal2', () => document.createElement('div'));
      initApp();
      goTo('personal');
      const ts = state.formStartedAt;
      vi.advanceTimersByTime(400);
      goTo('personal2');
      expect(state.formStartedAt).toBe(ts);
    });

    it('does not set formStartedAt for non-form views', () => {
      registerView('intro-r', () => document.createElement('div'));
      initApp();
      goTo('intro-r');
      expect(state.formStartedAt).toBeNull();
    });

    it('mounts rendered content into the content area', () => {
      registerView('m1', () => {
        const d = document.createElement('div'); d.id = 'mount-test'; return d;
      });
      initApp();
      goTo('m1');
      expect(document.querySelector('#mount-test')).not.toBeNull();
    });

    it('removes old content for non-step-views (no animation)', () => {
      registerView('ns1', () => { const d = document.createElement('div'); d.id = 'ns1'; return d; });
      registerView('ns2', () => { const d = document.createElement('div'); d.id = 'ns2'; return d; });
      initApp();
      goTo('ns1');
      expect(document.querySelector('#ns1')).not.toBeNull();
      goTo('ns2');
      expect(document.querySelector('#ns1')).toBeNull();
      expect(document.querySelector('#ns2')).not.toBeNull();
    });

    it('applies entrance animation styles for step views', () => {
      registerView('personal', () => {
        const d = document.createElement('div'); d.id = 'anim-test'; return d;
      });
      initApp();
      goTo('personal');
      const el = document.querySelector('#anim-test');
      expect(el.style.opacity).toBe('0');
      expect(el.style.transform).toBe('translateX(20px)');
      vi.advanceTimersByTime(100);
      expect(el.style.opacity).toBe('1');
      expect(el.style.transform).toBe('translateX(0)');
    });

    it('handles exit animation via setTimeout fallback', () => {
      registerView('personal', () => { const d = document.createElement('div'); d.id = 'exit1'; return d; });
      registerView('personal2', () => { const d = document.createElement('div'); d.id = 'exit2'; return d; });
      initApp();
      goTo('personal');
      vi.advanceTimersByTime(100);
      goTo('personal2');
      vi.advanceTimersByTime(400);
      expect(document.querySelector('#exit1')).toBeNull();
      expect(document.querySelector('#exit2')).not.toBeNull();
    });

    it('handles exit animation via transitionend event', () => {
      registerView('personal', () => { const d = document.createElement('div'); d.id = 'te1'; return d; });
      registerView('personal2', () => { const d = document.createElement('div'); d.id = 'te2'; return d; });
      initApp();
      goTo('personal');
      vi.advanceTimersByTime(100);
      const oldEl = document.querySelector('#te1');
      goTo('personal2');
      oldEl.dispatchEvent(new Event('transitionend'));
      expect(document.querySelector('#te2')).not.toBeNull();
      vi.advanceTimersByTime(400);
      expect(document.querySelectorAll('#te2').length).toBe(1);
    });
  });
});
