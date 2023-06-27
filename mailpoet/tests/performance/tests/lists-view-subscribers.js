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
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login } from '../utils/helpers.js';

export async function listsViewSubscribers() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  // Log in to WP Admin
  await login(page);

  // Go to the Lists page
  await page.goto(
    `${baseURL}/wp-admin/admin.php?page=mailpoet-segments#/lists`,
    {
      waitUntil: 'networkidle',
    },
  );

  await page.waitForLoadState('networkidle');
  await page.screenshot({
    path: screenshotPath + 'Lists_View_Subscribers_01.png',
    fullPage: fullPageSet,
  });

  // Click to view subscribers of the default list "Newsletter mailing list"
  await page.waitForSelector('[data-automation-id="dynamic-segments-tab"]');
  describe(listsPageTitle, () => {
    describe('should be able to see Segments tab', () => {
      expect(page.locator('[data-automation-id="dynamic-segments-tab"]')).to
        .exist;
    });
  });
  await page
    .locator('[data-automation-id="segment_name_' + defaultListName + '"]')
    .hover();
  await page
    .locator('[data-automation-id="view_subscribers_' + defaultListName + '"]')
    .click();

  // Wait for the page to load
  await page.waitForSelector('.mailpoet-listing-no-items');
  await page.waitForSelector('[data-automation-id="filters_subscribed"]');
  describe(listsPageTitle, () => {
    describe('should be able to see Lists Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
        .exist;
    });
  });
  await page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + 'Lists_View_Subscribers_02.png',
    fullPage: fullPageSet,
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function listsViewSubscribersTest() {
  await listsViewSubscribers();
}
