import { describe, it, expect } from 'vitest';
import { state, tracking } from '../src/framework/store.js';

describe('store', () => {
  it('initializes state with default values', () => {
    expect(state.currentView).toBe('intro');
    expect(state.formData).toBeDefined();
    expect(state.formData.phonePrefix).toBe('+34');
    expect(state.errors).toEqual({});
    expect(state.cvFile).toBeNull();
    expect(state.csrfToken).toBeNull();
    expect(state.formStartedAt).toBeNull();
  });

  it('initializes tracking with empty defaults', () => {
    expect(tracking.utmSource).toBe('');
    expect(tracking.leadId).toBe('');
  });

  it('state.formData is mutable', () => {
    state.formData.firstName = 'Test';
    expect(state.formData.firstName).toBe('Test');
    state.formData.firstName = '';
  });

  it('state.errors is mutable', () => {
    state.errors.test = 'error';
    expect(state.errors.test).toBe('error');
    delete state.errors.test;
  });
});
