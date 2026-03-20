/**
 * Admin panel — Lists all admission submissions with CV download.
 *
 * Accessible at /admin. Fetches data from GET /api/submissions,
 * displays a responsive table with decrypted email/phone,
 * and provides download links for uploaded CVs.
 */

import { registerView } from '../framework/router.js';
import { el } from '../framework/createElement.js';
import { SVG } from '../ui/icons.js';

/**
 * Format an ISO date string to a localized short format.
 *
 * @param {string} dateStr  ISO date string (e.g. "2026-03-20 14:30:00")
 * @returns {string}        Formatted date (e.g. "20/03/2026 14:30")
 */
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

/**
 * Create a table cell element.
 *
 * @param {string} text     Cell text content
 * @param {string} className  Optional CSS class
 * @returns {HTMLElement}
 */
function td(text, className) {
  const cell = el('td', className ? { className } : null, text || '—');
  return cell;
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

  // Stats bar (filled after data loads)
  const statsBar = el('div', { className: 'admin-stats' });
  page.appendChild(statsBar);

  // Table container
  const tableWrap = el('div', { className: 'admin-table-wrap' });
  const loading = el('div', { className: 'admin-loading' },
    el('div', { className: 'spinner' }),
    'Cargando solicitudes...'
  );
  tableWrap.appendChild(loading);
  page.appendChild(tableWrap);

  // Fetch and render submissions
  fetch('/api/submissions')
    .then(res => res.json())
    .then(data => {
      tableWrap.innerHTML = '';

      const submissions = data.submissions || [];

      // Stats
      statsBar.innerHTML = '';
      const statTotal = el('div', { className: 'admin-stat' });
      statTotal.appendChild(el('span', { className: 'admin-stat-num' }, String(submissions.length)));
      statTotal.appendChild(el('span', { className: 'admin-stat-label' }, 'Total'));
      statsBar.appendChild(statTotal);

      const withCv = submissions.filter(s => s.cv_filename).length;
      const statCv = el('div', { className: 'admin-stat' });
      statCv.appendChild(el('span', { className: 'admin-stat-num' }, String(withCv)));
      statCv.appendChild(el('span', { className: 'admin-stat-label' }, 'Con CV'));
      statsBar.appendChild(statCv);

      if (submissions.length === 0) {
        tableWrap.appendChild(el('div', { className: 'admin-empty' },
          'No hay solicitudes todavía.'
        ));
        return;
      }

      // Build table
      const table = el('table', { className: 'admin-table' });

      // Header row
      const thead = el('thead');
      const headerRow = el('tr');
      ['#', 'Nombre', 'Email', 'Teléfono', 'País', 'Formación', 'Inglés', 'Situación', 'Fecha', 'CV'].forEach(h => {
        headerRow.appendChild(el('th', null, h));
      });
      thead.appendChild(headerRow);
      table.appendChild(thead);

      // Data rows
      const tbody = el('tbody');
      submissions.forEach(s => {
        const row = el('tr');
        row.appendChild(td(String(s.id)));
        row.appendChild(td(`${s.first_name} ${s.last_name}`));
        row.appendChild(td(s.email, 'email-cell'));
        row.appendChild(td(`${s.phone_prefix} ${s.phone}`));
        row.appendChild(td(s.country_of_residence));
        row.appendChild(td(s.education));
        row.appendChild(td(s.english_level));
        row.appendChild(td(s.situation));
        row.appendChild(td(formatDate(s.created_at)));

        // CV download button or dash
        const cvCell = el('td');
        if (s.cv_filename) {
          const dlBtn = el('a', {
            href: `/api/submissions/${s.id}/cv`,
            className: 'admin-cv-btn',
            title: 'Descargar CV'
          });
          dlBtn.innerHTML = SVG.fileIcon;
          cvCell.appendChild(dlBtn);
        } else {
          cvCell.textContent = '—';
        }
        row.appendChild(cvCell);

        tbody.appendChild(row);
      });
      table.appendChild(tbody);
      tableWrap.appendChild(table);
    })
    .catch(err => {
      tableWrap.innerHTML = '';
      tableWrap.appendChild(el('div', { className: 'admin-empty admin-error' },
        'Error al cargar las solicitudes: ' + err.message
      ));
    });

  return page;
});
