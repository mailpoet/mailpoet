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
import { authenticate } from '../utils/helpers.js';
/* global Promise */

export async function subscribersListing() {
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
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' })]);

    // Verify filter, tag and listing are loaded and visible
    page.waitForLoadState('networkidle');
    check(page, {
      'subscribers filter is visible': page
        .locator('[data-automation-id="listing_filter_segment"]')
        .isVisible(),
      'subscribers tag is visible': page
        .locator('[data-automation-id="listing_filter_tag"]')
        .isVisible(),
      'subscribers listing is visible': page
        .locator('table.mailpoet-listing-table')
        .isVisible(),
    });
  } finally {
    page.close();
    browser.close();
  }

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function subscribersListingTest() {
  subscribersListing();
}
