/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/* eslint-disable no-unused-expressions */
/**
 * External dependencies
 */
import { sleep } from 'k6';
import { chromium } from 'k6/experimental/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';
import {
  expect,
  describe,
} from 'https://jslib.k6.io/k6chaijs/4.3.4.2/index.js';

/**
 * Internal dependencies
 */
import {
  baseURL,
  thinkTimeMin,
  thinkTimeMax,
  headlessSet,
  timeoutSet,
  defaultListName,
  listsPageTitle,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';

export async function listsViewSubscribers() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  // Go to the page
  await page.goto(
    `${baseURL}/wp-admin/admin.php?page=mailpoet-segments#/lists`,
    {
      waitUntil: 'networkidle',
    },
  );

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await page.waitForNavigation({ waitUntil: 'networkidle' });

  // Click to view subscribers of the default list "Newsletter mailing list"
  page.waitForSelector('[data-automation-id="dynamic-segments-tab"]');
  describe(listsPageTitle, () => {
    describe('should be able to see Segments tab', () => {
      expect(page.locator('[data-automation-id="dynamic-segments-tab"]')).to
        .exist;
    });
  });
  page
    .locator('[data-automation-id="segment_name_' + defaultListName + '"]')
    .hover();
  page
    .locator('[data-automation-id="view_subscribers_' + defaultListName + '"]')
    .click();

  // Wait for the page to load
  page.waitForSelector('.mailpoet-listing-no-items');
  page.waitForSelector('[data-automation-id="filters_subscribed"]');
  describe(listsPageTitle, () => {
    describe('should be able to see Lists Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
        .exist;
    });
  });
  page.waitForLoadState('networkidle');

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function listsViewSubscribersTest() {
  await listsViewSubscribers();
}
