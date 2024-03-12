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
  automationsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, selectInSelect2 } from '../utils/helpers.js';

export async function automationTriggerWorkflow() {
  const page = browser.newPage();

  try {
    const subscriberEmail =
      'blackhole+automation' +
      Math.floor(Math.random() * 9999 + 1) +
      '@mailpoet.com';

    // Log in to WP Admin
    await login(page);

    // Go to the Add New Subscriber
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers#/new`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page
      .locator('input[name="email"]')
      .type(subscriberEmail, { delay: 25 });
    await selectInSelect2(page, 'Workflow Triggered');
    await page.selectOption(
      '[data-automation-id="subscriber-status"]',
      'subscribed',
    );
    await page.locator('button[type="submit"]').click();
    await page.waitForSelector('.notice-success');

    // Verify you see the success message and the filter is visible
    const locator =
      "//div[@class='notice-success'].//p[starts-with(text(),'Subscriber was added successfully!')]";
    describe(automationsPageTitle, () => {
      describe('automation-trigger-workflow: should be able to see success notice for adding subscriber', () => {
        expect(page.locator(locator)).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Trigger_Workflow_01.png',
      fullPage: fullPageSet,
    });

    // Go to Scheduled Action to trigger the workflow
    await page.goto(
      `${baseURL}/wp-admin/tools.php?page=action-scheduler&status=pending`,
      {
        waitUntil: 'networkidle',
      },
    );
    await page
      .locator('#plugin-search-input')
      .fill('mailpoet/cron/daemon-trigger');
    await Promise.all([
      page.waitForNavigation(),
      page.locator('#search-submit').click(),
    ]);
    await page.waitForLoadState('networkidle');
    await page.locator('input[name="ID[]"]').hover(); // alternative hover to Run
    await page.locator('.run').click();
    await page.waitForSelector('#message');

    await page.screenshot({
      path: screenshotPath + 'Automation_Trigger_Workflow_02.png',
      fullPage: fullPageSet,
    });

    // Go to the Automation Analytics page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-automation-analytics&id=151&tab=automation-subscribers`,
      {
        waitUntil: 'networkidle',
      },
    );

    // Filter subscribers by subscribed email and completed status
    await page.waitForLoadState('networkidle');
    await page
      .locator('.components-text-control__input')
      .type(subscriberEmail, { delay: 25 });
    await page.selectOption('#inspector-select-control-1', 'complete');
    await page.locator('.components-text-control__input').click();
    await page.keyboard.press('Enter');

    await page.screenshot({
      path: screenshotPath + 'Automation_Trigger_Workflow_03.png',
      fullPage: fullPageSet,
    });

    describe(automationsPageTitle, () => {
      describe('automation-trigger-workflow: should be able to see subscriber in the results', () => {
        expect(
          page.locator('.mailpoet-analytics-orders__customer').innerText(),
        ).to.have.string(subscriberEmail);
      });
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default function automationTriggerWorkflowTest() {
  automationTriggerWorkflow();
}
