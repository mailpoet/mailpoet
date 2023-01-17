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
import {
  baseURL,
  thinkTimeMin,
  thinkTimeMax,
  headlessSet,
} from '../../config.js';
import { login } from '../utils/helpers.js';
/* global Promise */

export function subscribersListing() {
  const browser = chromium.launch({ headless: headlessSet });
  const page = browser.newPage();

  group(
    'Subscribers - Load all subscribers',
    function subscribersLoadAllSusbcribers() {
      page
        .goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
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
        })

        .finally(() => {
          page.close();
          browser.close();
        });
    },
  );

  sleep(randomIntBetween(`${thinkTimeMin}`, `${thinkTimeMax}`));
}

export function subscribersListingTest() {
  subscribersListing();
}
