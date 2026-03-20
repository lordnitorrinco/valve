import { state } from '../framework/store.js';
import { PHONE_PREFIXES } from '../data/options.js';
import { el, clearFieldError } from '../framework/createElement.js';
import { SVG } from './icons.js';
import { goTo } from '../framework/router.js';

function handleInput(name, event) {
  if (name === 'techYearsExperience') {
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

export function backButton(target) {
  return el('button', { className: 'btn-back', onClick: () => goTo(target) },
    el('span', { innerHTML: SVG.chevronLeft }), 'Atrás'
  );
}

export function conditionalSlot(compact) {
  return el('div', { className: 'conditional-slot' + (compact ? ' compact' : '') });
}

export function fieldInput(name, label, placeholder, type, icon, optional) {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = name;

  const lbl = el('label');
  lbl.textContent = label;
  if (optional) lbl.appendChild(el('span', { className: 'optional' }, ' (opcional)'));
  group.appendChild(lbl);

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

export function fieldDate(name, label) {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = name;
  group.appendChild(el('label', null, label));

  group.appendChild(el('input', {
    type: 'date',
    className: 'modern-input',
    value: state.formData[name] || '',
    max: new Date().toISOString().split('T')[0],
    onChange: (e) => {
      state.formData[name] = e.target.value;
      clearFieldError(name, e.target);
    }
  }));

  return group;
}

export function fieldPhone() {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = 'phone';
  group.appendChild(el('label', null, 'Número de teléfono'));

  const row = el('div', { className: 'phone-row' });
  const prefixWrap = el('div', { className: 'phone-prefix-wrap' });
  const current = PHONE_PREFIXES.find(p => p.code === state.formData.phonePrefix) || PHONE_PREFIXES[0];

  const prefixBtn = el('button', { type: 'button', className: 'phone-prefix-btn' });
  prefixBtn.appendChild(el('span', null, current.flag));
  prefixBtn.appendChild(el('span', { className: 'prefix-code' }, state.formData.phonePrefix));

  let dropdownOpen = false;

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
  document.addEventListener('click', () => {
    if (dropdownOpen) { dropdownOpen = false; toggleDropdown(); }
  });

  prefixWrap.appendChild(prefixBtn);
  row.appendChild(prefixWrap);

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

export function fieldFile() {
  const group = el('div', { className: 'field-group' });
  group.dataset.field = 'cvFile';
  group.appendChild(el('label', null, 'Subir CV'));

  const fileInput = el('input', { type: 'file', id: 'cv-upload', accept: '.pdf,.doc,.docx' });
  fileInput.style.display = 'none';
  group.appendChild(fileInput);

  const contentArea = el('div');
  group.appendChild(contentArea);

  function updateUI() {
    contentArea.innerHTML = '';
    if (state.cvFile) {
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
