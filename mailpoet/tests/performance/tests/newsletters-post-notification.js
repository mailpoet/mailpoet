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
  defaultListName,
  emailsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, selectInSelect2 } from '../utils/helpers.js';
/* global Promise */

export async function newsletterPostNotification() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Emails page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters#/new/notification`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Post_Notification_01.png',
      fullPage: fullPageSet,
    });

    // Click to add a new post notification email and set frequency
    await page
      .locator('[data-automation-id="newsletter_interval_type"]')
      .waitFor({ state: 'visible' });
    await page
      .locator('[data-automation-id="newsletter_interval_type"]')
      .click();
    await page.selectOption(
      '[data-automation-id="newsletter_interval_type"]',
      'immediately',
    );
    await Promise.all([
      page.waitForNavigation(),
      page.locator('.mailpoet-button.mailpoet-full-width').click(),
    ]);
    await page.waitForSelector('[data-automation-id="templates-standard"]');
    await page.waitForLoadState('networkidle');

    // Switch to a Standard templates tab and select the 2nd template
    await page.locator('[data-automation-id="templates-notification"]').click();
    await Promise.all([
      page.waitForNavigation(),
      page.locator('[data-automation-id="select_template_1"]').click(),
    ]);
    await page.waitForSelector('[data-automation-id="newsletter_title"]');
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Newsletter_Post_Notification_02.png',
      fullPage: fullPageSet,
    });

    // Try to close the tutorial video popup
    try {
      await page.locator('#mailpoet_modal_close').click({ timeout: 5000 });
    } catch (error) {
      console.log("Tutorial video wasn't present, skipping action.");
    }

    // Click to proceed to the next step (the last one)
    await page.$$('input[value="Next"]')[0].click();
    await page.waitForNavigation();
    await page.waitForSelector(
      '[data-automation-id="newsletter_send_heading"]',
    );
    await page.waitForLoadState('networkidle');

    // Select the default list and send the newsletter
    await selectInSelect2(page, defaultListName);
    await Promise.all([
      page.waitForNavigation(),
      page.locator('[data-automation-id="email-submit"]').click(),
    ]);
    sleep(1);

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Post_Notification_03.png',
      fullPage: fullPageSet,
    });

    if (page.url().includes('localhost')) {
      // Wait for the success page and confirm it
      await page.waitForSelector('.mailpoet-wizard-step');
      describe(emailsPageTitle, () => {
        describe('newsletter-post-notification: should be able to see confirmation page for the first time', () => {
          expect(
            page.locator('.mailpoet-congratulate > h1').innerText(),
          ).to.contain('You are all set up and ready to go!');
        });
      });

      await Promise.all([
        page.waitForNavigation(),
        page.locator('.mailpoet-full-width.button-link').click(),
      ]);
      sleep(1);
      await page.waitForSelector('[data-automation-id="tab-Newsletters"]');
      await page.waitForLoadState('networkidle');
    } else {
      // Wait for the success notice message and confirm it
      const locator =
        "//div[@class='notice-success'].//p[starts-with(text(),'Your post notification is now active!')]";
      await page.waitForSelector('#mailpoet_notices');
      describe(emailsPageTitle, () => {
        describe('newsletter-post-notification: should be able to see Post Notification is active message', () => {
          expect(page.locator(locator)).to.exist;
        });
      });
    }

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Post_Notification_04.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default async function newsletterPostNotificationTest() {
  await newsletterPostNotification();
}
