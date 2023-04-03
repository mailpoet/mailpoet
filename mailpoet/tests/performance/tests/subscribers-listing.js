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
  subscribersPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';

export async function subscribersListing() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  // Go to the page
  await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
    waitUntil: 'networkidle',
  });

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await page.waitForNavigation({ waitUntil: 'networkidle' });

  await page.screenshot({
    path: screenshotPath + 'Subscribers_Listing_01.png',
    fullPage: fullPageSet,
  });

  // Verify filter, tag and listing are loaded and visible
  await page.waitForLoadState('networkidle');
  describe(subscribersPageTitle, () => {
    describe('should be able to see Tags Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_tag"]')).to
        .exist;
    });
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function subscribersListingTest() {
  await subscribersListing();
}
