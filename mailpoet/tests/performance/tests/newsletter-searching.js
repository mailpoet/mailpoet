/* eslint-disable no-unused-expressions */
/**
 * External dependencies
 */
import { sleep } from 'k6';
import { browser } from 'k6/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.5.0/index.js';
import {
  expect,
  describe,
} from 'https://jslib.k6.io/k6chaijs/4.5.0.0/index.js';

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
import { login } from '../utils/helpers.js';

export async function newsletterSearching() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Newsletters page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Searching_01.png',
      fullPage: fullPageSet,
    });

    // Search for a newsletter
    await page.locator('#search_input').type('Newsletter 1st', { delay: 50 });
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="listing_filter_segment"]');
    await page.waitForLoadState('networkidle');
    const listingTitleElement = await page
      .locator('.mailpoet-listing-title')
      .innerText();
    describe(emailsPageTitle, () => {
      describe('newsletter-searching: should be able to search for Newsletter 1st', async () => {
        expect(listingTitleElement).to.contain('Newsletter 1st');
      });
    });

    // Filter newsletter results by a default list "Newsletter mailing list"
    await page
      .locator('[data-automation-id="listing_filter_segment"]')
      .selectOption('3');
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="listing_filter_segment"]');
    await page.waitForLoadState('networkidle');
    const listingFilterElement = await page.locator(
      '[data-automation-id="listing_filter_segment"]',
    );
    describe(emailsPageTitle, () => {
      describe('newsletter-searching: should be able to see Lists Filter', async () => {
        expect(listingFilterElement).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Newsletter_Searching_02.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function newsletterSearchingTest() {
  await newsletterSearching();
}
