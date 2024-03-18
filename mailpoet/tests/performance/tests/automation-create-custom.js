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
import {
  login,
  addActionTriggerItemToWorkflow,
  addValueToActionInWorkflow,
  activateWorkflow,
  waitForSelectorToBeVisible,
} from '../utils/helpers.js';

export async function automationCreateCustom() {
  const page = browser.newPage();
  const triggerbutton = '.mailpoet-automation-add-trigger';

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Automations page
    await page.goto(
      `${baseURL} / wp - admin / admin.php ? page = mailpoet - automation - templates`,
      {
        waitUntil: 'networkidle',
      },
    );

    // Wait for page to load and for template to show up
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.mailpoet-templates-card-grid');

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_01.png',
      fullPage: fullPageSet,
    });

    // Click on the button to start custom template
    await Promise.all([
      page.waitForNavigation(),
      page.locator('.mailpoet-page-header > button').click(),
    ]);
    await page.waitForSelector(
      '.mailpoet-automation-editor-step-transition-wrapper',
    );
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-create-custom: should be able to see Add Trigger button', () => {
        expect(page.locator(triggerbutton)).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_02.png',
      fullPage: fullPageSet,
    });

    // Click trigger button to see all possible actions
    await page.locator(triggerbutton).click();

    // Add Someone Subscribers action to the workflow
    await addActionTriggerItemToWorkflow(page, 'subscribe');

    // Make sure the action is selected in the workflow
    await page.locator('.is-selected-step').click();

    // Add Newsletter mailing list as a subscribe to list
    await addValueToActionInWorkflow(page, defaultListName);

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_03.png',
      fullPage: fullPageSet,
    });

    // Click to trigger another action
    await page.locator('.mailpoet-automation-editor-add-step-button').click();

    // Add adding a new tag to a subscriber
    await addActionTriggerItemToWorkflow(page, 'add tag');

    // Make sure the action is selected in the workflow
    await page.locator('.is-selected-step').click();

    // Add existing test1 tag to the action
    await addValueToActionInWorkflow(page, 'test1');

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_04.png',
      fullPage: fullPageSet,
    });

    // Activate the automation workflow
    await activateWorkflow(page);

    describe(automationsPageTitle, () => {
      describe('automation-create-custom: should be able to see Automation added message', () => {
        expect(
          page.locator('.components-snackbar__content').innerText(),
        ).to.contain('Well done! Automation is now activated!');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Create_Custom_05.png',
      fullPage: fullPageSet,
    });

    // Go to Automations listing to measure performance from workflow to listing
    await page.locator('.is-secondary').click();
    await waitForSelectorToBeVisible(page, '.wp-heading-inline');
    await page.waitForLoadState('networkidle');

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
