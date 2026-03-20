import { PARTNERS } from '../src/data/partners.js';

describe('PARTNERS data', () => {
  it('is a non-empty array', () => {
    expect(Array.isArray(PARTNERS)).toBe(true);
    expect(PARTNERS.length).toBeGreaterThan(0);
  });

  it('each entry has name and logo strings', () => {
    PARTNERS.forEach(p => {
      expect(typeof p.name).toBe('string');
      expect(p.name.length).toBeGreaterThan(0);
      expect(typeof p.logo).toBe('string');
      expect(p.logo.length).toBeGreaterThan(0);
    });
  });

  it('includes known partners', () => {
    const names = PARTNERS.map(p => p.name);
    expect(names).toContain('Accenture');
    expect(names).toContain('Telefónica');
    expect(names).toContain('Cabify');
  });
});
