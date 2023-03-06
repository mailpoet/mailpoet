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
import { authenticate, selectInSelect2 } from '../utils/helpers.js';
/* global Promise */

export async function newsletterSending() {
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

    // Wait for async actions
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' })]);

    // Click to add a new standard newsletter
    page.locator('[data-automation-id="new_email"]').click();
    page.locator('[data-automation-id="create_standard"]').click();
    page.waitForSelector('.mailpoet_loading');
    page.waitForSelector('[data-automation-id="templates-standard"]');
    page.waitForLoadState('networkidle');

    // Switch to a Standard templates tab and select the 2nd template
    page.locator('[data-automation-id="templates-standard"]').click();
    await Promise.all([
      page.waitForNavigation(),
      page.locator('[data-automation-id="select_template_1"]').click(),
    ]);
    page.waitForSelector('.mailpoet_loading');
    page.waitForSelector('[data-automation-id="newsletter_title"]');
    page.waitForLoadState('networkidle');

    // Click to proceed to the next step (the last one)
    page.locator('//input[text()="Next"]').click();
    page.waitForSelector('.mailpoet_loading');
    page.waitForSelector('[data-automation-id="newsletter_send_heading"]');
    page.waitForLoadState('networkidle');

    // Select the default list and send the newsletter
    selectInSelect2(defaultListName);
    page.locator('[data-automation-id="email-submit"]').click();
    page.waitForSelector('.mailpoet_loading');
    page.waitForSelector('.mailpoet-h0');
    page.waitForLoadState('networkidle');
    check(page, {
      'title congratulations is present':
        page.locator('h1').textContent() === 'Congratulations!',
    });

    // Click close button to get back to Emails page
    page.locator('button[type="button"]').click();
    page.waitForSelector('[data-automation-id="tab-Newsletters"]');
    page.waitForLoadState('networkidle');
    check(page, {
      'newsletter filter is visible': page
        .locator('[data-automation-id="listing_filter_segment"]')
        .isVisible(),
    });
  } finally {
    page.close();
    browser.close();
  }

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function newsletterSendingTest() {
  newsletterSending();
}
