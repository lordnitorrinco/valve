/**
 * SPA router with animated view transitions.
 *
 * Manages view registration, navigation (goTo), progress bar updates,
 * and CSS-based slide transitions between form steps.
 * No external router library — just a Map of view name → render function.
 */

import { state } from './store.js';
import { FORM_STEPS, STEP_VIEWS } from '../data/options.js';
import { el } from './createElement.js';
import { createProgressBar, updateProgressBar } from '../ui/progress-bar.js';

/** @type {Map<string, Function>} Registry of view name → render function */
const views = new Map();

/** @type {HTMLElement} Container element where views are mounted */
let contentEl = null;

/**
 * Register a named view with its render function.
 * Called at module load time by each step file.
 *
 * @param {string}   name      View identifier (e.g. "personal", "education")
 * @param {Function} renderFn  Returns an HTMLElement representing the view
 */
export function registerView(name, renderFn) {
  views.set(name, renderFn);
}

/**
 * Initialize the app shell: progress bar, content area, and honeypot field.
 * Called once on startup from app.js.
 */
export function initApp() {
  const app = document.getElementById('app');
  app.appendChild(createProgressBar());
  contentEl = el('div', { className: 'content-area' });
  app.appendChild(contentEl);

  // Honeypot field — hidden from users, filled only by bots
  const hp = el('input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off' });
  hp.style.cssText = 'position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden;pointer-events:none';
  app.appendChild(hp);
}

/**
 * Navigate to a named view with slide transition animation.
 *
 * Flow:
 *  1. Update state.currentView
 *  2. Animate old view out (slide left + fade)
 *  3. Render and mount new view
 *  4. Animate new view in (slide right + fade)
 *
 * @param {string} view  Target view name
 */
export function goTo(view) {
  const previousView = state.currentView;
  state.currentView = view;
  state.errors = {};

  // Track when the user first enters the form flow
  if (FORM_STEPS.has(view) && !state.formStartedAt) {
    state.formStartedAt = Date.now();
  }

  const renderFn = views.get(view);
  if (!renderFn) return;

  updateProgressBar(view);

  const oldContent = contentEl.firstChild;

  /** Mount a new view into the content area with optional entrance animation */
  function mountNew() {
    const content = renderFn();
    if (STEP_VIEWS.has(view)) {
      content.style.opacity = '0';
      content.style.transform = 'translateX(20px)';
    }
    contentEl.appendChild(content);
    if (STEP_VIEWS.has(view)) {
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          content.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          content.style.opacity = '1';
          content.style.transform = 'translateX(0)';
        });
      });
    }
  }

  // Animate exit if there's existing content in a step view
  if (oldContent && STEP_VIEWS.has(previousView)) {
    let done = false;
    const finishExit = () => {
      if (done) return;
      done = true;
      oldContent.remove();
      mountNew();
    };
    oldContent.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    requestAnimationFrame(() => {
      oldContent.style.opacity = '0';
      oldContent.style.transform = 'translateX(-20px)';
    });
    oldContent.addEventListener('transitionend', (e) => {
      if (e.target === oldContent) finishExit();
    }, { once: true });
    // Fallback timeout in case transitionend doesn't fire
    setTimeout(finishExit, 350);
  } else {
    if (oldContent) oldContent.remove();
    mountNew();
  }
}
