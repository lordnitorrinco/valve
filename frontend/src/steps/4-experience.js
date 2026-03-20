/**
 * Step 4 — Professional experience.
 *
 * Collects: employment situation, current job role (conditional),
 * years of tech experience, LinkedIn URL (optional), CV file upload,
 * willingness to complete training.
 */

import { registerView, goTo } from '../framework/router.js';
import { state } from '../framework/store.js';
import { SITUATIONS, NO_JOB_ROLE } from '../data/options.js';
import { el, showErrors } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';
import { validateExperience } from '../services/validation.js';
import { backButton, fieldInput, fieldSelect, fieldFile, conditionalSlot } from '../ui/fields.js';

registerView('experience', function renderExperience() {
  const container = el('div', { className: 'form-step' });
  container.appendChild(backButton('education'));
  container.appendChild(el('h2', { className: 'text-xl font-bold mb-1' }, 'Experiencia'));
  container.appendChild(el('p', { className: 'text-sm text-muted mb-6' }, 'Tu situación profesional actual'));

  const form = el('form');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    state.errors = validateExperience();
    if (Object.keys(state.errors).length) { showErrors(); return; }
    goTo('consent');
  });

  const fields = el('div', { className: 'form-fields' });

  // Job role field is only shown when the user is employed
  const sitSlot = conditionalSlot();

  function renderSitFields(val) {
    sitSlot.innerHTML = '';
    const situation = val || state.formData.situation;
    if (situation && !NO_JOB_ROLE.has(situation))
      sitSlot.appendChild(fieldInput('jobRole', 'Puesto actual', 'Ej: Analista de datos, Comercial...'));
  }

  fields.appendChild(fieldSelect('situation', 'Situación laboral actual', SITUATIONS, null, renderSitFields));
  renderSitFields();
  fields.appendChild(sitSlot);

  fields.appendChild(fieldInput('techYearsExperience', 'Años de experiencia en el sector Tech', 'Años de experiencia', 'number'));
  fields.appendChild(fieldInput('linkedinUrl', 'Perfil de LinkedIn', 'https://linkedin.com/in/tu-perfil', 'url', null, true));
  fields.appendChild(fieldFile());
  fields.appendChild(fieldSelect('willingToTrain',
    '¿Estarías dispuesto/a a formarte durante 30 semanas para poder optar a nuestros procesos de selección?',
    [
      { value: 'si', label: 'Sí' },
      { value: 'no', label: 'No' },
      { value: 'necesito_mas_info', label: 'Necesito más información' }
    ]
  ));
  fields.appendChild(el('div', { className: 'pt-4' },
    el('button', { type: 'submit', className: 'modern-button' },
      'Continuar', el('span', { innerHTML: SVG.arrowRight })
    )
  ));

  form.appendChild(fields);
  container.appendChild(form);
  return container;
});
