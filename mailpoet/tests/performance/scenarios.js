/**
 * Internal dependencies
 */
import { newsletterListing } from './tests/newsletter-listing.js';
import { subscribersListing } from './tests/subscribers-listing.js';
import { settingsBasic } from './tests/settings-basic.js';
import { subscribersFiltering } from './tests/subscribers-filtering.js';
import { subscribersAdding } from './tests/subscribers-adding.js';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';
import { scenario } from './config.js';

// Scenarios, Thresholds and Tags
export let options = {
  scenarios: {},
  thresholds: {
    browser_dom_content_loaded: ['p(95) < 2000'],
    browser_first_contentful_paint: ['max < 2000'],
    browser_first_meaningful_paint: ['max < 3000'],
    browser_first_paint: ['max < 2000'],
    browser_loaded: ['p(95) < 3000'],
    http_req_duration: ['p(95) < 2500'],
    http_req_receiving: ['p(95) < 2500'],
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
    maxDuration: '2m',
    exec: 'pullRequests',
  },
  nightlytests: {
    executor: 'per-vu-iterations',
    vus: 1,
    iterations: 3,
    maxDuration: '5m',
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
export function pullRequests() {
  newsletterListing();
  subscribersListing();
  settingsBasic();
  subscribersFiltering();
  subscribersAdding();
}

// All the tests ran for a nightly testing
export function nightly() {
  // TBD
}

// HTML report data saved in performance folder
export function handleSummary(data) {
  return {
    'tests/performance/_output/k6report.html': htmlReport(data),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}
