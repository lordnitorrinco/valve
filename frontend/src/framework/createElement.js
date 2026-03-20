import { state } from './store.js';

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

export function showErrors() {
  document.querySelectorAll('.field-group').forEach(group => {
    const name = group.dataset.field;
    if (!name) return;

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

export function clearFieldError(name, element) {
  if (!state.errors[name]) return;
  delete state.errors[name];
  if (!element) return;
  element.classList.remove('error');
  const group = element.closest('.field-group') || element;
  const errorEl = group.querySelector('.field-error');
  if (errorEl) errorEl.remove();
}
