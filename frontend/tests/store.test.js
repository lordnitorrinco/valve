/**
 * Unit tests for the global client-side store (`framework/store.js`).
 * Verifies default `state` and `tracking` shapes and that shared objects
 * remain mutable as the app expects.
 */

import { describe, it, expect } from 'vitest';
import { state, tracking } from '../src/framework/store.js';

// Default state shape, CSRF slot, and tracking defaults
describe('store', () => {
  // Initial view, formData, errors, CV slot, CSRF, and timing
  it('initializes state with default values', () => {
    expect(state.currentView).toBe('intro');
    expect(state.formData).toBeDefined();
    expect(state.formData.phonePrefix).toBe('+34');
    expect(state.errors).toEqual({});
    expect(state.cvFile).toBeNull();
    expect(state.csrfToken).toBeNull();
    expect(state.formStartedAt).toBeNull();
  });

  // UTM / lead identifiers start empty
  it('initializes tracking with empty defaults', () => {
    expect(tracking.utmSource).toBe('');
    expect(tracking.leadId).toBe('');
  });

  // Form fields can be updated in place
  it('state.formData is mutable', () => {
    state.formData.firstName = 'Test';
    expect(state.formData.firstName).toBe('Test');
    state.formData.firstName = '';
  });

  // Validation errors map is a plain mutable object
  it('state.errors is mutable', () => {
    state.errors.test = 'error';
    expect(state.errors.test).toBe('error');
    delete state.errors.test;
  });
});
