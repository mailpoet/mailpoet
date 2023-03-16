/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check } from 'k6';
import { chromium } from 'k6/experimental/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import {
  baseURL,
  thinkTimeMin,
  thinkTimeMax,
  headlessSet,
  timeoutSet,
} from '../config.js';
import { authenticate, waitForSelectorToBeVisible } from '../utils/helpers.js';

export async function newsletterStatistics() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  try {
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
    check(page, {
      'no products present as sold text is present':
        page.locator('.mailpoet-tab-content > p:nth-child(1)').textContent() ===
        'Unfortunately, no products were sold as a result of this email!',
    });

    // Click the subscribers engagement tab
    page.locator('[data-automation-id="engagement-tab"]');
    page.waitForSelector('.mailpoet-listing-table');
    waitForSelectorToBeVisible(
      page,
      '[data-automation-id="filters_all_engaged"]',
    );
    page.waitForLoadState('networkidle');
    check(page, {
      'link clicked filter is visible': page
        .locator('[data-automation-id="filters_all_engaged"]')
        .isVisible(),
    });
  } finally {
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
    page.close();
    browser.close();
  }
}

export default async function newsletterStatisticsTest() {
  await newsletterStatistics();
}
