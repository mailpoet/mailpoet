/* eslint-disable no-shadow */
/* eslint-disable import/no-unresolved */
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
import { login } from '../utils/helpers.js';
/* global Promise */

export function newsletterListing() {
  const browser = chromium.launch({ headless: headlessSet });
  const page = browser.newPage();

  group('Emails - Load all newsletters', function EmailsLoadAllNewsletters() {
    page
      .goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters`, {
        waitUntil: 'networkidle',
      })

      .then(() => {
        login(page);
      })

      .then(() => {
        return Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle' }),
        ]);
      })

      .then(() => {
        check(page, {
          'newsletter filter is visible': page
            .locator('[data-automation-id="listing_filter_segment"]')
            .isVisible(),
        });
      })

      .finally(() => {
        page.close();
        browser.close();
      });
  });

  sleep(randomIntBetween(`${thinkTimeMin}`, `${thinkTimeMax}`));
}

export function newsletterListingTest() {
  newsletterListing();
}
