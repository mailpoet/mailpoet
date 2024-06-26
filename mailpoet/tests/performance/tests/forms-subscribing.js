/**
 * External dependencies
 */
import { sleep } from 'k6';
import { browser } from 'k6/experimental/browser';
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
  formsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import {
  login,
  waitAndType,
  waitForSelectorToBeVisible,
} from '../utils/helpers.js';

export async function formsSubscribing() {
  const page = await browser.newPage();

  try {
    let subscriberEmail = 'blackhole+testform' + Date.now() + '@mailpoet.com';

    // Log in to WP Admin
    await login(page);

    // Go to the test form page
    await page.goto(`${baseURL}/sample-page/`, {
      waitUntil: 'networkidle',
    });

    await page.waitForSelector('.mailpoet_form_below_posts');
    await page.screenshot({
      path: screenshotPath + 'Forms_Subscribing_01.png',
      fullPage: fullPageSet,
    });

    // Subscribe to a test form
    await waitAndType(
      page,
      '[data-automation-id="form_email"]',
      subscriberEmail,
    );
    await page
      .locator('[data-automation-id="subscribe-submit-button"]')
      .click();
    await waitForSelectorToBeVisible(page, '.mailpoet_validate_success');
    describe(formsPageTitle, () => {
      describe('forms-subscribing: should be able to see successfully subscribed message', () => {
        expect(
          page.locator('.mailpoet_validate_success').innerText(),
        ).to.contain('Successfully subscribed!');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Forms_Subscribing_02.png',
      fullPage: fullPageSet,
    });

    // Go to Subscribers page to see subscribed user
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers#/`,
      {
        waitUntil: 'networkidle',
      },
    );
    await page.locator('#search_input').type(subscriberEmail, { delay: 25 });
    await page.waitForSelector('.mailpoet-listing-no-items');
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    await page.waitForLoadState('networkidle');
    describe(formsPageTitle, () => {
      describe('forms-subscribing: should be able to search for a new subscriber', () => {
        expect(page.locator('.mailpoet-listing-title').innerText()).to.contain(
          subscriberEmail,
        );
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Forms_Subscribing_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default function formsSubscribingTest() {
  formsSubscribing();
}
