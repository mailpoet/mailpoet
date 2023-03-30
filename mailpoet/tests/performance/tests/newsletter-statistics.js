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
  emailsPageTitle,
} from '../config.js';
import { authenticate, waitForSelectorToBeVisible } from '../utils/helpers.js';

export async function newsletterStatistics() {
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

  // Wait for async actions and open newsletter statistics
  await page.waitForNavigation({ waitUntil: 'networkidle' });
  await page.goto(
    `${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters#/stats/1`,
    {
      waitUntil: 'networkidle',
    },
  );

  // Wait for the page to load and click sold tab
  page.waitForSelector('.mailpoet-listing-table');
  page.waitForSelector('[data-automation-id="products-sold-tab"]');
  page.waitForLoadState('networkidle');
  page.locator('[data-automation-id="products-sold-tab"]').click();
  page.waitForLoadState('networkidle');
  await waitForSelectorToBeVisible(
    page,
    '.mailpoet-tab-content > p:nth-child(1)',
  );
  describe(emailsPageTitle, () => {
    describe('should be able to see text no products sold', () => {
      expect(page.locator('.mailpoet-tab-content > p:nth-child(1)').innerText()).to.contain(
        'Unfortunately, no products were sold as a result of this email!',
      );
    });
  });

  // Click the subscribers engagement tab
  page.locator('[data-automation-id="engagement-tab"]');
  page.waitForSelector('.mailpoet-listing-table');
  waitForSelectorToBeVisible(
    page,
    '[data-automation-id="filters_all_engaged"]',
  );
  page.waitForLoadState('networkidle');
  describe(emailsPageTitle, () => {
    describe('should be able to see Link Clicked filter', () => {
      expect(page.locator('[data-automation-id="filters_all_engaged"]')).to
        .exist;
    });
  });

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function newsletterStatisticsTest() {
  await newsletterStatistics();
}
