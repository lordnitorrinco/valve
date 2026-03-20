vi.mock('../src/framework/router.js', () => ({
  goTo: vi.fn(),
  registerView: vi.fn(),
}));

import { goTo } from '../src/framework/router.js';
import { state } from '../src/framework/store.js';
import { backButton, conditionalSlot, fieldInput, fieldSelect, fieldDate, fieldPhone, fieldFile } from '../src/ui/fields.js';
import { SVG } from '../src/ui/icons.js';

describe('fields', () => {
  beforeEach(() => {
    state.formData = { phonePrefix: '+34' };
    state.errors = {};
    state.cvFile = null;
    document.body.innerHTML = '';
    goTo.mockClear();
  });

  describe('backButton', () => {
    it('creates a back button that navigates on click', () => {
      const btn = backButton('intro');
      expect(btn.tagName).toBe('BUTTON');
      expect(btn.className).toBe('btn-back');
      expect(btn.textContent).toContain('Atrás');
      btn.click();
      expect(goTo).toHaveBeenCalledWith('intro');
    });
  });

  describe('conditionalSlot', () => {
    it('creates a plain conditional slot', () => {
      const slot = conditionalSlot();
      expect(slot.tagName).toBe('DIV');
      expect(slot.className).toBe('conditional-slot');
    });

    it('adds compact class when compact is true', () => {
      const slot = conditionalSlot(true);
      expect(slot.className).toBe('conditional-slot compact');
    });
  });

  describe('fieldInput', () => {
    it('creates a basic text input field group', () => {
      const group = fieldInput('someField', 'Label', 'placeholder');
      expect(group.tagName).toBe('DIV');
      expect(group.dataset.field).toBe('someField');
      expect(group.querySelector('label').textContent).toBe('Label');
      const input = group.querySelector('input');
      expect(input.type).toBe('text');
      expect(input.placeholder).toBe('placeholder');
      expect(input.className).toBe('modern-input');
    });

    it('creates an input with icon wrapper', () => {
      const group = fieldInput('email', 'Email', 'email', 'email', SVG.mail);
      const wrapper = group.querySelector('.input-wrapper');
      expect(wrapper).not.toBeNull();
      expect(wrapper.querySelector('.input-icon')).not.toBeNull();
      const input = wrapper.querySelector('input');
      expect(input.className).toContain('with-icon');
    });

    it('marks optional fields with suffix', () => {
      const group = fieldInput('other', 'Label', '', 'text', null, true);
      const optionalSpan = group.querySelector('.optional');
      expect(optionalSpan).not.toBeNull();
      expect(optionalSpan.textContent).toContain('opcional');
    });

    it('sets autocomplete off for sensitive fields', () => {
      const group = fieldInput('email', 'Email', 'email');
      expect(group.querySelector('input').getAttribute('autocomplete')).toBe('off');

      const group2 = fieldInput('firstName', 'Name', 'name');
      expect(group2.querySelector('input').getAttribute('autocomplete')).toBe('off');
    });

    it('sets inputMode decimal for techYearsExperience', () => {
      const group = fieldInput('techYearsExperience', 'Years', 'years');
      expect(group.querySelector('input').getAttribute('inputmode')).toBe('decimal');
    });

    it('updates state on input for regular fields', () => {
      const group = fieldInput('someField', 'Label', '');
      const input = group.querySelector('input');
      input.value = 'hello';
      input.dispatchEvent(new Event('input'));
      expect(state.formData.someField).toBe('hello');
    });

    it('validates decimal input for techYearsExperience', () => {
      const group = fieldInput('techYearsExperience', 'Years', '');
      const input = group.querySelector('input');

      input.value = '5.5';
      input.dispatchEvent(new Event('input'));
      expect(state.formData.techYearsExperience).toBe('5.5');

      input.value = 'abc';
      input.dispatchEvent(new Event('input'));
      expect(input.value).toBe('5.5');
    });

    it('restores previous value for invalid techYearsExperience input', () => {
      state.formData.techYearsExperience = '3';
      const group = fieldInput('techYearsExperience', 'Years', '');
      const input = group.querySelector('input');

      input.value = '999';
      input.dispatchEvent(new Event('input'));
      expect(input.value).toBe('3');
    });

    it('uses existing state value as initial input value', () => {
      state.formData.someField = 'preset';
      const group = fieldInput('someField', 'Label', '');
      expect(group.querySelector('input').value).toBe('preset');
    });
  });

  describe('fieldSelect', () => {
    const options = [
      { value: 'a', label: 'Option A' },
      { value: 'b', label: 'Option B' },
    ];

    it('creates a select with empty option and choices', () => {
      const group = fieldSelect('testSel', 'Pick one', options);
      expect(group.dataset.field).toBe('testSel');
      const select = group.querySelector('select');
      expect(select.options.length).toBe(3);
      expect(select.options[0].textContent).toBe('Selecciona');
    });

    it('uses custom empty label', () => {
      const group = fieldSelect('testSel', 'Pick', options, 'Choose...');
      const select = group.querySelector('select');
      expect(select.options[0].textContent).toBe('Choose...');
    });

    it('marks the option matching state as selected', () => {
      state.formData.testSel = 'b';
      const group = fieldSelect('testSel', 'Pick', options);
      const select = group.querySelector('select');
      expect(select.options[2].selected).toBe(true);
    });

    it('updates state and calls onChangeExtra on change', () => {
      const extra = vi.fn();
      const group = fieldSelect('testSel', 'Pick', options, null, extra);
      const select = group.querySelector('select');
      select.value = 'a';
      select.dispatchEvent(new Event('change'));
      expect(state.formData.testSel).toBe('a');
      expect(extra).toHaveBeenCalledWith('a');
    });

    it('handles change without onChangeExtra', () => {
      const group = fieldSelect('testSel', 'Pick', options);
      const select = group.querySelector('select');
      select.value = 'b';
      select.dispatchEvent(new Event('change'));
      expect(state.formData.testSel).toBe('b');
    });
  });

  describe('fieldDate', () => {
    it('creates a date input with max set to today', () => {
      const group = fieldDate('dateOfBirth', 'Birth date');
      expect(group.dataset.field).toBe('dateOfBirth');
      const input = group.querySelector('input');
      expect(input.type).toBe('date');
      expect(input.getAttribute('max')).toBe(new Date().toISOString().split('T')[0]);
    });

    it('updates state on date change', () => {
      const group = fieldDate('dateOfBirth', 'Birth date');
      const input = group.querySelector('input');
      input.value = '1995-06-15';
      input.dispatchEvent(new Event('change'));
      expect(state.formData.dateOfBirth).toBe('1995-06-15');
    });
  });

  describe('fieldPhone', () => {
    it('creates phone row with prefix and input', () => {
      const group = fieldPhone();
      expect(group.dataset.field).toBe('phone');
      expect(group.querySelector('.phone-prefix-btn')).not.toBeNull();
      expect(group.querySelector('input[type="tel"]')).not.toBeNull();
    });

    it('opens and closes prefix dropdown on button clicks', () => {
      const group = fieldPhone();
      document.body.appendChild(group);
      const prefixBtn = group.querySelector('.phone-prefix-btn');

      prefixBtn.click();
      expect(group.querySelector('.phone-prefix-dropdown')).not.toBeNull();

      prefixBtn.click();
      expect(group.querySelector('.phone-prefix-dropdown')).toBeNull();
    });

    it('selects a prefix from the dropdown', () => {
      const group = fieldPhone();
      document.body.appendChild(group);
      const prefixBtn = group.querySelector('.phone-prefix-btn');

      prefixBtn.click();
      const options = group.querySelectorAll('.phone-prefix-dropdown button');
      expect(options.length).toBeGreaterThan(0);

      options[1].click();
      expect(state.formData.phonePrefix).not.toBe('+34');
      expect(group.querySelector('.phone-prefix-dropdown')).toBeNull();
    });

    it('closes dropdown on document click', () => {
      const group = fieldPhone();
      document.body.appendChild(group);
      const prefixBtn = group.querySelector('.phone-prefix-btn');

      prefixBtn.click();
      expect(group.querySelector('.phone-prefix-dropdown')).not.toBeNull();

      document.dispatchEvent(new Event('click'));
      expect(group.querySelector('.phone-prefix-dropdown')).toBeNull();
    });

    it('updates state on phone input', () => {
      const group = fieldPhone();
      const input = group.querySelector('input[type="tel"]');
      input.value = '600111222';
      input.dispatchEvent(new Event('input'));
      expect(state.formData.phone).toBe('600111222');
    });
  });

  describe('fieldFile', () => {
    it('renders upload button when no file is selected', () => {
      const group = fieldFile();
      expect(group.querySelector('.file-upload-btn')).not.toBeNull();
      expect(group.querySelector('.file-hint')).not.toBeNull();
    });

    it('shows error class on upload button when cvFile error exists', () => {
      state.errors.cvFile = 'Required';
      const group = fieldFile();
      expect(group.querySelector('.file-upload-btn').className).toContain('error');
    });

    it('shows file info after selecting a file', () => {
      const group = fieldFile();
      const fileInput = group.querySelector('input[type="file"]');
      const mockFile = new File(['content'], 'test.pdf', { type: 'application/pdf' });
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: (i) => mockFile },
        configurable: true,
      });
      fileInput.dispatchEvent(new Event('change'));

      expect(state.cvFile).toBe(mockFile);
      expect(group.querySelector('.file-selected')).not.toBeNull();
    });

    it('removes file on remove button click', () => {
      state.cvFile = new File([''], 'existing.pdf');
      const group = fieldFile();
      const removeBtn = group.querySelector('.file-remove-btn');
      expect(removeBtn).not.toBeNull();
      removeBtn.click();
      expect(state.cvFile).toBeNull();
      expect(group.querySelector('.file-upload-btn')).not.toBeNull();
    });

    it('rejects oversized files', () => {
      vi.spyOn(window, 'alert').mockImplementation(() => {});
      const group = fieldFile();
      const fileInput = group.querySelector('input[type="file"]');
      const bigFile = new File(['x'], 'big.pdf');
      Object.defineProperty(bigFile, 'size', { value: 11 * 1024 * 1024 });
      Object.defineProperty(fileInput, 'files', {
        value: { 0: bigFile, length: 1, item: (i) => bigFile },
        configurable: true,
      });
      fileInput.dispatchEvent(new Event('change'));

      expect(window.alert).toHaveBeenCalled();
      expect(state.cvFile).toBeNull();
    });

    it('ignores change event with no file', () => {
      const group = fieldFile();
      const fileInput = group.querySelector('input[type="file"]');
      Object.defineProperty(fileInput, 'files', {
        value: { length: 0 },
        configurable: true,
      });
      fileInput.dispatchEvent(new Event('change'));
      expect(state.cvFile).toBeNull();
    });

    it('clears cvFile error and removes error element on valid file select', () => {
      state.errors.cvFile = 'Required';
      const group = fieldFile();
      document.body.appendChild(group);

      const errorP = document.createElement('p');
      errorP.className = 'field-error';
      group.appendChild(errorP);

      const fileInput = group.querySelector('input[type="file"]');
      const mockFile = new File(['x'], 'cv.pdf');
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: (i) => mockFile },
        configurable: true,
      });
      fileInput.dispatchEvent(new Event('change'));

      expect(state.errors.cvFile).toBeUndefined();
      expect(group.querySelector('.field-error')).toBeNull();
    });

    it('clears cvFile error even without error element in DOM', () => {
      state.errors.cvFile = 'Required';
      const group = fieldFile();

      const fileInput = group.querySelector('input[type="file"]');
      const mockFile = new File(['x'], 'cv.pdf');
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: (i) => mockFile },
        configurable: true,
      });
      fileInput.dispatchEvent(new Event('change'));

      expect(state.errors.cvFile).toBeUndefined();
    });

    it('triggers file input click from upload button', () => {
      const group = fieldFile();
      const fileInput = group.querySelector('input[type="file"]');
      const clickSpy = vi.spyOn(fileInput, 'click');
      const uploadBtn = group.querySelector('.file-upload-btn');
      uploadBtn.click();
      expect(clickSpy).toHaveBeenCalled();
    });
  });
});
