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
import { login } from '../utils/helpers.js';

export async function newsletterListing() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Emails page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Listing_01.png',
      fullPage: fullPageSet,
    });

    // Check if there is element present and visible
    await page.waitForSelector('[data-automation-id="filters_all"]');
    await page.waitForLoadState('networkidle');
    describe(emailsPageTitle, () => {
      describe('newsletter-listing: should be able to see All Filter', () => {
        expect(page.locator('[data-automation-id="filters_all"]')).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Newsletter_Listing_02.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default async function newsletterListingTest() {
  await newsletterListing();
}
