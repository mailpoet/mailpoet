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
  automationsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import {
  login,
  activateWorkflow,
  waitForSelectorToBeVisible,
  designEmailInWorkflow,
} from '../utils/helpers.js';

export async function automationCreateWooCommerce() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Automations page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-automation-templates`,
      {
        waitUntil: 'networkidle',
      },
    );

    // Wait for page to load and for template to show up
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.mailpoet-templates-card-grid');

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_WooCommerce_01.png',
      fullPage: fullPageSet,
    });

    // Click on the WooCommerce tab and then choose the first template
    await page.locator('#tab-panel-0-woocommerce').click();
    await page.waitForLoadState('networkidle');
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await waitForSelectorToBeVisible(
      page,
      '.mailpoet-automation-template-detail-content',
    );
    await Promise.all([
      page.waitForNavigation(),
      page.locator('.components-button.is-primary').click(),
    ]);
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-create-woocommerce: should be able to see items in the workflow', () => {
        expect(page.locator('.mailpoet-automation-editor-automation-row')).to
          .exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_WooCommerce_02.png',
      fullPage: fullPageSet,
    });

    // Click Send Email actionable item and design it
    await activateWorkflow(page);
    await page.waitForSelector('.mailpoet-automation-errors-header');
    await page.locator('.mailpoet-automation-step-error').click();
    await designEmailInWorkflow(page);

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_WooCommerce_03.png',
      fullPage: fullPageSet,
    });

    // Activate the automation workflow
    await activateWorkflow(page);

    describe(automationsPageTitle, () => {
      describe('automation-create-woocommerce: should be able to see Automation added message', () => {
        expect(
          page.locator('.components-snackbar__content').innerText(),
        ).to.contain('Well done! Automation is now activated!');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_WooCommerce_04.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default function automationCreateWooCommerceTest() {
  automationCreateWooCommerce();
}
