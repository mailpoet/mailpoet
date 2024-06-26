/* eslint-disable no-unused-expressions */
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
  emailsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, waitForSelectorToBeVisible } from '../utils/helpers.js';

export async function newsletterStatistics() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Newsletter Statistics page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters#/stats/2`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Statistics_01.png',
      fullPage: fullPageSet,
    });

    // Wait for the page to load and click sold tab
    await page.waitForSelector('.mailpoet-listing-table');
    await page.waitForSelector('[data-automation-id="products-sold-tab"]');
    await page.waitForLoadState('networkidle');
    await page.locator('[data-automation-id="products-sold-tab"]').click();
    await page.waitForLoadState('networkidle');

    // Check if you see the product in the sold tab
    await waitForSelectorToBeVisible(
      page,
      '[data-acceptance-id="purchased-product-Simple Product"]',
    );
    describe(emailsPageTitle, () => {
      describe('newsletter-statistics: should be able to see the product as sold', () => {
        expect(
          page.locator(
            '[data-acceptance-id="purchased-product-Simple Product"]',
          ),
        ).to.exist;
      });
    });

    // Click the subscribers engagement tab
    await page.locator('[data-automation-id="engagement-tab"]').click();
    await page.waitForSelector('[data-automation-id="filters_all_engaged"]');
    await page.waitForLoadState('networkidle');
    describe(emailsPageTitle, () => {
      describe('newsletter-statistics: should be able to see Link Clicked filter', () => {
        expect(page.locator('[data-automation-id="filters_all_engaged"]')).to
          .exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Newsletter_Statistics_02.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function newsletterStatisticsTest() {
  await newsletterStatistics();
}
