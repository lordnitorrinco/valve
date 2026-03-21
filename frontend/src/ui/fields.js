/**
 * Reusable form field components.
 *
 * Each function returns a complete field-group DOM element
 * with label, input/select, error display, and two-way binding
 * to the global state via input/change event handlers.
 */

import { state } from '../framework/store.js';
import { PHONE_PREFIXES } from '../data/options.js';
import { el, clearFieldError } from '../framework/createElement.js';
import { SVG } from './icons.js';
import { goTo } from '../framework/router.js';

/**
 * Handle text input changes — update state and clear field errors.
 * Special handling for techYearsExperience (decimal number validation).
 *
 * @param {string} name   State field name
 * @param {Event}  event  Input event
 */
function handleInput(name, event) {
  if (name === 'techYearsExperience') {
    // Only allow numbers with max 1 decimal place
    if (event.target.value === '' || /^\d{0,2}(\.\d{0,1})?$/.test(event.target.value)) {
      state.formData[name] = event.target.value;
    } else {
      event.target.value = state.formData[name] || '';
    }
  } else {
    state.formData[name] = event.target.value;
  }
  clearFieldError(name, event.target);
}

/**
 * Create a "Back" navigation button.
 *
 * @param {string} target  View name to navigate back to
 * @returns {HTMLElement}   Button element
 */
export function backButton(target) {
  return el('button', { className: 'btn-back', onClick: () => goTo(target) },
    el('span', { innerHTML: SVG.chevronLeft }), 'Atrás'
  );
}

/**
 * Create a container for conditional fields that appear/disappear
 * based on user selections (e.g. nationality "other" → extra fields).
 *
 * @param {boolean} compact  Use compact spacing
 * @returns {HTMLElement}     Container div
 */
export function conditionalSlot(compact) {
  return el('div', { className: 'conditional-slot' + (compact ? ' compact' : '') });
}

/**
 * Create a text input field with optional icon and autocomplete-off for PII.
 *
 * @param {string}  name         State field name
 * @param {string}  label        Label text
 * @param {string}  placeholder  Input placeholder
 * @param {string}  type         Input type (default: "text")
 * @param {string}  icon         SVG icon HTML (optional)
 * @param {boolean} optional     Show "(optional)" label suffix
 * @returns {HTMLElement}        Complete field group
 */
export function fieldInput(name, label, placeholder, type, icon, optional) {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = name;

  const lbl = el('label');
  lbl.textContent = label;
  if (optional) lbl.appendChild(el('span', { className: 'optional' }, ' (opcional)'));
  group.appendChild(lbl);

  // Disable autocomplete for sensitive fields to prevent data leakage
  const sensitive = ['email', 'phone', 'firstName', 'lastName'];
  const inputAttrs = {
    type: type || 'text',
    className: 'modern-input' + (icon ? ' with-icon' : ''),
    placeholder: placeholder || '',
    value: state.formData[name] || '',
    onInput: (e) => handleInput(name, e)
  };
  if (sensitive.includes(name)) inputAttrs.autocomplete = 'off';
  if (name === 'techYearsExperience') inputAttrs.inputMode = 'decimal';

  if (icon) {
    const wrapper = el('div', { className: 'input-wrapper' });
    wrapper.appendChild(el('span', { className: 'input-icon', innerHTML: icon }));
    wrapper.appendChild(el('input', inputAttrs));
    group.appendChild(wrapper);
  } else {
    group.appendChild(el('input', inputAttrs));
  }

  return group;
}

/**
 * Create a dropdown select field.
 *
 * @param {string}    name           State field name
 * @param {string}    label          Label text
 * @param {Array}     options        Array of { value, label } objects
 * @param {string}    emptyLabel     Placeholder option text
 * @param {Function}  onChangeExtra  Additional callback on selection change
 * @returns {HTMLElement}            Complete field group
 */
export function fieldSelect(name, label, options, emptyLabel, onChangeExtra) {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = name;
  group.appendChild(el('label', null, label));

  const select = document.createElement('select');
  select.className = 'modern-input';
  select.appendChild(el('option', { value: '' }, emptyLabel || 'Selecciona'));

  options.forEach(opt => {
    const option = el('option', { value: opt.value }, opt.label);
    if (state.formData[name] === opt.value) option.selected = true;
    select.appendChild(option);
  });

  select.addEventListener('change', (e) => {
    state.formData[name] = e.target.value;
    clearFieldError(name, select);
    if (onChangeExtra) onChangeExtra(e.target.value);
  });

  group.appendChild(select);
  return group;
}

/**
 * Create a date picker field.
 *
 * @param {string} name   State field name
 * @param {string} label  Label text
 * @returns {HTMLElement}  Complete field group
 */
export function fieldDate(name, label) {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = name;
  group.appendChild(el('label', null, label));

  group.appendChild(el('input', {
    type: 'date',
    className: 'modern-input',
    value: state.formData[name] || '',
    max: new Date().toISOString().split('T')[0], // Cannot select future dates
    onChange: (e) => {
      state.formData[name] = e.target.value;
      clearFieldError(name, e.target);
    }
  }));

  return group;
}

/**
 * Create the phone number field with country code prefix selector.
 *
 * Includes a dropdown button showing the flag + code that opens
 * a scrollable list of all available phone prefixes.
 *
 * @returns {HTMLElement} Complete phone field group
 */
export function fieldPhone() {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = 'phone';
  group.appendChild(el('label', null, 'Número de teléfono'));

  const row = el('div', { className: 'phone-row' });
  const prefixWrap = el('div', { className: 'phone-prefix-wrap' });
  const current = PHONE_PREFIXES.find(p => p.code === state.formData.phonePrefix) || PHONE_PREFIXES[0];
  if (current.code !== state.formData.phonePrefix) {
    state.formData.phonePrefix = current.code;
  }

  // Prefix selector button showing flag + code
  const prefixBtn = el('button', { type: 'button', className: 'phone-prefix-btn' });
  prefixBtn.appendChild(el('span', null, current.flag));
  prefixBtn.appendChild(el('span', { className: 'prefix-code' }, current.code));

  let dropdownOpen = false;

  /** Toggle the prefix dropdown visibility and render options */
  function toggleDropdown() {
    const existing = prefixWrap.querySelector('.phone-prefix-dropdown');
    if (existing) existing.remove();
    if (!dropdownOpen) return;

    const dropdown = el('div', { className: 'phone-prefix-dropdown' });
    PHONE_PREFIXES.forEach(prefix => {
      const btn = el('button', {
        type: 'button',
        onClick: (e) => {
          e.stopPropagation();
          state.formData.phonePrefix = prefix.code;
          prefixBtn.innerHTML = '';
          prefixBtn.appendChild(el('span', null, prefix.flag));
          prefixBtn.appendChild(el('span', { className: 'prefix-code' }, prefix.code));
          dropdownOpen = false;
          toggleDropdown();
        }
      });
      btn.appendChild(el('span', null, prefix.flag));
      btn.appendChild(el('span', { className: 'dd-code' }, prefix.code));
      btn.appendChild(el('span', null, prefix.country));
      dropdown.appendChild(btn);
    });
    prefixWrap.appendChild(dropdown);
  }

  prefixBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdownOpen = !dropdownOpen;
    toggleDropdown();
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', () => {
    if (dropdownOpen) { dropdownOpen = false; toggleDropdown(); }
  });

  prefixWrap.appendChild(prefixBtn);
  row.appendChild(prefixWrap);

  // Phone number input
  const phoneWrap = el('div', { className: 'phone-input-wrap' });
  phoneWrap.appendChild(el('span', { className: 'phone-input-icon', innerHTML: SVG.phone }));
  phoneWrap.appendChild(el('input', {
    type: 'tel',
    className: 'modern-input with-icon',
    placeholder: '600 000 000',
    autocomplete: 'off',
    value: state.formData.phone || '',
    onInput: (e) => {
      state.formData.phone = e.target.value;
      clearFieldError('phone', e.target);
    }
  }));
  row.appendChild(phoneWrap);

  group.appendChild(row);
  return group;
}

/**
 * Create the CV file upload field with drag-and-drop style button.
 *
 * Shows either:
 *  - An upload button (when no file selected)
 *  - The selected filename with a remove button
 *
 * Enforces a 10 MB size limit and accepts PDF/DOC/DOCX files.
 *
 * @returns {HTMLElement} Complete file upload field group
 */
export function fieldFile() {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = 'cvFile';
  group.appendChild(el('label', null, 'Subir CV'));

  const fileInput = el('input', { type: 'file', id: 'cv-upload', accept: '.pdf,.doc,.docx' });
  fileInput.style.display = 'none';
  group.appendChild(fileInput);

  const contentArea = el('div');
  group.appendChild(contentArea);

  /** Re-render the file field UI based on current state */
  function updateUI() {
    contentArea.innerHTML = '';
    if (state.cvFile) {
      // Show selected file with remove button
      const row = el('div', { className: 'file-selected' });
      const info = el('div', { className: 'file-selected-info' });
      info.appendChild(el('span', { innerHTML: SVG.fileIcon }));
      info.appendChild(el('span', null, state.cvFile.name));
      row.appendChild(info);
      row.appendChild(el('button', {
        type: 'button', className: 'file-remove-btn', innerHTML: SVG.trash,
        onClick: () => { state.cvFile = null; fileInput.value = ''; updateUI(); }
      }));
      contentArea.appendChild(row);
    } else {
      // Show upload button
      const btn = el('button', {
        type: 'button',
        className: 'file-upload-btn' + (state.errors.cvFile ? ' error' : ''),
        onClick: () => fileInput.click()
      });
      btn.appendChild(el('span', { innerHTML: SVG.upload }));
      btn.appendChild(el('span', null, 'Seleccionar archivo (PDF, DOC)'));
      contentArea.appendChild(btn);
      contentArea.appendChild(el('p', { className: 'file-hint' }, 'Máximo 10MB'));
    }
  }

  fileInput.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { alert('El archivo no puede superar los 10MB'); return; }
    state.cvFile = file;
    if (state.errors.cvFile) {
      delete state.errors.cvFile;
      const errorEl = group.querySelector('.field-error');
      if (errorEl) errorEl.remove();
    }
    updateUI();
  });

  updateUI();
  return group;
}
