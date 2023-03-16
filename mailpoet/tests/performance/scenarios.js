/**
 * Internal dependencies
 */
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';
import { scenario } from './config.js';
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

// Scenarios, Thresholds and Tags
export let options = {
  scenarios: {},
  thresholds: {
    browser_dom_content_loaded: ['p(90) < 5000'],
    browser_first_contentful_paint: ['p(90) < 5000'],
    browser_first_meaningful_paint: ['p(90) < 5000'],
    browser_first_paint: ['p(90) < 5000'],
    browser_loaded: ['p(90) < 5000'],
    http_req_duration: ['p(90) < 15000'],
    http_req_receiving: ['p(90) < 15000'],
    checks: ['rate==1.0'],
  },
  tags: {
    name: 'value',
  },
};

// Separate scenarios for separate testing needs
let scenarios = {
  pullrequests: {
    executor: 'per-vu-iterations',
    vus: 1,
    iterations: 1,
    maxDuration: '5m',
    exec: 'pullRequests',
  },
  nightlytests: {
    executor: 'per-vu-iterations',
    vus: 1,
    iterations: 3,
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

// All the tests ran for pull requests
export async function pullRequests() {
  await newsletterListing();
  await subscribersListing();
  await settingsBasic();
  await subscribersFiltering();
  await subscribersAdding();
  await formsAdding();
  await newsletterSearching();
  await newsletterSending();
  await listsViewSubscribers();
  await listsComplexSegment();
  await newsletterStatistics();
}

// All the tests ran for a nightly testing
export async function nightly() {
  // TBD
}

// HTML report data saved in performance folder
export function handleSummary(data) {
  return {
    'tests/performance/_output/k6report.html': htmlReport(data),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}
