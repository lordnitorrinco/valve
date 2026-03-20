import { state } from './store.js';
import { FORM_STEPS, STEP_VIEWS } from '../data/options.js';
import { el } from './createElement.js';
import { createProgressBar, updateProgressBar } from '../ui/progress-bar.js';

const views = new Map();
let contentEl = null;

export function registerView(name, renderFn) {
  views.set(name, renderFn);
}

export function initApp() {
  const app = document.getElementById('app');
  app.appendChild(createProgressBar());
  contentEl = el('div', { className: 'content-area' });
  app.appendChild(contentEl);

  const hp = el('input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off' });
  hp.style.cssText = 'position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden;pointer-events:none';
  app.appendChild(hp);
}

export function goTo(view) {
  const previousView = state.currentView;
  state.currentView = view;
  state.errors = {};

  if (FORM_STEPS.has(view) && !state.formStartedAt) {
    state.formStartedAt = Date.now();
  }

  const renderFn = views.get(view);
  if (!renderFn) return;

  updateProgressBar(view);

  const oldContent = contentEl.firstChild;

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
    setTimeout(finishExit, 350);
  } else {
    if (oldContent) oldContent.remove();
    mountNew();
  }
}
