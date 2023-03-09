/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check } from 'k6';
import { chromium } from 'k6/experimental/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

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
} from '../config.js';
import {
  authenticate,
  selectInSelect2,
  waitAndClick,
} from '../utils/helpers.js';

export async function formsAdding() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  try {
    // Go to the page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-forms`, {
      waitUntil: 'networkidle',
    });

    // Log in to WP Admin
    authenticate(page);

    // Wait for async actions
    await page.waitForNavigation({ waitUntil: 'networkidle' });

    // Wait and click the Add New Form button
    waitAndClick(page, '[data-automation-id="create_new_form"]');
    sleep(1);
    page.waitForLoadState('networkidle');

    // Choose the form template
    waitAndClick(
      page,
      '[data-automation-id="select_template_template_1_popup"]',
    );
    sleep(1);

    // Select the list and save the form
    page.waitForSelector('[data-automation-id="form_title_input"]');
    selectInSelect2(page, defaultListName);
    page.waitForSelector('[data-automation-id="form_save_button"]');
    page.locator('[data-automation-id="form_save_button"]').click();
    page.waitForSelector('.components-notice');
    check(page, {
      'form has been saved notice is present':
        page.locator('.components-notice__content').textContent() ===
        'Form saved. Cookies reset — you will see all your dismissed popup forms again.',
    });
  } finally {
    page.close();
    browser.close();
  }
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function formsAddingTest() {
  formsAdding();
}
