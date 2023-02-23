/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check, group } from 'k6';
import { chromium } from 'k6/x/browser';
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
/* global Promise */

export function formsAdding() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  group('Forms - Add a new form', () => {
    page
      .goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-forms`, {
        waitUntil: 'networkidle',
      })

      .then(() => {
        authenticate(page);
      })

      .then(() => {
        return Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle' }),
        ]);
      })

      .then(() => {
        waitAndClick(page, '[data-automation-id="create_new_form"]');
        sleep(1);
        page.waitForLoadState('networkidle');
        waitAndClick(
          page,
          '[data-automation-id="select_template_template_1_popup"]',
        );
        sleep(1);
        page.waitForSelector('[data-automation-id="form_title_input"]');
        selectInSelect2(page, defaultListName);
        page.waitForSelector('[data-automation-id="form_save_button"]');
        page.locator('[data-automation-id="form_save_button"]').click();
        page.waitForSelector('.components-notice');
        check(page, {
          'form has been saved notice is visible': page
            .locator('.components-notice__content')
            .isVisible(),
        });
      })

      .finally(() => {
        page.close();
        browser.close();
      });
  });

  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
}

export default function formsAddingTest() {
  formsAdding();
}
