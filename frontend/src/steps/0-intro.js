/**
 * Step 0 — Intro / Landing screen.
 *
 * Shows the Evolve Academy branding, two feature highlight cards,
 * a scrolling partner logo marquee, and a CTA button to start
 * the admission form.
 */

import { registerView, goTo } from '../framework/router.js';
import { el } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';
import { PARTNERS } from '../data/partners.js';

registerView('intro', function renderIntro() {
  const section = el('div', { className: 'intro-section' });

  // Logo
  const logoWrap = el('div', { className: 'stagger-item from-top text-center mb-8', style: 'animation-delay:0s' });
  logoWrap.appendChild(el('img', {
    src: '/assets/logo-evolve.svg', className: 'logo', alt: 'Evolve Academy',
    style: 'width:1.75rem;height:auto;margin:0 auto 1.5rem auto'
  }));
  section.appendChild(logoWrap);

  // Title
  const titleWrap = el('div', { className: 'stagger-item subtle text-center mb-4', style: 'animation-delay:0.1s' });
  titleWrap.appendChild(el('h1', { className: 'text-2xl sm-text-3xl font-bold leading-tight' }, 'Iniciar proceso de selección'));
  section.appendChild(titleWrap);

  // Subtitle
  const subtitle = el('p', { className: 'stagger-item subtle text-sm text-muted text-center mb-8', style: 'animation-delay:0.2s' });
  subtitle.textContent = '¡Descubre si cumples con los requisitos para acceder!';
  section.appendChild(subtitle);

  // Feature cards
  const cards = el('div', { className: 'stagger-item space-y-3 mb-8', style: 'animation-delay:0.4s' });
  cards.appendChild(el('div', { className: 'modern-card feature-card' },
    el('div', { className: 'feature-icon', innerHTML: SVG.building }),
    el('div', null,
      el('p', { className: 'font-semibold text-sm' }, 'Empresas líderes del sector'),
      el('p', { className: 'text-xs text-muted' }, 'Procesos de selección con empresas tecnológicas de primer nivel')
    )
  ));
  cards.appendChild(el('div', { className: 'modern-card feature-card' },
    el('div', { className: 'feature-icon', innerHTML: SVG.target }),
    el('div', null,
      el('p', { className: 'font-semibold text-sm' }, 'Evaluación personalizada'),
      el('p', { className: 'text-xs text-muted' }, 'Analizamos tu perfil para ofrecerte las mejores oportunidades')
    )
  ));
  section.appendChild(cards);

  // Partner logo marquee (infinite horizontal scroll)
  const partnersWrap = el('div', { className: 'stagger-item mb-8', style: 'animation-delay:0.5s' });
  partnersWrap.appendChild(el('p', { className: 'text-xs text-muted text-center mb-3' }, 'Empresas asociadas'));
  const marquee = el('div', { className: 'marquee' });
  // Two identical tracks for seamless infinite scroll
  const track1 = el('div', { className: 'marquee-content' });
  const track2 = el('div', { className: 'marquee-content' });
  track2.setAttribute('aria-hidden', 'true');
  PARTNERS.forEach(partner => {
    const makeItem = () => {
      const wrap = el('div', { className: 'partner-logo-item' });
      const img = el('img');
      img.src = partner.logo;
      img.alt = partner.name;
      wrap.appendChild(img);
      return wrap;
    };
    track1.appendChild(makeItem());
    track2.appendChild(makeItem());
  });
  marquee.appendChild(track1);
  marquee.appendChild(track2);
  partnersWrap.appendChild(marquee);
  section.appendChild(partnersWrap);

  section.appendChild(el('div', { className: 'flex-1-spacer' }));

  // CTA button
  const ctaWrap = el('div', { className: 'stagger-item', style: 'animation-delay:0.6s' });
  ctaWrap.appendChild(el('button', { className: 'modern-button', onClick: () => goTo('personal') },
    'Comenzar solicitud', el('span', { innerHTML: SVG.arrowRight })
  ));
  ctaWrap.appendChild(el('p', { className: 'text-10 text-center text-muted mt-2' },
    'Completa el formulario y te contactaremos si tu perfil es seleccionado'
  ));
  section.appendChild(ctaWrap);

  return section;
});
