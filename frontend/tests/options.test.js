import { describe, it, expect } from 'vitest';
import {
  STEP_NAMES, FORM_STEPS, STEP_VIEWS, STEP_ORDER,
  COUNTRIES, EDUCATION_LEVELS, KILLER_EDUCATION, KILLER_ENGLISH,
  EDUCATION_NO_AREA, EDUCATION_NO_YEAR, ENGLISH_LEVELS,
  STUDY_AREAS, GRADUATION_YEARS, SITUATIONS, NO_JOB_ROLE,
  PHONE_PREFIXES,
} from '../src/data/options.js';

describe('options data integrity', () => {
  it('STEP_NAMES has 5 steps', () => {
    expect(STEP_NAMES).toHaveLength(5);
  });

  it('FORM_STEPS contains all form step views', () => {
    expect(FORM_STEPS.has('personal')).toBe(true);
    expect(FORM_STEPS.has('personal2')).toBe(true);
    expect(FORM_STEPS.has('education')).toBe(true);
    expect(FORM_STEPS.has('experience')).toBe(true);
    expect(FORM_STEPS.has('consent')).toBe(true);
    expect(FORM_STEPS.has('intro')).toBe(false);
  });

  it('STEP_VIEWS is a superset of FORM_STEPS plus result views', () => {
    for (const step of FORM_STEPS) {
      expect(STEP_VIEWS.has(step)).toBe(true);
    }
    expect(STEP_VIEWS.has('rejected')).toBe(true);
    expect(STEP_VIEWS.has('killer')).toBe(true);
  });

  it('STEP_ORDER maps form steps to numbers 1-5', () => {
    expect(STEP_ORDER.personal).toBe(1);
    expect(STEP_ORDER.consent).toBe(5);
  });

  it('COUNTRIES contains Spain and common countries', () => {
    expect(COUNTRIES).toContain('España');
    expect(COUNTRIES).toContain('México');
    expect(COUNTRIES).toContain('Otro');
    expect(COUNTRIES.length).toBeGreaterThan(10);
  });

  it('EDUCATION_LEVELS covers range from none to PhD', () => {
    expect(EDUCATION_LEVELS[0]).toBe('Sin formación');
    expect(EDUCATION_LEVELS[EDUCATION_LEVELS.length - 1]).toBe('Doctorado');
  });

  it('KILLER_EDUCATION blocks lower education levels', () => {
    expect(KILLER_EDUCATION.has('Sin formación')).toBe(true);
    expect(KILLER_EDUCATION.has('ESO')).toBe(true);
    expect(KILLER_EDUCATION.has('Grado Universitario')).toBe(false);
  });

  it('KILLER_ENGLISH blocks A1 and A2', () => {
    expect(KILLER_ENGLISH.has('A1')).toBe(true);
    expect(KILLER_ENGLISH.has('A2')).toBe(true);
    expect(KILLER_ENGLISH.has('B1')).toBe(false);
    expect(KILLER_ENGLISH.has('C1')).toBe(false);
  });

  it('EDUCATION_NO_AREA excludes basic levels from study area', () => {
    expect(EDUCATION_NO_AREA.has('Sin formación')).toBe(true);
    expect(EDUCATION_NO_AREA.has('ESO')).toBe(true);
    expect(EDUCATION_NO_AREA.has('Bachillerato')).toBe(true);
    expect(EDUCATION_NO_AREA.has('FP Grado Superior')).toBe(false);
  });

  it('ENGLISH_LEVELS has 6 levels A1-C2', () => {
    expect(ENGLISH_LEVELS).toHaveLength(6);
    expect(ENGLISH_LEVELS[0].value).toBe('A1');
    expect(ENGLISH_LEVELS[5].value).toBe('C2');
  });

  it('STUDY_AREAS are objects with value and label', () => {
    expect(STUDY_AREAS.length).toBeGreaterThan(5);
    STUDY_AREAS.forEach(area => {
      expect(area).toHaveProperty('value');
      expect(area).toHaveProperty('label');
      expect(area.value).toBe(area.label);
    });
  });

  it('GRADUATION_YEARS includes current year range and special entries', () => {
    const currentYear = new Date().getFullYear();
    expect(GRADUATION_YEARS).toContain(String(currentYear));
    expect(GRADUATION_YEARS[GRADUATION_YEARS.length - 1]).toBe('Aún cursando');
    expect(GRADUATION_YEARS.some(y => y.startsWith('Antes de'))).toBe(true);
  });

  it('SITUATIONS has 4 options', () => {
    expect(SITUATIONS).toHaveLength(4);
    const values = SITUATIONS.map(s => s.value);
    expect(values).toContain('Empleado');
    expect(values).toContain('Estudiando');
    expect(values).toContain('Desempleado');
  });

  it('NO_JOB_ROLE excludes unemployed from requiring job role', () => {
    expect(NO_JOB_ROLE.has('Estudiando')).toBe(true);
    expect(NO_JOB_ROLE.has('Desempleado')).toBe(true);
    expect(NO_JOB_ROLE.has('Empleado')).toBe(false);
  });

  it('PHONE_PREFIXES all have code, flag, and country', () => {
    expect(PHONE_PREFIXES.length).toBeGreaterThan(10);
    PHONE_PREFIXES.forEach(p => {
      expect(p.code).toMatch(/^\+\d+$/);
      expect(p.flag).toBeTruthy();
      expect(p.country).toBeTruthy();
    });
  });

  it('Spain is the first phone prefix', () => {
    expect(PHONE_PREFIXES[0].code).toBe('+34');
    expect(PHONE_PREFIXES[0].country).toBe('España');
  });
});
