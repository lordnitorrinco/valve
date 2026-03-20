/**
 * Client-side form validation functions.
 *
 * One function per form step, each returning an object of
 * { fieldName: errorMessage } entries. Empty object = valid.
 *
 * These mirror the backend validation rules (double validation)
 * to provide immediate user feedback without a server round-trip.
 */

import { state } from '../framework/store.js';
import { EDUCATION_NO_AREA, EDUCATION_NO_YEAR, NO_JOB_ROLE } from '../data/options.js';

/**
 * Validate Step 1: Contact information.
 * Checks: firstName, lastName, gender, email format, phone format.
 *
 * @returns {object} Field errors (empty if all valid)
 */
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

/**
 * Validate Step 2: Location and personal details.
 * Checks: country, nationality (with conditional "other" fields),
 * relocation willingness, date of birth.
 *
 * @returns {object} Field errors
 */
export function validatePersonal2() {
  const errors = {};
  const d = state.formData;

  if (!d.countryOfResidence) errors.countryOfResidence = 'Selecciona un país';
  if (!d.nationality)        errors.nationality = 'Selecciona tu nacionalidad';

  // Extra fields required when nationality is "otro" (other)
  if (d.nationality === 'otro' && !d.nationalityOther?.trim()) errors.nationalityOther = 'Indica tu nacionalidad';
  if (d.nationality === 'otro' && !d.workPermit)               errors.workPermit = 'Selecciona una opción';

  if (!d.relocation)         errors.relocation = 'Selecciona una opción';
  if (!d.dateOfBirth)        errors.dateOfBirth = 'Indica tu fecha de nacimiento';

  return errors;
}

/**
 * Validate Step 3: Education.
 * Checks: education level, study area (conditional), graduation year
 * (conditional), English level.
 *
 * Study area and graduation year are only required for higher
 * education levels (not for "Sin formación", "ESO", "Bachillerato").
 *
 * @returns {object} Field errors
 */
export function validateEducation() {
  const errors = {};
  const d = state.formData;

  if (!d.education) errors.education = 'Selecciona tu nivel de estudios';

  // Study area is not required for basic education levels
  if (d.education && !EDUCATION_NO_AREA.has(d.education) && !d.studyArea?.trim())
    errors.studyArea = 'Indica tu área de estudios';

  // Graduation year is not required for "Sin formación"
  if (d.education && !EDUCATION_NO_YEAR.has(d.education) && !d.graduationYear)
    errors.graduationYear = 'Selecciona el año';

  if (!d.englishLevel) errors.englishLevel = 'Selecciona tu nivel';

  return errors;
}

/**
 * Validate Step 4: Professional experience.
 * Checks: employment situation, job role (conditional), experience years
 * (0-50, max 1 decimal), CV file, willingness to train.
 *
 * Job role is only required when the user is currently employed.
 *
 * @returns {object} Field errors
 */
export function validateExperience() {
  const errors = {};
  const d = state.formData;

  if (!d.situation) errors.situation = 'Selecciona tu situación';

  // Job role required only for employed users
  if (d.situation && !NO_JOB_ROLE.has(d.situation) && !d.jobRole?.trim())
    errors.jobRole = 'Indica tu puesto';

  if (!state.cvFile)       errors.cvFile = 'Sube tu CV';
  if (!d.willingToTrain)   errors.willingToTrain = 'Selecciona una opción';

  // Tech experience: 0-50 years, max 1 decimal place
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
