import { registerView, goTo } from '../framework/router.js';
import { el } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';
import { submitForm } from '../services/api.js';
import { backButton } from '../ui/fields.js';

registerView('consent', function renderConsent() {
  const container = el('div', { className: 'form-step' });
  container.appendChild(backButton('experience'));

  const header = el('div', { style: 'display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem' });
  header.appendChild(el('div', {
    style: 'width:2.5rem;height:2.5rem;border-radius:0.75rem;background:hsl(0 0% 10% / .05);display:flex;align-items:center;justify-content:center;flex-shrink:0',
    innerHTML: SVG.shield
  }));
  const headerText = el('div');
  headerText.appendChild(el('h2', { className: 'text-xl font-bold' }, 'Autorización'));
  headerText.appendChild(el('p', { className: 'text-sm text-muted' }, 'Último paso'));
  header.appendChild(headerText);
  container.appendChild(header);

  const card = el('div', { className: 'modern-card', style: 'padding:1rem;margin-bottom:1.5rem' });
  card.appendChild(el('p', { className: 'text-sm font-medium mb-3' },
    '¿Nos autorizas a compartir tu información con las empresas asociadas en este proceso de selección?'
  ));
  card.appendChild(el('p', { className: 'text-xs text-muted leading-relaxed' },
    'Evolve Academy Educación SL tratará los datos de carácter personal que nos has proporcionado con la finalidad de gestionar tu solicitud y el proceso de selección asociado. Podrás revocar el consentimiento otorgado, así como ejercitar los derechos reconocidos en los artículos 15 a 22 del Reglamento (UE) 2016/679, mediante solicitud dirigida a soporte@evolve.es. Usted autoriza expresamente a Evolve Academy para ceder los datos recogidos en el presente formulario a la empresa en que usted tiene intención de incorporarse.'
  ));
  container.appendChild(card);

  const buttons = el('div', { className: 'consent-buttons' });
  const acceptBtn = el('button', { className: 'modern-button', onClick: () => submitForm(acceptBtn) });
  acceptBtn.textContent = 'Sí, acepto';
  buttons.appendChild(acceptBtn);
  buttons.appendChild(el('button', { className: 'btn-reject', onClick: () => goTo('rejected') }, 'No acepto'));
  container.appendChild(buttons);

  return container;
});
