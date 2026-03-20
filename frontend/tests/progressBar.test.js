import { createProgressBar, updateProgressBar } from '../src/ui/progress-bar.js';
import { STEP_NAMES } from '../src/data/options.js';

describe('progress bar', () => {
  let bar;

  beforeEach(() => {
    bar = createProgressBar();
    document.body.innerHTML = '';
    document.body.appendChild(bar);
  });

  describe('createProgressBar', () => {
    it('returns a hidden wrapper div', () => {
      expect(bar.tagName).toBe('DIV');
      expect(bar.className).toBe('progress-bar-wrap');
      expect(bar.style.display).toBe('none');
    });
  });

  describe('updateProgressBar', () => {
    it('hides the bar for non-form views', () => {
      updateProgressBar('intro');
      expect(bar.style.display).toBe('none');
    });

    it('shows and builds step indicators on first form-step render', () => {
      updateProgressBar('personal');
      expect(bar.style.display).toBe('');
      expect(bar.querySelectorAll('.step-circle').length).toBe(STEP_NAMES.length);
      expect(bar.querySelectorAll('.step-label').length).toBe(STEP_NAMES.length);
    });

    it('marks correct steps as active based on step order', () => {
      updateProgressBar('education');
      const circles = bar.querySelectorAll('.step-circle');
      expect(circles[0].className).toContain('active');
      expect(circles[1].className).toContain('active');
      expect(circles[2].className).toContain('active');
      expect(circles[3].className).toContain('pending');
      expect(circles[4].className).toContain('pending');
    });

    it('updates classes on subsequent renders without rebuilding', () => {
      updateProgressBar('personal');
      updateProgressBar('experience');
      const circles = bar.querySelectorAll('.step-circle');
      const labels = bar.querySelectorAll('.step-label');
      expect(circles[0].className).toContain('active');
      expect(circles[3].className).toContain('active');
      expect(circles[4].className).toContain('pending');
      expect(labels[3].className).toContain('active');
      expect(labels[4].className).toContain('pending');
    });

    it('hides again when navigating to a non-form view', () => {
      updateProgressBar('personal');
      expect(bar.style.display).toBe('');
      updateProgressBar('success');
      expect(bar.style.display).toBe('none');
    });
  });
});
