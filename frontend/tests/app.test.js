import { describe, it, expect, beforeEach, vi } from 'vitest';

vi.mock('../src/framework/router.js', () => ({
  registerView: vi.fn(),
  initApp: vi.fn(),
  goTo: vi.fn()
}));

vi.mock('../src/steps/0-intro.js', () => ({}));
vi.mock('../src/steps/1-contact.js', () => ({}));
vi.mock('../src/steps/2-location.js', () => ({}));
vi.mock('../src/steps/3-education.js', () => ({}));
vi.mock('../src/steps/4-experience.js', () => ({}));
vi.mock('../src/steps/5-consent.js', () => ({}));
vi.mock('../src/steps/results.js', () => ({}));
vi.mock('../src/steps/admin.js', () => ({}));

describe('app.js bootstrap', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
  });

  it('calls initApp on load', async () => {
    const { initApp } = await import('../src/framework/router.js');
    await import('../src/app.js');
    expect(initApp).toHaveBeenCalled();
  });

  it('navigates to intro by default', async () => {
    Object.defineProperty(window, 'location', {
      value: { pathname: '/', search: '' },
      writable: true,
      configurable: true
    });
    const { goTo } = await import('../src/framework/router.js');
    await import('../src/app.js');
    expect(goTo).toHaveBeenCalledWith('intro');
  });

  it('navigates to admin when pathname is /admin', async () => {
    Object.defineProperty(window, 'location', {
      value: { pathname: '/admin', search: '' },
      writable: true,
      configurable: true
    });
    vi.resetModules();
    const { goTo } = await import('../src/framework/router.js');
    await import('../src/app.js');
    expect(goTo).toHaveBeenCalledWith('admin');
  });
});
