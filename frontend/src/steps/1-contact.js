/**
 * Step 1 — Contact information.
 *
 * Collects: first name, last name, gender, email, phone number.
 * Validates all fields before proceeding to step 2 (location).
 */

import { registerView, goTo } from '../framework/router.js';
import { state } from '../framework/store.js';
import { el, showErrors } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';
import { validatePersonal } from '../services/validation.js';
import { backButton, fieldInput, fieldSelect, fieldPhone } from '../ui/fields.js';

registerView('personal', function renderContact() {
  const container = el('div', { className: 'form-step' });
  container.appendChild(backButton('intro'));
  container.appendChild(el('h2', { className: 'text-xl font-bold mb-1' }, 'Datos personales'));
  container.appendChild(el('p', { className: 'text-sm text-muted mb-6' }, 'Información de contacto'));

  const form = el('form');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    state.errors = validatePersonal();
    if (Object.keys(state.errors).length) { showErrors(); return; }
    goTo('personal2');
  });

  const fields = el('div', { className: 'form-fields' });
  fields.appendChild(fieldInput('firstName', 'Nombre', 'Tu nombre', 'text', SVG.user));
  fields.appendChild(fieldInput('lastName', 'Apellidos', 'Tus apellidos'));
  fields.appendChild(fieldSelect('gender', 'Género', [
    { value: 'hombre', label: 'Hombre' },
    { value: 'mujer', label: 'Mujer' }
  ]));
  fields.appendChild(fieldInput('email', 'Correo electrónico', 'tu@email.com', 'email', SVG.mail));
  fields.appendChild(fieldPhone());
  fields.appendChild(el('div', { className: 'pt-4' },
    el('button', { type: 'submit', className: 'modern-button' },
      'Continuar', el('span', { innerHTML: SVG.arrowRight })
    )
  ));

  form.appendChild(fields);
  container.appendChild(form);
  return container;
});
