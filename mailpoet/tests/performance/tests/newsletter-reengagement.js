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
import { login, selectInSelect2, waitAndType } from '../utils/helpers.js';
/* global Promise */

export async function newsletterReEngagement() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Emails page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-newsletters#/new/re-engagement`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Newsletter_Re_Engagement_01.png',
      fullPage: fullPageSet,
    });

    // Click to add a new re-engagement email and set frequency
    await waitAndType(page, 'input[type="text"]', '99');
    await page.locator('.mailpoet-button.mailpoet-full-width').click();
    await page.waitForSelector('.mailpoet_loading');
    await page.waitForSelector(
      '[data-automation-id="templates-re_engagement"]',
    );
    await page.waitForLoadState('networkidle');

    // Switch to a Standard templates tab and select the 2nd template
    await page
      .locator('[data-automation-id="templates-re_engagement"]')
      .click();
    await Promise.all([
      page.waitForNavigation(),
      page.locator('[data-automation-id="select_template_1"]').click(),
    ]);
    await page.waitForSelector('[data-automation-id="newsletter_title"]');
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Newsletter_Re_Engagement_02.png',
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
      path: screenshotPath + 'Newsletter_Re_Engagement_03.png',
      fullPage: fullPageSet,
    });

    // Wait for the success notice message and confirm it
    const locator =
      "//div[@class='notice-success'].//p[starts-with(text(),'Your Re-engagement Email is now activated!')]";
    await page.waitForSelector('#mailpoet_notices');
    describe(emailsPageTitle, () => {
      describe('newsletter-re-engagement: should be able to see Re-engagement is active message', () => {
        expect(page.locator(locator)).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Newsletter_Re_Engagement_04.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default async function newsletterReEngagementTest() {
  await newsletterReEngagement();
}
