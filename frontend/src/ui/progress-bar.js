/**
 * Top progress bar showing the current form step (1-5).
 *
 * Displays numbered circles with step labels. Active steps
 * are highlighted; the bar is hidden on non-form views
 * (intro, success, killer, rejected).
 */

import { el } from '../framework/createElement.js';
import { STEP_NAMES, STEP_ORDER, FORM_STEPS } from '../data/options.js';

/** @type {HTMLElement} Root wrapper element for the progress bar */
let barEl = null;

/**
 * Create the progress bar wrapper element (initially hidden).
 * Called once during app initialization.
 *
 * @returns {HTMLElement} The progress bar container
 */
export function createProgressBar() {
  barEl = el('div', { className: 'progress-bar-wrap' });
  barEl.style.display = 'none';
  return barEl;
}

/**
 * Update the progress bar to reflect the current view.
 *
 * Hides the bar for non-form views. For form steps, either
 * creates the step indicators (first call) or updates their
 * active/pending CSS classes.
 *
 * @param {string} view  Current view name
 */
export function updateProgressBar(view) {
  const isFormStep = FORM_STEPS.has(view);
  barEl.style.display = isFormStep ? '' : 'none';
  if (!isFormStep) return;

  const currentStep = STEP_ORDER[view] || 0;
  const circles = barEl.querySelectorAll('.step-circle');
  const labels = barEl.querySelectorAll('.step-label');

  if (circles.length === 0) {
    // First render — build the step indicators
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
    // Subsequent renders — just toggle active/pending classes
    circles.forEach((c, i) => {
      c.className = `step-circle ${(i + 1) <= currentStep ? 'active' : 'pending'}`;
    });
    labels.forEach((l, i) => {
      l.className = `step-label ${(i + 1) <= currentStep ? 'active' : 'pending'}`;
    });
  }
}
