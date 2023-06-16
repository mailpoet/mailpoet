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
import { authenticate, waitForSelectorToBeVisible } from '../utils/helpers.js';

export async function subscribersTrashingRestoring() {
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
    path: screenshotPath + 'Subscribers_Trashing_Restoring_01.png',
    fullPage: fullPageSet,
  });

  // Check the subscribers filter is present
  await page.waitForSelector('[data-automation-id="filters_subscribed"]');
  describe(subscribersPageTitle, () => {
    describe('should be able to see Lists Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
        .exist;
    });
  });

  // Select all subscribers
  await page.locator('[data-automation-id="select_all"]').click();
  await page.waitForSelector('.mailpoet-listing-select-all');
  await page.locator('.mailpoet-listing-select-all > a').click();
  await page.waitForSelector('.mailpoet-listing-select-all');

  // Move to trash all the subscribers
  await page.locator('[data-automation-id="action-trash"]').click();
  await page.waitForSelector('.notice-success');
  await page.waitForSelector('.colspanchange');
  describe(subscribersPageTitle, () => {
    describe('should be able to see the message', () => {
      expect(page.locator('.colspanchange').innerText()).to.contain(
        'No items found.',
      );
    });
  });

  await page.screenshot({
    path: screenshotPath + 'Subscribers_Trashing_Restoring_02.png',
    fullPage: fullPageSet,
  });

  // Restore from trash all the trashed subscribers
  await page.locator('[data-automation-id="filters_trash"]').click();
  await page.waitForSelector('[data-automation-id="empty_trash"]');
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  await page.locator('[data-automation-id="select_all"]').click();
  await page.waitForSelector('.mailpoet-listing-select-all');
  await page.locator('.mailpoet-listing-select-all > a').click();
  await page.waitForSelector('[data-automation-id="action-restore"]');
  await page.locator('[data-automation-id="action-restore"]').click();
  await page.waitForSelector('.notice-success');
  waitForSelectorToBeVisible(page, '.colspanchange');
  waitForSelectorToBeVisible(page, '[data-automation-id="filters_subscribed"]');

  await page.screenshot({
    path: screenshotPath + 'Subscribers_Trashing_Restoring_03.png',
    fullPage: fullPageSet,
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function subscribersTrashingRestoringTest() {
  await subscribersTrashingRestoring();
}
