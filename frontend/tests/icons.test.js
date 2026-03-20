import { SVG } from '../src/ui/icons.js';

describe('SVG icon library', () => {
  const iconNames = Object.keys(SVG);

  it.each(iconNames)('SVG.%s is a valid SVG string', (name) => {
    expect(typeof SVG[name]).toBe('string');
    expect(SVG[name].length).toBeGreaterThan(0);
    expect(SVG[name]).toContain('<svg');
    expect(SVG[name]).toContain('</svg>');
  });

  it('uses default size 16 for standard icons', () => {
    expect(SVG.user).toContain('width="16"');
    expect(SVG.user).toContain('height="16"');
    expect(SVG.mail).toContain('width="16"');
  });

  it('shield uses custom size 20', () => {
    expect(SVG.shield).toContain('width="20"');
    expect(SVG.shield).toContain('height="20"');
  });

  it('x uses custom size 32', () => {
    expect(SVG.x).toContain('width="32"');
  });

  it('checkCircle uses custom size 40', () => {
    expect(SVG.checkCircle).toContain('width="40"');
  });
});
