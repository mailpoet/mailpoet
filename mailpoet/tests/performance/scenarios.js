/**
 * Internal dependencies
 */
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';
import { scenario, k6CloudID, projectName } from './config.js';
import { newsletterListing } from './tests/newsletter-listing.js';
import { subscribersListing } from './tests/subscribers-listing.js';
import { settingsBasic } from './tests/settings-basic.js';
import { subscribersFiltering } from './tests/subscribers-filtering.js';
import { subscribersAdding } from './tests/subscribers-adding.js';
import { formsAdding } from './tests/forms-adding.js';
import { newsletterSearching } from './tests/newsletter-searching.js';
import { newsletterSending } from './tests/newsletter-sending.js';
import { listsViewSubscribers } from './tests/lists-view-subscribers.js';
import { listsComplexSegment } from './tests/lists-complex-segment.js';
import { newsletterStatistics } from './tests/newsletter-statistics.js';
import { onboardingWizard } from './tests/onboarding-wizard.js';
import { subscribersTrashingRestoring } from './tests/subscribers-trashing-restoring.js';
import { automationCreateCustom } from './tests/automation-create-custom.js';

// Scenarios, Thresholds, Tags and Project ID used for K6 Cloud
export let options = {
  ext: {
    loadimpact: {
      projectID: k6CloudID,
      name: projectName,
    },
  },
  scenarios: {},
  thresholds: {
    browser_web_vital_lcp: ['p(75) < 8000'],
    browser_web_vital_fid: ['p(75) < 300'],
    browser_web_vital_cls: ['p(75) < 0.60'],
    browser_web_vital_ttfb: ['p(75) < 4000'],
    browser_web_vital_fcp: ['p(75) < 4000'],
    browser_web_vital_inp: ['p(75) < 300'],
    checks: ['rate==1.0'],
  },
  tags: {
    name: projectName,
  },
};

// Separate scenarios for separate testing needs
let scenarios = {
  pullrequests: {
    executor: 'per-vu-iterations',
    options: {
      browser: {
        type: 'chromium', // chromium is the only supported browser type
      },
    },
    vus: 1,
    iterations: 1,
    maxDuration: '10m',
    exec: 'pullRequests',
  },
  nightlytests: {
    executor: 'per-vu-iterations',
    options: {
      browser: {
        type: 'chromium',
      },
    },
    vus: 1,
    iterations: 1,
    maxDuration: '15m',
    exec: 'nightly',
  },
};

// Scenario execution setup
if (scenario) {
  // Use just a single scenario if `--env scenario=whatever` is used
  options.scenarios[scenario] = scenarios[scenario];
} else {
  // Use all scenarios
  options.scenarios = scenarios;
}

// Run those tests against a pull request build
export async function pullRequests() {
  await onboardingWizard();
  await newsletterListing();
  await newsletterSearching();
  await listsViewSubscribers();
  await subscribersListing();
  await subscribersFiltering();
  await subscribersAdding();
  await formsAdding();
}

// Run those tests against trunk in a nightly build
export async function nightly() {
  await newsletterListing();
  await newsletterStatistics();
  await newsletterSearching();
  await newsletterSending();
  await automationCreateCustom();
  await subscribersListing();
  await subscribersFiltering();
  await subscribersAdding();
  await subscribersTrashingRestoring();
  await listsViewSubscribers();
  await listsComplexSegment();
  await settingsBasic();
  await formsAdding();
}

// HTML report data saved in performance folder
export function handleSummary(data) {
  return {
    'tests/performance/_output/k6report.html': htmlReport(data),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}
