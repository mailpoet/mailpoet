/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check } from 'k6';
import { chromium } from 'k6/experimental/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';
// import {
//   describe,
//   expect,
// } from 'https://jslib.k6.io/k6chaijs/4.3.4.2/index.js';

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

export async function settingsBasic() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();
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
  await page.waitForNavigation({ waitUntil: 'networkidle' });

  // Click to save the settings
  page.locator('[data-automation-id="settings-submit-button"]').click();
  page.waitForSelector('div.notice');
  page.waitForLoadState('networkidle');

  // Check if there's notice about saved settings
  // describe('Settings Page - should be able to see the settings saved success notice', () => {
  //   expect(page.locator('div.notice').innerText()).to.contain(
  //     'Settings saved2',
  //   );
  // });

  check(page, {
    'Settings saved notice has been present':
      page.locator('div.notice').textContent() === 'Settings saved2',
  });

  // Click to save the settings
  page.locator('[data-automation-id="settings-submit-button"]').click();
  page.waitForSelector('div.notice');
  page.waitForLoadState('networkidle');

  // Wait for a bit before closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function settingsBasicTest() {
  await settingsBasic();
}
