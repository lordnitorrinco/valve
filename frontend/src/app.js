/**
 * Application entry point.
 *
 * Imports all step view modules (which self-register via registerView),
 * initializes the app shell (progress bar, content area, honeypot),
 * and navigates to the intro screen.
 */

import { initApp, goTo } from './framework/router.js';

// Each step file calls registerView() on import
import './steps/0-intro.js';
import './steps/1-contact.js';
import './steps/2-location.js';
import './steps/3-education.js';
import './steps/4-experience.js';
import './steps/5-consent.js';
import './steps/results.js';
import './steps/admin.js';

// Bootstrap the application
initApp();

// Route based on URL path — /admin opens the admin panel
const startView = window.location.pathname === '/admin' ? 'admin' : 'intro';
goTo(startView);
