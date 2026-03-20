/**
 * Result screens shown after form completion or disqualification.
 *
 * Three possible outcomes:
 *  - "rejected"  → User declined the data consent (can go back)
 *  - "killer"    → User doesn't meet minimum requirements (education/english)
 *  - "success"   → Form submitted successfully
 */

import { registerView, goTo } from '../framework/router.js';
import { el } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';

/**
 * Consent rejection screen.
 * Explains that the process cannot continue without consent
 * and offers a button to go back and accept.
 */
registerView('rejected', function renderRejected() {
  const page = el('div', { className: 'status-page' });

  page.appendChild(el('div', { className: 'status-icon-circle error', innerHTML: SVG.x }));
  page.appendChild(el('h2', { className: 'text-xl font-bold mb-3' }, 'No es posible continuar con tu solicitud'));
  page.appendChild(el('p', { className: 'text-sm text-muted mb-4 max-w-sm' },
    'Para poder continuar con el proceso de solicitud y evaluación, es necesario que autorices el tratamiento y la cesión de tus datos personales en los términos indicados.'
  ));
  page.appendChild(el('p', { className: 'text-sm text-muted mb-4 max-w-sm' },
    'Al no haber aceptado los términos, no podemos seguir tramitando tu aplicación en este momento.'
  ));
  page.appendChild(el('p', { className: 'text-sm text-muted mb-8 max-w-sm' },
    'Si deseas continuar, por favor vuelve al paso anterior y acepta las condiciones indicadas.'
  ));

  const btn = el('button', {
    className: 'modern-button', style: 'width:auto;padding:0.75rem 2rem',
    onClick: () => goTo('consent')
  });
  btn.appendChild(el('span', { innerHTML: SVG.chevronLeft }));
  btn.appendChild(document.createTextNode('Volver al paso anterior'));
  page.appendChild(btn);

  return page;
});

/**
 * Killer (disqualification) screen.
 * Shown when the applicant doesn't meet minimum requirements:
 *  - Education below FP Grado Superior
 *  - English below B1
 *  - Not willing to complete training
 */
registerView('killer', function renderKiller() {
  const page = el('div', { className: 'status-page' });

  page.appendChild(el('div', { className: 'status-icon-circle error', innerHTML: SVG.x }));
  page.appendChild(el('h2', { className: 'text-xl font-bold mb-3' }, 'No cumples con los requisitos mínimos'));
  page.appendChild(el('p', { className: 'text-sm text-muted mb-4 max-w-sm' },
    'Lamentablemente, según la información proporcionada, tu perfil no cumple con los requisitos mínimos para acceder a nuestros procesos de selección en este momento.'
  ));
  page.appendChild(el('p', { className: 'text-sm text-muted mb-8 max-w-sm' },
    'Si crees que ha habido un error o tus circunstancias cambian, no dudes en volver a aplicar.'
  ));

  return page;
});

/**
 * Success screen with animated check icon.
 * Shown after a successful form submission. Displays a
 * "next steps" card explaining that Evolve will reach out by phone.
 */
registerView('success', function renderSuccess() {
  const page = el('div', { className: 'status-page' });

  page.appendChild(el('img', {
    src: '/assets/logo-evolve.svg', alt: 'Evolve Academy',
    style: 'width:2rem;height:auto;margin-bottom:2rem'
  }));

  // Animated check circle (scales in after 200ms)
  const iconCircle = el('div', { className: 'status-icon-circle success', innerHTML: SVG.checkCircle });
  iconCircle.style.transform = 'scale(0)';
  iconCircle.style.transition = 'transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
  page.appendChild(iconCircle);
  setTimeout(() => { iconCircle.style.transform = 'scale(1)'; }, 200);

  page.appendChild(el('h1', { className: 'text-2xl font-bold mb-4' }, 'Gracias por completar tu aplicación'));

  // Next steps info card
  const card = el('div', { className: 'modern-card max-w-sm', style: 'padding:1.25rem;width:100%' });
  const cardIcon = el('div', { style: 'display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem' });
  cardIcon.appendChild(el('div', {
    style: 'width:2.25rem;height:2.25rem;border-radius:0.5rem;background:hsl(0 0% 10% / .05);display:flex;align-items:center;justify-content:center;flex-shrink:0',
    innerHTML: SVG.mail
  }));
  cardIcon.appendChild(el('p', { className: 'text-sm font-medium text-left' }, 'Próximos pasos'));
  card.appendChild(cardIcon);
  card.appendChild(el('p', { className: 'text-sm text-muted text-left' },
    'Un miembro de nuestro equipo se pondrá en contacto por teléfono si tu perfil continuase a la siguiente fase del proceso de selección.'
  ));
  page.appendChild(card);

  return page;
});
