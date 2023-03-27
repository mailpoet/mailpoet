/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/* eslint-disable no-unused-expressions */
/**
 * External dependencies
 */
import { sleep } from 'k6';
import { chromium } from 'k6/experimental/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';
import {
  expect,
  describe,
} from 'https://jslib.k6.io/k6chaijs/4.3.4.2/index.js';

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
  subscribersPageTitle,
} from '../config.js';
import { authenticate, selectInSelect2 } from '../utils/helpers.js';

export async function subscribersAdding() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  let subscriberEmail =
    'test+' + Math.floor(Math.random() * 9999 + 1) + '@test.com';

  // Go to the page
  await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
    waitUntil: 'networkidle',
  });

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await page.waitForNavigation({ waitUntil: 'networkidle' });

  // Add a new subscriber
  page.locator('[data-automation-id="add-new-subscribers-button"]').click();
  page.locator('input[name="email"]').type(subscriberEmail);
  page.locator('input[name="first_name"]').type(firstName);
  page.locator('input[name="last_name"]').type(lastName);
  selectInSelect2(page, defaultListName);
  page.locator('button[type="submit"]').click();

  // Verify you see the success message and the filter is visible
  page.waitForSelector('div.notice');
  describe(subscribersPageTitle, () => {
    describe('should be able to see Subscriber Added message', () => {
      expect(page.locator('div.notice').innerText()).to.contain(
        'Subscriber was added successfully!',
      );
    });
  });
  page.waitForSelector('.mailpoet-listing-no-items');
  page.waitForSelector('[data-automation-id="filters_subscribed"]');
  describe(subscribersPageTitle, () => {
    describe('should be able to see Lists Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
        .exist;
    });
  });
  page.waitForLoadState('networkidle');

  // Search for a newly added subscriber and verify
  page.locator('#search_input').type(subscriberEmail, { delay: 50 });
  page.waitForSelector('.mailpoet-listing-no-items');
  page.waitForSelector('[data-automation-id="filters_subscribed"]');
  page.waitForLoadState('networkidle');
  describe(subscribersPageTitle, () => {
    describe('should be able to search for Newly Added Subscriber', () => {
      expect(page.locator('.mailpoet-listing-title').innerText()).to.contain(
        subscriberEmail,
      );
    });
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function subscribersAddingTest() {
  await subscribersAdding();
}
