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
  defaultListName,
  emailsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { authenticate, selectInSelect2 } from '../utils/helpers.js';
/* global Promise */

export async function newsletterSending() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  // Go to the page
  await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters`, {
    waitUntil: 'networkidle',
  });

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' })]);

  await page.screenshot({
    path: screenshotPath + 'Newsletter_Sending_01.png',
    fullPage: fullPageSet,
  });

  // Click to add a new standard newsletter
  page.locator('[data-automation-id="new_email"]').click();
  page.locator('[data-automation-id="create_standard"]').click();
  page.waitForSelector('.mailpoet_loading');
  page.waitForSelector('[data-automation-id="templates-standard"]');
  page.waitForLoadState('networkidle');

  // Switch to a Standard templates tab and select the 2nd template
  page.locator('[data-automation-id="templates-standard"]').click();
  await Promise.all([
    page.waitForNavigation(),
    page.locator('[data-automation-id="select_template_1"]').click(),
  ]);
  page.waitForSelector('.mailpoet_loading');
  page.waitForSelector('[data-automation-id="newsletter_title"]');
  page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + 'Newsletter_Sending_02.png',
    fullPage: fullPageSet,
  });

  // Click to proceed to the next step (the last one)
  await Promise.all([
    page.waitForNavigation(),
    page
      .locator('#mailpoet_editor_top > div > div > .mailpoet_save_next')
      .click(),
  ]);
  page.waitForSelector('[data-automation-id="newsletter_send_heading"]');
  page.waitForLoadState('networkidle');

  // Select the default list and send the newsletter
  selectInSelect2(page, defaultListName);
  await Promise.all([
    page.waitForNavigation(),
    page.locator('[data-automation-id="email-submit"]').click(),
  ]);
  sleep(1);

  await page.screenshot({
    path: screenshotPath + 'Newsletter_Sending_03.png',
    fullPage: fullPageSet,
  });

  // Wait for the success notice message and confirm it
  page.waitForSelector('#mailpoet_notices');
  describe(emailsPageTitle, () => {
    describe('should be able to see Newsletter Sent message', () => {
      expect(page.locator('div > .notice-success').innerText()).to.contain(
        'The newsletter is being sent...',
      );
    });
  });
  page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + 'Newsletter_Sending_04.png',
    fullPage: fullPageSet,
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function newsletterSendingTest() {
  await newsletterSending();
}
