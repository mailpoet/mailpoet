/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check, group } from 'k6';
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

export function newsletterSearching() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  group('Emails - Search for a newsletter', () => {
    page
      .goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters`, {
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
        page.locator('#search_input').type('Newsletter 1st', { delay: 50 });
        page.waitForSelector('.mailpoet-listing-no-items');
        page.waitForSelector('[data-automation-id="listing_filter_segment"]');
        page.waitForLoadState('networkidle');
        check(page, {
          'newsletter is found': page
            .locator('.mailpoet-listing-title')
            .innerText('Newsletter 1st'),
        });
      })

      .then(() => {
        page
          .locator('[data-automation-id="listing_filter_segment"]')
          .selectOption('3');
        page.waitForSelector('.mailpoet-listing-no-items');
        page.waitForSelector('[data-automation-id="listing_filter_segment"]');
        page.waitForLoadState('networkidle');
        check(page, {
          'lists filter is visible': page
            .locator('[data-automation-id="listing_filter_segment"]')
            .isVisible(),
        });
      })

      .finally(() => {
        page.close();
        browser.close();
      });
  });

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function newsletterSearchingTest() {
  newsletterSearching();
}
