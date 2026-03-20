import { state } from '../framework/store.js';
import { EDUCATION_NO_AREA, EDUCATION_NO_YEAR, NO_JOB_ROLE } from '../data/options.js';

export function validatePersonal() {
  const errors = {};
  const d = state.formData;

  if (!d.firstName?.trim())  errors.firstName = 'El nombre es obligatorio';
  if (!d.lastName?.trim())   errors.lastName = 'Los apellidos son obligatorios';
  if (!d.gender)             errors.gender = 'Selecciona tu género';

  if (!d.email?.trim()) {
    errors.email = 'El email es obligatorio';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) {
    errors.email = 'Introduce un email válido';
  }

  if (!d.phone?.trim()) {
    errors.phone = 'El teléfono es obligatorio';
  } else {
    const cleaned = d.phone.replace(/[\s\-+]/g, '');
    if (cleaned.length < 7)          errors.phone = 'Introduce un número válido';
    else if (/^(\d)\1+$/.test(cleaned)) errors.phone = 'Introduce un número real';
  }

  return errors;
}

export function validatePersonal2() {
  const errors = {};
  const d = state.formData;

  if (!d.countryOfResidence) errors.countryOfResidence = 'Selecciona un país';
  if (!d.nationality)        errors.nationality = 'Selecciona tu nacionalidad';
  if (d.nationality === 'otro' && !d.nationalityOther?.trim()) errors.nationalityOther = 'Indica tu nacionalidad';
  if (d.nationality === 'otro' && !d.workPermit)               errors.workPermit = 'Selecciona una opción';
  if (!d.relocation)         errors.relocation = 'Selecciona una opción';
  if (!d.dateOfBirth)        errors.dateOfBirth = 'Indica tu fecha de nacimiento';

  return errors;
}

export function validateEducation() {
  const errors = {};
  const d = state.formData;

  if (!d.education) errors.education = 'Selecciona tu nivel de estudios';
  if (d.education && !EDUCATION_NO_AREA.has(d.education) && !d.studyArea?.trim())
    errors.studyArea = 'Indica tu área de estudios';
  if (d.education && !EDUCATION_NO_YEAR.has(d.education) && !d.graduationYear)
    errors.graduationYear = 'Selecciona el año';
  if (!d.englishLevel) errors.englishLevel = 'Selecciona tu nivel';

  return errors;
}

export function validateExperience() {
  const errors = {};
  const d = state.formData;

  if (!d.situation) errors.situation = 'Selecciona tu situación';
  if (d.situation && !NO_JOB_ROLE.has(d.situation) && !d.jobRole?.trim())
    errors.jobRole = 'Indica tu puesto';
  if (!state.cvFile)       errors.cvFile = 'Sube tu CV';
  if (!d.willingToTrain)   errors.willingToTrain = 'Selecciona una opción';

  const years = d.techYearsExperience?.trim();
  if (!years) {
    errors.techYearsExperience = 'Indica los años de experiencia (0-50)';
  } else {
    const num = parseFloat(years);
    if (isNaN(num) || num < 0 || num > 50)
      errors.techYearsExperience = 'Indica los años de experiencia (0-50)';
    else if (years.includes('.') && years.split('.')[1].length > 1)
      errors.techYearsExperience = 'Máximo 1 decimal';
  }

  return errors;
}
