import { initApp, goTo } from './framework/router.js';

import './steps/0-intro.js';
import './steps/1-contact.js';
import './steps/2-location.js';
import './steps/3-education.js';
import './steps/4-experience.js';
import './steps/5-consent.js';
import './steps/results.js';

initApp();
goTo('intro');
