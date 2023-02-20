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
import {
  baseURL,
  thinkTimeMin,
  thinkTimeMax,
  headlessSet,
  adminEmail,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';
/* global Promise */

export function subscribersFiltering() {
  const browser = chromium.launch({ headless: headlessSet });
  const page = browser.newPage();

  group('Subscribers - Filter subscribers', function subscribersFiltering() {
    page
      .goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
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
        page
          .locator('[data-automation-id="listing_filter_segment"]')
          .selectOption('3');
        sleep(2);
        page.waitForSelector('[data-automation-id="filters_subscribed"]');
        check(page, {
          'subscribers filter is visible': page
            .locator('[data-automation-id="listing_filter_segment"]')
            .isVisible(),
        });
      })

      .then(() => {
        page.waitForNavigation({ waitUntil: 'networkidle' });
        page.locator('#search_input').type(adminEmail);
        sleep(2);
        page.waitForSelector('[data-automation-id="filters_subscribed"]');
        check(page, {
          'subscribers filter is visible': page
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

export default function subscribersFilteringTest() {
  subscribersFiltering();
}
