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
  adminEmail,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';

export async function subscribersFiltering() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  try {
    // Go to the page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
      waitUntil: 'networkidle',
    });

    // Log in to WP Admin
    authenticate(page);

    // Wait for async actions
    await page.waitForNavigation({ waitUntil: 'networkidle' });

    // Check the subscribers filter is present
    page.locator('[data-automation-id="filters_subscribed"]').click();
    page.waitForSelector('[data-automation-id="filters_subscribed"]');
    check(page, {
      'subscribers filter is visible': page
        .locator('[data-automation-id="listing_filter_segment"]')
        .isVisible(),
    });

    // Select option "Newsletter mailing list" in the subscribers filter
    page
      .locator('[data-automation-id="listing_filter_segment"]')
      .selectOption('3');
    page.waitForSelector('.mailpoet-listing-no-items');
    page.waitForSelector('[data-automation-id="filters_subscribed"]');
    check(page, {
      'subscribers filter is visible': page
        .locator('[data-automation-id="listing_filter_segment"]')
        .isVisible(),
    });
    page.waitForNavigation({ waitUntil: 'networkidle' });

    // Search for a subscriber in a filtered list
    page.locator('#search_input').type(adminEmail, { delay: 50 });
    page.waitForSelector('.mailpoet-listing-no-items');
    page.waitForSelector('[data-automation-id="filters_subscribed"]');
    check(page, {
      'subscribers filter is visible': page
        .locator('[data-automation-id="listing_filter_segment"]')
        .isVisible(),
    });
  } finally {
    page.close();
    browser.close();
  }

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function subscribersFilteringTest() {
  subscribersFiltering();
}
