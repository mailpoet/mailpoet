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
  automationsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login } from '../utils/helpers.js';

export async function automationCreateCustom() {
  const page = browser.newPage();

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

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_01.png',
      fullPage: fullPageSet,
    });

    await page.waitForSelector('.mailpoet-templates-card-grid');
    await page
      .locator('//button[text()="Create custom automation"]')
      .first()
      .click();
    await page.waitForSelector(
      '.mailpoet-automation-editor-step-transition-wrapper',
    );
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-create-custom: should be able to see Add Trigger button', () => {
        expect(page.locator('.mailpoet-automation-add-trigger')).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_02.png',
      fullPage: fullPageSet,
    });

    await page.locator('.mailpoet-automation-add-trigger').click();
    await page
      .locator('.editor-block-list-item-mailpoet:someone-subscribes')
      .click();

    await page.locator('[placeholder="Any list"]').type(defaultListName);
    await page.keyboard.press('Enter');

    await page.locator('.mailpoet-automation-editor-add-step-button').click();
    await page.locator('.editor-block-list-item-mailpoet:add-tag').click();

    await page.locator('[placeholder="Please select a tag"]').type('test');
    await page.keyboard.press('Enter');

    await page.locator('//button[text()="Activate"]');
    await page.locator('//button[text()="Activate"]');

    describe(automationsPageTitle, () => {
      describe('automation-create-custom: should be able to see Automation added message', () => {
        expect(page.locator('.mailpoet-automation-add-trigger')).to.exist;
      });
    });

    page
      .locator('.components-snackbar__content')
      .innerText()
      .to.contain('Automation is now activated!');

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default function automationCreateCustomTest() {
  automationCreateCustom();
}
