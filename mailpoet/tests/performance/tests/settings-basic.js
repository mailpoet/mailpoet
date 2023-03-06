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

export async function settingsBasic() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  try {
    // Go to the page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-settings#/basics`,
      {
        waitUntil: 'networkidle',
      },
    );

    // Log in to WP Admin
    authenticate(page);

    // Wait for async actions
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' })]);

    // Check if the basics tab is present and visible
    check(page, {
      'basics tab is visible': page
        .locator('[data-automation-id="basic_settings_tab"]')
        .isVisible(),
    });

    // Click to save the settings
    page.locator('[data-automation-id="settings-submit-button"]').click();
    page.waitForSelector('div.notice');
    page.waitForLoadState('networkidle');

    // Check if there's notice about saved settings
    check(page, {
      'settings saved is visible': page.locator('div.notice').isVisible(),
    });
  } finally {
    page.close();
    browser.close();
  }
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function settingsBasicTest() {
  settingsBasic();
}
