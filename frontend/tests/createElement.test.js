/**
 * Unit tests for the lightweight DOM helpers in `framework/createElement.js`.
 * Covers `el()` element creation, `showErrors()` syncing validation state to the
 * DOM, and `clearFieldError()` clearing a single field’s error UI.
 */

import { describe, it, expect, beforeEach } from 'vitest';
import { state } from '../src/framework/store.js';
import { el, showErrors, clearFieldError } from '../src/framework/createElement.js';

// Tag, props, children, events, and style string handling
describe('el() - createElement', () => {
  // Minimal element factory
  it('creates an element with the given tag', () => {
    const node = el('div');
    expect(node.tagName).toBe('DIV');
  });

  // className prop maps to DOM class
  it('sets className', () => {
    const node = el('div', { className: 'test-class' });
    expect(node.className).toBe('test-class');
  });

  // innerHTML for rich snippets
  it('sets innerHTML', () => {
    const node = el('div', { innerHTML: '<b>bold</b>' });
    expect(node.innerHTML).toBe('<b>bold</b>');
  });

  // Arbitrary HTML attributes via props
  it('sets attributes', () => {
    const node = el('input', { type: 'text', placeholder: 'Enter' });
    expect(node.getAttribute('type')).toBe('text');
    expect(node.getAttribute('placeholder')).toBe('Enter');
  });

  // Text nodes from variadic string children
  it('appends text children', () => {
    const node = el('p', null, 'Hello', ' ', 'World');
    expect(node.textContent).toBe('Hello World');
  });

  // JSON/API numeric fields must render without throwing (admin modal, etc.)
  it('appends number and boolean children as text', () => {
    const n = el('span', { className: 'modal-value' }, 42);
    expect(n.textContent).toBe('42');
    const b = el('span', null, true);
    expect(b.textContent).toBe('true');
  });

  // Nested elements as children
  it('appends element children', () => {
    const child = el('span', null, 'inner');
    const parent = el('div', null, child);
    expect(parent.children).toHaveLength(1);
    expect(parent.firstChild.tagName).toBe('SPAN');
  });

  // Null slots are skipped in children list
  it('ignores null children', () => {
    const node = el('div', null, null, 'text', null);
    expect(node.childNodes).toHaveLength(1);
  });

  // Arrays of nodes are flattened into parent
  it('flattens array children', () => {
    const items = ['a', 'b', 'c'].map(t => el('li', null, t));
    const list = el('ul', null, items);
    expect(list.children).toHaveLength(3);
  });

  // onClick and similar event props
  it('attaches event listeners', () => {
    let clicked = false;
    const btn = el('button', { onClick: () => { clicked = true; } });
    btn.click();
    expect(clicked).toBe(true);
  });

  // style as raw attribute string (not CSSStyleDeclaration)
  it('sets style as string attribute', () => {
    const node = el('div', { style: 'color:red' });
    expect(node.getAttribute('style')).toBe('color:red');
  });
});

// Renders field-level errors from `state.errors` into `.field-group` markup
describe('showErrors', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    state.errors = {};
  });

  // Injects `.field-error` and `.error` class when state has a message
  it('shows error message on field group', () => {
    const input = el('input', { className: 'modern-input' });
    const group = el('div', { className: 'field-group' }, input);
    group.dataset.field = 'email';
    document.body.appendChild(group);

    state.errors = { email: 'Email required' };
    showErrors();

    const errorEl = group.querySelector('.field-error');
    expect(errorEl).not.toBeNull();
    expect(errorEl.textContent).toBe('Email required');
    expect(input.classList.contains('error')).toBe(true);
  });

  // Skips groups without data-field name
  it('ignores field-group without data-field', () => {
    const group = el('div', { className: 'field-group' });
    group.appendChild(el('input', { className: 'modern-input' }));
    document.body.appendChild(group);
    state.errors = { orphan: 'x' };
    showErrors();
    expect(group.querySelector('.field-error')).toBeNull();
  });

  // Clears DOM and classes when error removed from state
  it('removes error when field has no error', () => {
    const input = el('input', { className: 'modern-input error' });
    const errorP = el('p', { className: 'field-error' }, 'Old error');
    const group = el('div', { className: 'field-group' }, input, errorP);
    group.dataset.field = 'email';
    document.body.appendChild(group);

    state.errors = {};
    showErrors();

    expect(group.querySelector('.field-error')).toBeNull();
    expect(input.classList.contains('error')).toBe(false);
  });
});

// Single-field cleanup for live validation UX
describe('clearFieldError', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    state.errors = {};
  });

  // Syncs state + DOM for one field key
  it('removes error from state and DOM', () => {
    state.errors = { name: 'Required' };

    const input = el('input', { className: 'modern-input error' });
    const errorP = el('p', { className: 'field-error' }, 'Required');
    const group = el('div', { className: 'field-group' }, input, errorP);
    group.dataset.field = 'name';
    document.body.appendChild(group);

    clearFieldError('name', input);

    expect(state.errors).not.toHaveProperty('name');
    expect(input.classList.contains('error')).toBe(false);
    expect(group.querySelector('.field-error')).toBeNull();
  });

  // No-op when nothing to clear
  it('does nothing when no error exists', () => {
    state.errors = {};
    clearFieldError('name', null);
    expect(state.errors).toEqual({});
  });

  // Deletes state but skips DOM when element is null (branch: if (!element) return)
  it('clears state only when element is null', () => {
    state.errors = { email: 'bad' };
    clearFieldError('email', null);
    expect(state.errors).not.toHaveProperty('email');
  });

  // Wrapper without .field-group: use element as root for removing .field-error
  it('clears error when clearing from a wrapper without field-group class', () => {
    state.errors = { email: 'bad' };
    const wrap = document.createElement('div');
    const input = el('input', { className: 'modern-input error' });
    wrap.appendChild(input);
    wrap.appendChild(el('p', { className: 'field-error' }, 'bad'));
    document.body.appendChild(wrap);
    clearFieldError('email', wrap);
    expect(state.errors).not.toHaveProperty('email');
    expect(wrap.querySelector('.field-error')).toBeNull();
  });

  // Branch: no .field-error node left (already removed) — if (errorEl) is false
  it('still clears state and class when .field-error was already removed from DOM', () => {
    state.errors = { name: 'Required' };
    const input = el('input', { className: 'modern-input error' });
    const group = el('div', { className: 'field-group' }, input);
    group.dataset.field = 'name';
    document.body.appendChild(group);
    clearFieldError('name', input);
    expect(state.errors).not.toHaveProperty('name');
    expect(input.classList.contains('error')).toBe(false);
  });
});
