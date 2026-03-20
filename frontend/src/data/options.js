/**
 * Static data and configuration constants for the admission form.
 *
 * Centralizes all dropdown options, step definitions, validation
 * sets (killer conditions), and phone prefix data so they can be
 * imported by any module without duplication.
 */

// ── Step / View configuration ────────────────────────────────

/** Step labels displayed in the progress bar */
export const STEP_NAMES = ['Contacto', 'Ubicación', 'Formación', 'Experiencia', 'Envío'];

/** Views that are part of the multi-step form (show progress bar) */
export const FORM_STEPS = new Set(['personal', 'personal2', 'education', 'experience', 'consent']);

/** All views with slide transition animations */
export const STEP_VIEWS = new Set(['personal', 'personal2', 'education', 'experience', 'consent', 'rejected', 'killer']);

/** Maps form step view names to their 1-based position number */
export const STEP_ORDER = { personal: 1, personal2: 2, education: 3, experience: 4, consent: 5 };

// ── Country options ──────────────────────────────────────────

export const COUNTRIES = [
  'España', 'Alemania', 'Argentina', 'Brasil', 'Chile', 'China', 'Colombia',
  'Ecuador', 'Estados Unidos', 'Francia', 'India', 'Italia', 'Japón',
  'Marruecos', 'México', 'Perú', 'Portugal', 'Reino Unido', 'Venezuela', 'Otro'
];

// ── Education options ────────────────────────────────────────

export const EDUCATION_LEVELS = [
  'Sin formación', 'ESO', 'Bachillerato', 'FP Grado Medio',
  'FP Grado Superior', 'Grado Universitario', 'Máster / Postgrado', 'Doctorado'
];

/** Education levels that disqualify the applicant (killer condition) */
export const KILLER_EDUCATION = new Set(['Sin formación', 'ESO', 'Bachillerato', 'FP Grado Medio']);

/** English levels that disqualify the applicant (killer condition) */
export const KILLER_ENGLISH = new Set(['A1', 'A2']);

/** Education levels that don't require a study area selection */
export const EDUCATION_NO_AREA = new Set(['Sin formación', 'ESO', 'Bachillerato']);

/** Education levels that don't require a graduation year */
export const EDUCATION_NO_YEAR = new Set(['Sin formación']);

export const ENGLISH_LEVELS = [
  { value: 'A1', label: 'A1 - Principiante' },
  { value: 'A2', label: 'A2 - Elemental' },
  { value: 'B1', label: 'B1 - Intermedio' },
  { value: 'B2', label: 'B2 - Intermedio alto' },
  { value: 'C1', label: 'C1 - Avanzado' },
  { value: 'C2', label: 'C2 - Nativo / Bilingüe' }
];

export const STUDY_AREAS = [
  'Artes y Humanidades', 'Ciencias Sociales', 'Derecho', 'Economía y Empresa',
  'Ciencias Naturales y Exactas', 'Ingeniería y Arquitectura',
  'Informática y Tecnologías de la información', 'Ciencias de la Salud',
  'Educación y Pedagogía', 'Actividades Físicas y Deporte',
  'Ciencias agrarias y Medioambientales', 'Servicios y Turismo',
  'Formación Profesional y Oficios'
].map(s => ({ value: s, label: s }));

/** Dynamically generated list of graduation years (current+4 down to 44 years ago) */
export const GRADUATION_YEARS = (() => {
  const current = new Date().getFullYear() + 4;
  const years = [];
  for (let i = 0; i < 44; i++) years.push(String(current - i));
  years.push('Antes de ' + (current - 43));
  years.push('Aún cursando');
  return years;
})();

// ── Employment options ───────────────────────────────────────

export const SITUATIONS = [
  { value: 'Empleado', label: 'Empleado' },
  { value: 'Estudiando', label: 'Estudiando' },
  { value: 'En búsqueda de empleo', label: 'En búsqueda de empleo' },
  { value: 'Desempleado', label: 'Desempleado' }
];

/** Employment situations that don't require a current job role */
export const NO_JOB_ROLE = new Set(['Estudiando', 'En búsqueda de empleo', 'Desempleado']);

// ── Phone prefix data ────────────────────────────────────────

/** International phone prefixes with flag emoji and country name */
export const PHONE_PREFIXES = [
  { code: '+34', flag: '🇪🇸', country: 'España' },
  { code: '+1', flag: '🇺🇸', country: 'EE.UU.' },
  { code: '+44', flag: '🇬🇧', country: 'Reino Unido' },
  { code: '+33', flag: '🇫🇷', country: 'Francia' },
  { code: '+49', flag: '🇩🇪', country: 'Alemania' },
  { code: '+39', flag: '🇮🇹', country: 'Italia' },
  { code: '+351', flag: '🇵🇹', country: 'Portugal' },
  { code: '+52', flag: '🇲🇽', country: 'México' },
  { code: '+54', flag: '🇦🇷', country: 'Argentina' },
  { code: '+57', flag: '🇨🇴', country: 'Colombia' },
  { code: '+56', flag: '🇨🇱', country: 'Chile' },
  { code: '+51', flag: '🇵🇪', country: 'Perú' },
  { code: '+593', flag: '🇪🇨', country: 'Ecuador' },
  { code: '+86', flag: '🇨🇳', country: 'China' },
  { code: '+91', flag: '🇮🇳', country: 'India' },
  { code: '+81', flag: '🇯🇵', country: 'Japón' },
  { code: '+212', flag: '🇲🇦', country: 'Marruecos' }
];
