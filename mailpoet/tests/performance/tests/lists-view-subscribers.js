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
  defaultListName,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';
/* global Promise */

export async function listsViewSubscribers() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  try {
    // Go to the page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-segments#/lists`,
      {
        waitUntil: 'networkidle',
      },
    );

    // Log in to WP Admin
    authenticate(page);

    // Wait for async actions
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' })]);

    // Click to view subscribers of the default list "Newsletter mailing list"
    page.waitForSelector('[data-automation-id="dynamic-segments-tab"]');
    check(page, {
      'segments tab is visible': page
        .locator('[data-automation-id="dynamic-segments-tab"]')
        .isVisible(),
    });
    page
      .locator('[data-automation-id="segment_name_' + defaultListName + '"]')
      .hover();
    page
      .locator(
        '[data-automation-id="view_subscribers_' + defaultListName + '"]',
      )
      .click();

    // Wait for the page to load
    page.waitForSelector('.mailpoet-listing-no-items');
    page.waitForSelector('[data-automation-id="filters_subscribed"]');
    check(page, {
      'subscribers filter is visible': page
        .locator('[data-automation-id="listing_filter_segment"]')
        .isVisible(),
    });
    page.waitForLoadState('networkidle');
  } finally {
    page.close();
    browser.close();
  }

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function listsViewSubscribersTest() {
  listsViewSubscribers();
}
