/**
 * Lightweight DOM element creation and error display utilities.
 *
 * Replaces JSX/template engines with a simple el() function
 * that creates elements, sets attributes, and appends children.
 */

import { state } from './store.js';

/**
 * Create a DOM element with attributes and children.
 *
 * Handles special attributes:
 *  - className  → sets element.className
 *  - innerHTML  → sets element.innerHTML
 *  - style (string) → sets via setAttribute
 *  - on*        → adds event listener (e.g. onClick → "click")
 *
 * Children can be strings (→ text nodes), elements, arrays, or null (ignored).
 *
 * @param {string}   tag       HTML tag name
 * @param {object}   attrs     Attributes/properties to set
 * @param {...*}     children  Child nodes (strings, elements, arrays, null)
 * @returns {HTMLElement}
 */
export function el(tag, attrs, ...children) {
  const node = document.createElement(tag);
  if (attrs) {
    for (const [key, value] of Object.entries(attrs)) {
      if (key === 'className')      node.className = value;
      else if (key === 'innerHTML') node.innerHTML = value;
      else if (key === 'style' && typeof value === 'string') node.setAttribute('style', value);
      else if (key.startsWith('on')) node.addEventListener(key.slice(2).toLowerCase(), value);
      else node.setAttribute(key, value);
    }
  }
  for (const child of children.flat()) {
    if (child == null) continue;
    node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
  }
  return node;
}

/**
 * Display validation errors on all .field-group elements in the DOM.
 *
 * For each field group:
 *  - If state.errors[fieldName] exists → add .error class to input and show error <p>
 *  - If no error → remove .error class and any error <p>
 */
export function showErrors() {
  document.querySelectorAll('.field-group').forEach(group => {
    const name = group.dataset.field;
    if (!name) return;

    // Remove any previous error message
    const oldError = group.querySelector('.field-error');
    if (oldError) oldError.remove();

    const input = group.querySelector('.modern-input');
    if (state.errors[name]) {
      if (input) input.classList.add('error');
      group.appendChild(el('p', { className: 'field-error' }, state.errors[name]));
    } else {
      if (input) input.classList.remove('error');
    }
  });
}

/**
 * Clear a single field's validation error from state and DOM.
 * Called on input/change events to provide real-time feedback.
 *
 * @param {string}      name     Field name to clear
 * @param {HTMLElement}  element  The input element (for removing .error class)
 */
export function clearFieldError(name, element) {
  if (!state.errors[name]) return;
  delete state.errors[name];
  if (!element) return;
  element.classList.remove('error');
  const group = element.closest('.field-group') || element;
  const errorEl = group.querySelector('.field-error');
  if (errorEl) errorEl.remove();
}
