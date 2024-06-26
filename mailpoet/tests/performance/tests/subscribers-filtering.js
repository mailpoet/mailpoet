/**
 * External dependencies
 */
import { sleep } from 'k6';
import { browser } from 'k6/experimental/browser';
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
  adminEmail,
  subscribersPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login } from '../utils/helpers.js';

export async function subscribersFiltering() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Subscribers page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Subscribers_Filtering_01.png',
      fullPage: fullPageSet,
    });

    // Check the subscribers filter is present
    await page.locator('[data-automation-id="filters_subscribed"]').click();
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    describe(subscribersPageTitle, () => {
      describe('subscribers-filtering: should be able to see Lists Filter 1st time', () => {
        expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
          .exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Subscribers_Filtering_02.png',
      fullPage: fullPageSet,
    });

    // Select option "Newsletter mailing list" in the subscribers filter
    await page
      .locator('[data-automation-id="listing_filter_segment"]')
      .selectOption('3');
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    describe(subscribersPageTitle, () => {
      describe('subscribers-filtering: should be able to see Lists Filter 2nd time', () => {
        expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
          .exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Subscribers_Filtering_03.png',
      fullPage: fullPageSet,
    });

    // Search for a subscriber in a filtered list
    await page.locator('#search_input').type(adminEmail, { delay: 50 });
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    describe(subscribersPageTitle, () => {
      describe('subscribers-filtering: should be able to see Lists Filter 3rd time', () => {
        expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
          .exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Subscribers_Filtering_04.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function subscribersFilteringTest() {
  await subscribersFiltering();
}
