import { describe, it, expect, beforeEach } from 'vitest';
import { state } from '../src/framework/store.js';
import { el, showErrors, clearFieldError } from '../src/framework/createElement.js';

describe('el() - createElement', () => {
  it('creates an element with the given tag', () => {
    const node = el('div');
    expect(node.tagName).toBe('DIV');
  });

  it('sets className', () => {
    const node = el('div', { className: 'test-class' });
    expect(node.className).toBe('test-class');
  });

  it('sets innerHTML', () => {
    const node = el('div', { innerHTML: '<b>bold</b>' });
    expect(node.innerHTML).toBe('<b>bold</b>');
  });

  it('sets attributes', () => {
    const node = el('input', { type: 'text', placeholder: 'Enter' });
    expect(node.getAttribute('type')).toBe('text');
    expect(node.getAttribute('placeholder')).toBe('Enter');
  });

  it('appends text children', () => {
    const node = el('p', null, 'Hello', ' ', 'World');
    expect(node.textContent).toBe('Hello World');
  });

  it('appends element children', () => {
    const child = el('span', null, 'inner');
    const parent = el('div', null, child);
    expect(parent.children).toHaveLength(1);
    expect(parent.firstChild.tagName).toBe('SPAN');
  });

  it('ignores null children', () => {
    const node = el('div', null, null, 'text', null);
    expect(node.childNodes).toHaveLength(1);
  });

  it('flattens array children', () => {
    const items = ['a', 'b', 'c'].map(t => el('li', null, t));
    const list = el('ul', null, items);
    expect(list.children).toHaveLength(3);
  });

  it('attaches event listeners', () => {
    let clicked = false;
    const btn = el('button', { onClick: () => { clicked = true; } });
    btn.click();
    expect(clicked).toBe(true);
  });

  it('sets style as string attribute', () => {
    const node = el('div', { style: 'color:red' });
    expect(node.getAttribute('style')).toBe('color:red');
  });
});

describe('showErrors', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    state.errors = {};
  });

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

describe('clearFieldError', () => {
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

  it('does nothing when no error exists', () => {
    state.errors = {};
    clearFieldError('name', null);
    expect(state.errors).toEqual({});
  });
});
