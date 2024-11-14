/* eslint-disable no-unused-expressions */
/**
 * External dependencies
 */
import { sleep } from 'k6';
import { browser } from 'k6/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.5.0/index.js';
import {
  expect,
  describe,
} from 'https://jslib.k6.io/k6chaijs/4.5.0.0/index.js';

/**
 * Internal dependencies
 */
import {
  baseURL,
  thinkTimeMin,
  thinkTimeMax,
  firstName,
  lastName,
  defaultListName,
  subscribersPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, selectInSelect2 } from '../utils/helpers.js';

export async function subscribersAdding() {
  const page = await browser.newPage();

  try {
    let subscriberEmail = 'blackhole+automation' + Date.now() + '@mailpoet.com';

    // Log in to WP Admin
    await login(page);

    // Go to the Subscribers page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Subscribers_Adding_01.png',
      fullPage: fullPageSet,
    });

    // Add a new subscriber
    await page
      .locator('[data-automation-id="add-new-subscribers-button"]')
      .click();
    await page
      .locator('input[name="email"]')
      .type(subscriberEmail, { delay: 25 });
    await page.locator('input[name="first_name"]').type(firstName);
    await page.locator('input[name="last_name"]').type(lastName);
    await selectInSelect2(page, defaultListName);
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');

    // Verify you see the success message and the filter is visible
    const locator =
      "//div[@class='notice-success'].//p[starts-with(text(),'Subscriber was added successfully!')]";
    const noticeElement = await page.locator(locator);
    describe(subscribersPageTitle, () => {
      describe('subscribers-adding: should be able to see Subscriber Added message', async () => {
        expect(noticeElement).to.exist;
      });
    });
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    const listingFilterElement = await page.locator(
      '[data-automation-id="listing_filter_segment"]',
    );
    describe(subscribersPageTitle, () => {
      describe('subscribers-adding: should be able to see Lists Filter', async () => {
        expect(listingFilterElement).to.exist;
      });
    });
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Subscribers_Adding_02.png',
      fullPage: fullPageSet,
    });

    // Search for a newly added subscriber and verify
    await page.locator('#search_input').type(subscriberEmail, { delay: 25 });
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    await page.waitForLoadState('networkidle');
    const listingTitleElement = await page
      .locator('.mailpoet-listing-title')
      .innerText();
    describe(subscribersPageTitle, () => {
      describe('subscribers-adding: should be able to search for Newly Added Subscriber', async () => {
        expect(listingTitleElement).to.contain(subscriberEmail);
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Subscribers_Adding_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function subscribersAddingTest() {
  await subscribersAdding();
}
