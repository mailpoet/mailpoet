/**
 * Internal dependencies
 */
import { wpLogin } from '../requests/wp-admin/wp-login.js';
import { newsletterListing } from '../requests/wp-admin/newsletter-listing.js';
import { subscribersListing } from '../requests/wp-admin/subscribers-listing.js';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';
import { scenario } from '../config.js';

// Scenarios, Thresholds and Tags
export let options = {
  scenarios: {},
  thresholds: {
    browser_dom_content_loaded: ['p(95) < 1000'],
    browser_first_contentful_paint: ['max < 1000'],
    browser_first_meaningful_paint: ['max < 2000'],
    browser_first_paint: ['max < 1000'],
    browser_loaded: ['p(95) < 2000'],
    http_req_duration: ['p(95) < 1500'],
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
    maxDuration: '1m',
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
}

// All the tests ran for a nightly testing
export function nightly() {
  wpLogin();
  newsletterListing();
  subscribersListing();
}

// HTML report data saved in performance folder
export function handleSummary(data) {
  return {
    'tests/performance/k6report.html': htmlReport(data),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}
