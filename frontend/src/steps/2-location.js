/**
 * Step 2 — Location and availability.
 *
 * Collects: country of residence, nationality (with conditional
 * "other" sub-fields for specific nationality and work permit),
 * relocation willingness, date of birth.
 */

import { registerView, goTo } from '../framework/router.js';
import { state } from '../framework/store.js';
import { COUNTRIES } from '../data/options.js';
import { el, showErrors } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';
import { validatePersonal2 } from '../services/validation.js';
import { backButton, fieldInput, fieldSelect, fieldDate, conditionalSlot } from '../ui/fields.js';

registerView('personal2', function renderLocation() {
  const container = el('div', { className: 'form-step compact' });
  container.appendChild(backButton('personal'));
  container.appendChild(el('h2', { className: 'text-lg font-bold', style: 'margin-bottom:0.125rem' }, 'Datos personales'));
  container.appendChild(el('p', { className: 'text-sm text-muted mb-4' }, 'Ubicación y disponibilidad'));

  const form = el('form');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    state.errors = validatePersonal2();
    if (Object.keys(state.errors).length) { showErrors(); return; }
    goTo('education');
  });

  const fields = el('div', { className: 'form-fields compact' });
  fields.appendChild(fieldSelect('countryOfResidence', 'País de residencia',
    COUNTRIES.map(x => ({ value: x, label: x })), 'Selecciona un país'
  ));

  // Conditional fields shown when nationality is "otro" (other)
  const natSlot = conditionalSlot(true);

  function renderNatFields() {
    natSlot.innerHTML = '';
    if (state.formData.nationality === 'otro') {
      natSlot.appendChild(fieldInput('nationalityOther', '¿Cuál es tu nacionalidad?', 'Indica tu nacionalidad'));
      natSlot.appendChild(fieldSelect('workPermit', '¿Tienes permiso de trabajo vigente en España?', [
        { value: 'si', label: 'Sí' },
        { value: 'no', label: 'No' },
        { value: 'en_tramite', label: 'En trámite' }
      ]));
    }
  }

  fields.appendChild(fieldSelect('nationality', 'Nacionalidad', [
    { value: 'española', label: 'España' },
    { value: 'otro', label: 'Otro' }
  ], null, renderNatFields));

  renderNatFields();
  fields.appendChild(natSlot);

  fields.appendChild(fieldSelect('relocation', '¿Estarías dispuesto/a a cambiar de ciudad?', [
    { value: 'si', label: 'Sí' },
    { value: 'no', label: 'No' },
    { value: 'depende', label: 'Depende de la oferta' }
  ]));
  fields.appendChild(fieldDate('dateOfBirth', 'Fecha de nacimiento'));
  fields.appendChild(el('div', { className: 'pt-4' },
    el('button', { type: 'submit', className: 'modern-button' },
      'Continuar', el('span', { innerHTML: SVG.arrowRight })
    )
  ));

  form.appendChild(fields);
  container.appendChild(form);
  return container;
});
