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
  formsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, selectInSelect2, waitAndClick } from '../utils/helpers.js';

export async function formsAdding() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Forms page
    await page.goto(
      `${baseURL} / wp - admin / admin.php ? page = mailpoet - forms`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Forms_Adding_01.png',
      fullPage: fullPageSet,
    });

    // Wait and click the Add New Form button
    await waitAndClick(page, '[data-automation-id="create_new_form"]');

    // Choose the form template
    await page.waitForNavigation();
    await waitAndClick(
      page,
      '[data-automation-id="select_template_template_1_popup"]',
    );
    await page.waitForNavigation();

    // Try to close the tutorial video popup
    try {
      await page
        .locator('[data-automation-id="mailpoet-modal-close"]')
        .click({ timeout: 5000 });
    } catch (error) {
      console.log("Tutorial video wasn't present, skipping action.");
    }

    // Select the list and save the form
    await page.waitForSelector('[data-automation-id="form_title_input"]');
    await selectInSelect2(page, defaultListName);
    await page.waitForSelector('[data-automation-id="form_save_button"]');
    await page.locator('[data-automation-id="form_save_button"]').click();
    await page.waitForSelector('.components-notice');
    describe(formsPageTitle, () => {
      describe('forms-adding: should be able to see Forms Saved message', () => {
        expect(
          page.locator('.components-notice__content').innerText(),
        ).to.contain(
          'Form saved. Cookies reset — you will see all your dismissed popup forms again.',
        );
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Forms_Adding_02.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default function formsAddingTest() {
  formsAdding();
}
