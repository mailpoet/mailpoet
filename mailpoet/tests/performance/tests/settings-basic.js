/* eslint-disable no-shadow */
/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check, group } from 'k6';
import { chromium } from 'k6/x/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import { baseURL, thinkTimeMin, thinkTimeMax, headlessSet } from '../config.js';
import { authenticate } from '../utils/helpers.js';
/* global Promise */

export function settingsBasic() {
  const browser = chromium.launch({ headless: headlessSet });
  const page = browser.newPage();

  group(
    'Settings - Load and save the basics tab',
    function settingsBasicTabSaving() {
      page
        .goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-settings#/basics`, {
          waitUntil: 'networkidle',
        })

        .then(() => {
          authenticate(page);
        })

        .then(() => {
          return Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
          ]);
        })

        .then(() => {
          check(page, {
            'basics tab is visible': page
              .locator('[data-automation-id="basic_settings_tab"]')
              .isVisible(),
          });
        })

        .then(() => {
          return Promise.all([
            page.waitForNavigation(),
            page
              .locator('[data-automation-id="settings-submit-button"]')
              .click(),
          ]);
        })

        .then(() => {
          check(page, {
            'settings saved is visible': page.locator('div.notice').isVisible(),
          });
        })

        .finally(() => {
          page.close();
          browser.close();
        });
    },
  );

  sleep(randomIntBetween(`${thinkTimeMin}`, `${thinkTimeMax}`));
}

export default function settingsBasicTest() {
  settingsBasic();
}
