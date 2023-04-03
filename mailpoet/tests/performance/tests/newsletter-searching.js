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
  emailsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';

export async function newsletterSearching() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  // Go to the page
  await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters`, {
    waitUntil: 'networkidle',
  });

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await page.waitForNavigation({ waitUntil: 'networkidle' });

  await page.screenshot({
    path: screenshotPath + 'Newsletter_Searching_01.png',
    fullPage: fullPageSet,
  });

  // Search for a newsletter
  page.locator('#search_input').type('Newsletter 1st', { delay: 50 });
  page.waitForSelector('.mailpoet-listing-no-items');
  page.waitForSelector('[data-automation-id="listing_filter_segment"]');
  page.waitForLoadState('networkidle');
  describe(emailsPageTitle, () => {
    describe('should be able to search for Newsletter 1st', () => {
      expect(page.locator('.mailpoet-listing-title').innerText()).to.contain(
        'Newsletter 1st',
      );
    });
  });

  // Filter newsletter results by a default list "Newsletter mailing list"
  page
    .locator('[data-automation-id="listing_filter_segment"]')
    .selectOption('3');
  page.waitForSelector('.mailpoet-listing-no-items');
  page.waitForSelector('[data-automation-id="listing_filter_segment"]');
  page.waitForLoadState('networkidle');
  describe(emailsPageTitle, () => {
    describe('should be able to see Lists Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
        .exist;
    });
  });

  await page.screenshot({
    path: screenshotPath + 'Newsletter_Searching_02.png',
    fullPage: fullPageSet,
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function newsletterSearchingTest() {
  await newsletterSearching();
}
