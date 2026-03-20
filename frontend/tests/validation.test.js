import { describe, it, expect, beforeEach } from 'vitest';
import { state } from '../src/framework/store.js';
import {
  validatePersonal,
  validatePersonal2,
  validateEducation,
  validateExperience,
} from '../src/services/validation.js';

function resetState(overrides = {}) {
  Object.assign(state.formData, {
    firstName: '', lastName: '', gender: '', email: '', phone: '',
    phonePrefix: '+34', countryOfResidence: '', nationality: '',
    nationalityOther: '', workPermit: '', relocation: '', dateOfBirth: '',
    education: '', studyArea: '', graduationYear: '', englishLevel: '',
    situation: '', jobRole: '', techYearsExperience: '', linkedinUrl: '',
    willingToTrain: '',
  }, overrides);
  state.cvFile = null;
  state.errors = {};
}

// ── validatePersonal ─────────────────────────────────────────

describe('validatePersonal', () => {
  beforeEach(() => resetState());

  it('returns errors for all empty required fields', () => {
    const errors = validatePersonal();
    expect(errors).toHaveProperty('firstName');
    expect(errors).toHaveProperty('lastName');
    expect(errors).toHaveProperty('gender');
    expect(errors).toHaveProperty('email');
    expect(errors).toHaveProperty('phone');
  });

  it('returns no errors with valid data', () => {
    resetState({
      firstName: 'Pablo', lastName: 'García', gender: 'hombre',
      email: 'pablo@test.com', phone: '600123456',
    });
    const errors = validatePersonal();
    expect(Object.keys(errors)).toHaveLength(0);
  });

  it('detects invalid email format', () => {
    resetState({
      firstName: 'A', lastName: 'B', gender: 'hombre',
      email: 'not-an-email', phone: '600123456',
    });
    const errors = validatePersonal();
    expect(errors).toHaveProperty('email');
  });

  it('detects phone too short', () => {
    resetState({
      firstName: 'A', lastName: 'B', gender: 'hombre',
      email: 'a@b.com', phone: '123',
    });
    const errors = validatePersonal();
    expect(errors).toHaveProperty('phone');
  });

  it('detects repeated digit phone numbers', () => {
    resetState({
      firstName: 'A', lastName: 'B', gender: 'hombre',
      email: 'a@b.com', phone: '1111111111',
    });
    const errors = validatePersonal();
    expect(errors).toHaveProperty('phone');
  });

  it('accepts phone with spaces and dashes', () => {
    resetState({
      firstName: 'A', lastName: 'B', gender: 'hombre',
      email: 'a@b.com', phone: '600 123 456',
    });
    const errors = validatePersonal();
    expect(errors).not.toHaveProperty('phone');
  });

  it('trims whitespace-only firstName', () => {
    resetState({
      firstName: '   ', lastName: 'B', gender: 'hombre',
      email: 'a@b.com', phone: '600123456',
    });
    const errors = validatePersonal();
    expect(errors).toHaveProperty('firstName');
  });
});

// ── validatePersonal2 ────────────────────────────────────────

describe('validatePersonal2', () => {
  beforeEach(() => resetState());

  it('returns errors for all empty required fields', () => {
    const errors = validatePersonal2();
    expect(errors).toHaveProperty('countryOfResidence');
    expect(errors).toHaveProperty('nationality');
    expect(errors).toHaveProperty('relocation');
    expect(errors).toHaveProperty('dateOfBirth');
  });

  it('returns no errors with valid data', () => {
    resetState({
      countryOfResidence: 'España', nationality: 'española',
      relocation: 'si', dateOfBirth: '1995-06-15',
    });
    const errors = validatePersonal2();
    expect(Object.keys(errors)).toHaveLength(0);
  });

  it('requires nationalityOther when nationality is "otro"', () => {
    resetState({
      countryOfResidence: 'España', nationality: 'otro',
      relocation: 'si', dateOfBirth: '1995-06-15',
      nationalityOther: '',
    });
    const errors = validatePersonal2();
    expect(errors).toHaveProperty('nationalityOther');
  });

  it('requires workPermit when nationality is "otro"', () => {
    resetState({
      countryOfResidence: 'España', nationality: 'otro',
      relocation: 'si', dateOfBirth: '1995-06-15',
      nationalityOther: 'Francesa', workPermit: '',
    });
    const errors = validatePersonal2();
    expect(errors).toHaveProperty('workPermit');
  });

  it('does not require nationalityOther when nationality is not "otro"', () => {
    resetState({
      countryOfResidence: 'España', nationality: 'española',
      relocation: 'si', dateOfBirth: '1995-06-15',
    });
    const errors = validatePersonal2();
    expect(errors).not.toHaveProperty('nationalityOther');
    expect(errors).not.toHaveProperty('workPermit');
  });
});

// ── validateEducation ────────────────────────────────────────

describe('validateEducation', () => {
  beforeEach(() => resetState());

  it('requires education and englishLevel', () => {
    const errors = validateEducation();
    expect(errors).toHaveProperty('education');
    expect(errors).toHaveProperty('englishLevel');
  });

  it('returns no errors with valid data', () => {
    resetState({
      education: 'Grado Universitario', studyArea: 'Informática',
      graduationYear: '2020', englishLevel: 'B2',
    });
    const errors = validateEducation();
    expect(Object.keys(errors)).toHaveLength(0);
  });

  it('does not require studyArea for basic education levels', () => {
    resetState({
      education: 'ESO', englishLevel: 'B1',
    });
    const errors = validateEducation();
    expect(errors).not.toHaveProperty('studyArea');
  });

  it('requires studyArea for higher education', () => {
    resetState({
      education: 'Grado Universitario', graduationYear: '2020',
      englishLevel: 'B2', studyArea: '',
    });
    const errors = validateEducation();
    expect(errors).toHaveProperty('studyArea');
  });

  it('does not require graduationYear for "Sin formación"', () => {
    resetState({
      education: 'Sin formación', englishLevel: 'B1',
    });
    const errors = validateEducation();
    expect(errors).not.toHaveProperty('graduationYear');
  });
});

// ── validateExperience ───────────────────────────────────────

describe('validateExperience', () => {
  beforeEach(() => resetState());

  it('requires situation, cvFile, willingToTrain, techYearsExperience', () => {
    const errors = validateExperience();
    expect(errors).toHaveProperty('situation');
    expect(errors).toHaveProperty('cvFile');
    expect(errors).toHaveProperty('willingToTrain');
    expect(errors).toHaveProperty('techYearsExperience');
  });

  it('returns no errors with valid data', () => {
    state.cvFile = new File(['content'], 'cv.pdf');
    resetState({
      situation: 'Empleado', jobRole: 'Developer',
      techYearsExperience: '3', willingToTrain: 'si',
    });
    state.cvFile = new File(['content'], 'cv.pdf');
    const errors = validateExperience();
    expect(Object.keys(errors)).toHaveLength(0);
  });

  it('requires jobRole when employed', () => {
    state.cvFile = new File(['x'], 'cv.pdf');
    resetState({
      situation: 'Empleado', jobRole: '',
      techYearsExperience: '2', willingToTrain: 'si',
    });
    state.cvFile = new File(['x'], 'cv.pdf');
    const errors = validateExperience();
    expect(errors).toHaveProperty('jobRole');
  });

  it('does not require jobRole for students', () => {
    state.cvFile = new File(['x'], 'cv.pdf');
    resetState({
      situation: 'Estudiando', techYearsExperience: '0',
      willingToTrain: 'si',
    });
    state.cvFile = new File(['x'], 'cv.pdf');
    const errors = validateExperience();
    expect(errors).not.toHaveProperty('jobRole');
  });

  it('rejects negative experience years', () => {
    state.cvFile = new File(['x'], 'cv.pdf');
    resetState({
      situation: 'Estudiando', techYearsExperience: '-1',
      willingToTrain: 'si',
    });
    state.cvFile = new File(['x'], 'cv.pdf');
    const errors = validateExperience();
    expect(errors).toHaveProperty('techYearsExperience');
  });

  it('rejects experience over 50', () => {
    state.cvFile = new File(['x'], 'cv.pdf');
    resetState({
      situation: 'Estudiando', techYearsExperience: '51',
      willingToTrain: 'si',
    });
    state.cvFile = new File(['x'], 'cv.pdf');
    const errors = validateExperience();
    expect(errors).toHaveProperty('techYearsExperience');
  });

  it('rejects more than 1 decimal in experience', () => {
    state.cvFile = new File(['x'], 'cv.pdf');
    resetState({
      situation: 'Estudiando', techYearsExperience: '3.55',
      willingToTrain: 'si',
    });
    state.cvFile = new File(['x'], 'cv.pdf');
    const errors = validateExperience();
    expect(errors).toHaveProperty('techYearsExperience');
  });

  it('accepts single decimal experience', () => {
    state.cvFile = new File(['x'], 'cv.pdf');
    resetState({
      situation: 'Estudiando', techYearsExperience: '3.5',
      willingToTrain: 'si',
    });
    state.cvFile = new File(['x'], 'cv.pdf');
    const errors = validateExperience();
    expect(errors).not.toHaveProperty('techYearsExperience');
  });
});
