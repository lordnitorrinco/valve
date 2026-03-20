/**
 * Data-integrity tests for `data/options.js` static lists and sets.
 * Ensures step ordering, killer rules, dropdown options, and phone prefixes
 * stay consistent with the multi-step form and validation logic.
 */

import { describe, it, expect } from 'vitest';
import {
  STEP_NAMES, FORM_STEPS, STEP_VIEWS, STEP_ORDER,
  COUNTRIES, EDUCATION_LEVELS, KILLER_EDUCATION, KILLER_ENGLISH,
  EDUCATION_NO_AREA, EDUCATION_NO_YEAR, ENGLISH_LEVELS,
  STUDY_AREAS, GRADUATION_YEARS, SITUATIONS, NO_JOB_ROLE,
  PHONE_PREFIXES,
} from '../src/data/options.js';

// Step names, form vs. full view sets, and numeric ordering
describe('options data integrity', () => {
  // Wizard has five named steps
  it('STEP_NAMES has 5 steps', () => {
    expect(STEP_NAMES).toHaveLength(5);
  });

  // Form flow excludes intro; includes consent
  it('FORM_STEPS contains all form step views', () => {
    expect(FORM_STEPS.has('personal')).toBe(true);
    expect(FORM_STEPS.has('personal2')).toBe(true);
    expect(FORM_STEPS.has('education')).toBe(true);
    expect(FORM_STEPS.has('experience')).toBe(true);
    expect(FORM_STEPS.has('consent')).toBe(true);
    expect(FORM_STEPS.has('intro')).toBe(false);
  });

  // Result screens exist alongside form steps
  it('STEP_VIEWS is a superset of FORM_STEPS plus result views', () => {
    for (const step of FORM_STEPS) {
      expect(STEP_VIEWS.has(step)).toBe(true);
    }
    expect(STEP_VIEWS.has('rejected')).toBe(true);
    expect(STEP_VIEWS.has('killer')).toBe(true);
  });

  // Progress indicator mapping 1–5
  it('STEP_ORDER maps form steps to numbers 1-5', () => {
    expect(STEP_ORDER.personal).toBe(1);
    expect(STEP_ORDER.consent).toBe(5);
  });

  // Country dropdown includes Spain, Mexico, and fallback
  it('COUNTRIES contains Spain and common countries', () => {
    expect(COUNTRIES).toContain('España');
    expect(COUNTRIES).toContain('México');
    expect(COUNTRIES).toContain('Otro');
    expect(COUNTRIES.length).toBeGreaterThan(10);
  });

  // Education list spans “none” through doctorate
  it('EDUCATION_LEVELS covers range from none to PhD', () => {
    expect(EDUCATION_LEVELS[0]).toBe('Sin formación');
    expect(EDUCATION_LEVELS[EDUCATION_LEVELS.length - 1]).toBe('Doctorado');
  });

  // Auto-reject set for very low formal education
  it('KILLER_EDUCATION blocks lower education levels', () => {
    expect(KILLER_EDUCATION.has('Sin formación')).toBe(true);
    expect(KILLER_EDUCATION.has('ESO')).toBe(true);
    expect(KILLER_EDUCATION.has('Grado Universitario')).toBe(false);
  });

  // Beginner English levels trigger killer flow
  it('KILLER_ENGLISH blocks A1 and A2', () => {
    expect(KILLER_ENGLISH.has('A1')).toBe(true);
    expect(KILLER_ENGLISH.has('A2')).toBe(true);
    expect(KILLER_ENGLISH.has('B1')).toBe(false);
    expect(KILLER_ENGLISH.has('C1')).toBe(false);
  });

  // Study area not shown for lowest tiers
  it('EDUCATION_NO_AREA excludes basic levels from study area', () => {
    expect(EDUCATION_NO_AREA.has('Sin formación')).toBe(true);
    expect(EDUCATION_NO_AREA.has('ESO')).toBe(true);
    expect(EDUCATION_NO_AREA.has('Bachillerato')).toBe(true);
    expect(EDUCATION_NO_AREA.has('FP Grado Superior')).toBe(false);
  });

  // CEFR ladder A1–C2
  it('ENGLISH_LEVELS has 6 levels A1-C2', () => {
    expect(ENGLISH_LEVELS).toHaveLength(6);
    expect(ENGLISH_LEVELS[0].value).toBe('A1');
    expect(ENGLISH_LEVELS[5].value).toBe('C2');
  });

  // Study area options are value/label pairs (same string)
  it('STUDY_AREAS are objects with value and label', () => {
    expect(STUDY_AREAS.length).toBeGreaterThan(5);
    STUDY_AREAS.forEach(area => {
      expect(area).toHaveProperty('value');
      expect(area).toHaveProperty('label');
      expect(area.value).toBe(area.label);
    });
  });

  // Graduation years include current year, “still studying”, and legacy bucket
  it('GRADUATION_YEARS includes current year range and special entries', () => {
    const currentYear = new Date().getFullYear();
    expect(GRADUATION_YEARS).toContain(String(currentYear));
    expect(GRADUATION_YEARS[GRADUATION_YEARS.length - 1]).toBe('Aún cursando');
    expect(GRADUATION_YEARS.some(y => y.startsWith('Antes de'))).toBe(true);
  });

  // Employment situation enum size and key values
  it('SITUATIONS has 4 options', () => {
    expect(SITUATIONS).toHaveLength(4);
    const values = SITUATIONS.map(s => s.value);
    expect(values).toContain('Empleado');
    expect(values).toContain('Estudiando');
    expect(values).toContain('Desempleado');
  });

  // Situations where job role is optional
  it('NO_JOB_ROLE excludes unemployed from requiring job role', () => {
    expect(NO_JOB_ROLE.has('Estudiando')).toBe(true);
    expect(NO_JOB_ROLE.has('Desempleado')).toBe(true);
    expect(NO_JOB_ROLE.has('Empleado')).toBe(false);
  });

  // Phone prefix picker metadata for UI
  it('PHONE_PREFIXES all have code, flag, and country', () => {
    expect(PHONE_PREFIXES.length).toBeGreaterThan(10);
    PHONE_PREFIXES.forEach(p => {
      expect(p.code).toMatch(/^\+\d+$/);
      expect(p.flag).toBeTruthy();
      expect(p.country).toBeTruthy();
    });
  });

  // Default locale: Spain first in list
  it('Spain is the first phone prefix', () => {
    expect(PHONE_PREFIXES[0].code).toBe('+34');
    expect(PHONE_PREFIXES[0].country).toBe('España');
  });
});
