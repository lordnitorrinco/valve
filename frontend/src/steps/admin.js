/**
 * Admin panel — Submission list with detail popup and CV download.
 *
 * Accessible at /admin. Fetches data from GET /api/submissions,
 * displays a clean list of names. Clicking a row opens a modal
 * with all submission fields and a CV download button.
 */

import { registerView } from '../framework/router.js';
import { el } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

function formatBirthDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/** Label map for human-readable field display in the detail modal. */
const FIELD_LABELS = {
  email:                 'Email',
  phone:                 'Teléfono',
  gender:                'Género',
  country_of_residence:  'País de residencia',
  nationality:           'Nacionalidad',
  nationality_other:     'Otra nacionalidad',
  work_permit:           'Permiso de trabajo',
  relocation:            'Disponibilidad reubicación',
  date_of_birth:         'Fecha de nacimiento',
  education:             'Formación',
  study_area:            'Área de estudio',
  graduation_year:       'Año de graduación',
  english_level:         'Nivel de inglés',
  situation:             'Situación laboral',
  job_role:              'Puesto actual',
  tech_years_experience: 'Años exp. tech',
  linkedin_url:          'LinkedIn',
  willing_to_train:      'Disponibilidad formación',
  utm_source:            'UTM Source',
  lead_id:               'Lead ID',
};

/**
 * Build and display the detail modal for a submission.
 */
function showDetail(s) {
  // Backdrop
  const backdrop = el('div', { className: 'modal-backdrop' });
  const modal = el('div', { className: 'modal' });

  // Close button
  const closeBtn = el('button', { className: 'modal-close', onClick: () => backdrop.remove() });
  closeBtn.innerHTML = SVG.x;
  modal.appendChild(closeBtn);

  // Header
  const header = el('div', { className: 'modal-header' });
  header.appendChild(el('h2', null, `${s.first_name} ${s.last_name}`));
  header.appendChild(el('span', { className: 'modal-date' }, formatDate(s.created_at)));
  modal.appendChild(header);

  // Fields grid
  const grid = el('div', { className: 'modal-grid' });

  for (const [key, label] of Object.entries(FIELD_LABELS)) {
    let value = s[key];
    if (value == null || value === '') continue;
    if (key === 'date_of_birth') value = formatBirthDate(value);
    if (key === 'phone') value = `${s.phone_prefix || '+34'} ${value}`;
    if (key === 'linkedin_url') {
      const link = el('a', { href: value, target: '_blank', rel: 'noopener' }, value);
      const row = el('div', { className: 'modal-field' },
        el('span', { className: 'modal-label' }, label),
      );
      row.appendChild(link);
      grid.appendChild(row);
      continue;
    }
    grid.appendChild(el('div', { className: 'modal-field' },
      el('span', { className: 'modal-label' }, label),
      el('span', { className: 'modal-value' }, value),
    ));
  }
  modal.appendChild(grid);

  // CV download button
  if (s.cv_filename) {
    const dlBtn = el('a', {
      href: `/api/submissions/${s.id}/cv`,
      className: 'modal-cv-btn',
    });
    dlBtn.innerHTML = SVG.fileIcon + ' Descargar CV';
    modal.appendChild(dlBtn);
  }

  backdrop.appendChild(modal);
  document.body.appendChild(backdrop);

  // Close on backdrop click
  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) backdrop.remove();
  });

  // Close on Escape
  const onKey = (e) => {
    if (e.key === 'Escape') { backdrop.remove(); document.removeEventListener('keydown', onKey); }
  };
  document.addEventListener('keydown', onKey);
}

registerView('admin', function renderAdmin() {
  const page = el('div', { className: 'admin-page' });

  // Header
  const header = el('div', { className: 'admin-header' });
  header.appendChild(el('img', {
    src: '/assets/logo-evolve.svg', alt: 'Evolve Academy',
    style: 'width:1.5rem;height:auto'
  }));
  header.appendChild(el('h1', null, 'Panel de solicitudes'));
  page.appendChild(header);

  // Stats bar
  const statsBar = el('div', { className: 'admin-stats' });
  page.appendChild(statsBar);

  // List container
  const listWrap = el('div', { className: 'admin-list-wrap' });
  const loading = el('div', { className: 'admin-loading' },
    el('div', { className: 'spinner' }),
    'Cargando solicitudes...'
  );
  listWrap.appendChild(loading);
  page.appendChild(listWrap);

  fetch('/api/submissions')
    .then(res => res.json())
    .then(data => {
      listWrap.innerHTML = '';

      const submissions = data.submissions || [];

      // Stats — only total count (CV is mandatory)
      statsBar.innerHTML = '';
      const statTotal = el('div', { className: 'admin-stat' });
      statTotal.appendChild(el('span', { className: 'admin-stat-num' }, String(submissions.length)));
      statTotal.appendChild(el('span', { className: 'admin-stat-label' }, 'Solicitudes'));
      statsBar.appendChild(statTotal);

      if (submissions.length === 0) {
        listWrap.appendChild(el('div', { className: 'admin-empty' },
          'No hay solicitudes todavía.'
        ));
        return;
      }

      // Simple table: #, Name, Date — click opens detail popup
      const table = el('table', { className: 'admin-table' });
      const thead = el('thead');
      const hr = el('tr');
      ['#', 'Nombre', 'Fecha'].forEach(h => hr.appendChild(el('th', null, h)));
      thead.appendChild(hr);
      table.appendChild(thead);

      const tbody = el('tbody');
      submissions.forEach(s => {
        const row = el('tr', { onClick: () => showDetail(s) });
        row.appendChild(el('td', null, String(s.id)));
        row.appendChild(el('td', { className: 'name-cell' }, `${s.first_name} ${s.last_name}`));
        row.appendChild(el('td', { className: 'date-cell' }, formatDate(s.created_at)));
        tbody.appendChild(row);
      });

      table.appendChild(tbody);
      listWrap.appendChild(table);
    })
    .catch(err => {
      listWrap.innerHTML = '';
      listWrap.appendChild(el('div', { className: 'admin-empty admin-error' },
        'Error al cargar las solicitudes: ' + err.message
      ));
    });

  return page;
});
