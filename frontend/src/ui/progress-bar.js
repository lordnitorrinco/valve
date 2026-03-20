import { el } from '../framework/createElement.js';
import { STEP_NAMES, STEP_ORDER, FORM_STEPS } from '../data/options.js';

let barEl = null;

export function createProgressBar() {
  barEl = el('div', { className: 'progress-bar-wrap' });
  barEl.style.display = 'none';
  return barEl;
}

export function updateProgressBar(view) {
  const isFormStep = FORM_STEPS.has(view);
  barEl.style.display = isFormStep ? '' : 'none';
  if (!isFormStep) return;

  const currentStep = STEP_ORDER[view] || 0;
  const circles = barEl.querySelectorAll('.step-circle');
  const labels = barEl.querySelectorAll('.step-label');

  if (circles.length === 0) {
    barEl.innerHTML = '';
    const bar = el('div', { className: 'progress-bar' });
    STEP_NAMES.forEach((name, i) => {
      const num = i + 1;
      const active = num <= currentStep;
      const circle = el('div', { className: `step-circle ${active ? 'active' : 'pending'}` });
      circle.textContent = String(num);
      const label = el('span', { className: `step-label ${active ? 'active' : 'pending'}` }, name);
      bar.appendChild(el('div', { className: 'step-indicator' }, circle, label));
    });
    barEl.appendChild(bar);
  } else {
    circles.forEach((c, i) => {
      c.className = `step-circle ${(i + 1) <= currentStep ? 'active' : 'pending'}`;
    });
    labels.forEach((l, i) => {
      l.className = `step-label ${(i + 1) <= currentStep ? 'active' : 'pending'}`;
    });
  }
}
