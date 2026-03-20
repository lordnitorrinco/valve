/**
 * Step 3 — Education.
 *
 * Collects: education level, study area (conditional on level),
 * graduation year (conditional on level), English proficiency.
 *
 * Conditional fields appear/disappear based on the selected
 * education level using a conditional slot container.
 */

import { registerView, goTo } from '../framework/router.js';
import { state } from '../framework/store.js';
import { EDUCATION_LEVELS, EDUCATION_NO_AREA, EDUCATION_NO_YEAR, ENGLISH_LEVELS, STUDY_AREAS, GRADUATION_YEARS } from '../data/options.js';
import { el, showErrors } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';
import { validateEducation } from '../services/validation.js';
import { backButton, fieldSelect, conditionalSlot } from '../ui/fields.js';

registerView('education', function renderEducation() {
  const container = el('div', { className: 'form-step' });
  container.appendChild(backButton('personal2'));
  container.appendChild(el('h2', { className: 'text-xl font-bold mb-1' }, 'Formación'));
  container.appendChild(el('p', { className: 'text-sm text-muted mb-6' }, 'Tu formación académica'));

  const form = el('form');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    state.errors = validateEducation();
    if (Object.keys(state.errors).length) { showErrors(); return; }
    goTo('experience');
  });

  const fields = el('div', { className: 'form-fields' });

  // Container for study area and graduation year (shown conditionally)
  const eduSlot = conditionalSlot();

  /** Show/hide study area and graduation year based on education level */
  function renderEduFields(val) {
    eduSlot.innerHTML = '';
    const level = val || state.formData.education;
    if (level && !EDUCATION_NO_AREA.has(level))
      eduSlot.appendChild(fieldSelect('studyArea', 'Área de estudios', STUDY_AREAS));
    if (level && !EDUCATION_NO_YEAR.has(level))
      eduSlot.appendChild(fieldSelect('graduationYear', 'Año de graduación',
        GRADUATION_YEARS.map(y => ({ value: y, label: y }))
      ));
  }

  fields.appendChild(fieldSelect('education', 'Nivel de estudios',
    EDUCATION_LEVELS.map(l => ({ value: l, label: l })), null, renderEduFields
  ));

  renderEduFields();
  fields.appendChild(eduSlot);

  fields.appendChild(fieldSelect('englishLevel', 'Nivel de inglés', ENGLISH_LEVELS));
  fields.appendChild(el('div', { className: 'pt-4' },
    el('button', { type: 'submit', className: 'modern-button' },
      'Continuar', el('span', { innerHTML: SVG.arrowRight })
    )
  ));

  form.appendChild(fields);
  container.appendChild(form);
  return container;
});
