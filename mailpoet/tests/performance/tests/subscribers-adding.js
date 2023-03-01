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
  firstName,
  lastName,
  defaultListName,
} from '../config.js';
import { authenticate, selectInSelect2 } from '../utils/helpers.js';
/* global Promise */

export function subscribersAdding() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  group('Subscribers - Add a new subscriber', () => {
    let subscriberEmail =
      'test+' + Math.floor(Math.random() * 9999 + 1) + '@test.com';

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
          .locator('[data-automation-id="add-new-subscribers-button"]')
          .click();
        page.locator('input[name="email"]').type(subscriberEmail);
        page.locator('input[name="first_name"]').type(firstName);
        page.locator('input[name="last_name"]').type(lastName);
        selectInSelect2(page, defaultListName);
        page.locator('button[type="submit"]').click();
        page.waitForSelector('.mailpoet-listing-no-items');
        page.waitForSelector('[data-automation-id="filters_subscribed"]');
        check(page, {
          'subscribers filter is visible': page
            .locator('[data-automation-id="listing_filter_segment"]')
            .isVisible(),
        });
      })

      .then(() => {
        page.waitForNavigation({ waitUntil: 'networkidle' });
        page.locator('#search_input').type(subscriberEmail, { delay: 50 });
        page.waitForSelector('.mailpoet-listing-no-items');
        page.waitForSelector('[data-automation-id="filters_subscribed"]');
        check(page, {
          'newly added subscriber is present in the listing':
            page.locator('.mailpoet-listing-title').textContent() ===
            subscriberEmail,
        });
      })

      .finally(() => {
        page.close();
        browser.close();
      });
  });

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function subscribersAddingTest() {
  subscribersAdding();
}
