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
import { login, waitForSelectorToBeVisible } from '../utils/helpers.js';

export async function automationTrashRestore() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Automations page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-automation`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Automation_Trash_Restore_01.png',
      fullPage: fullPageSet,
    });

    // Move to trash one of the existing automation listing
    await page.$$('[aria-label="More"]')[0].click(); // click More
    await page.locator('.components-popover__content').click(); // click Trash
    await page.waitForLoadState('networkidle');
    await page.locator('div.components-flex > button.is-primary').focus();
    await page.locator('div.components-flex > button.is-primary').click(); // click Move to trash
    await page.waitForLoadState('networkidle');

    // Wait for the success notice message and confirm it
    const movedToTrashNotice =
      "//div[@class='notice-success'].//p[contains(text(),'was moved to the trash')]";
    describe(automationsPageTitle, () => {
      describe('automation-trash-restore: should be able to see moved to trash notice', () => {
        expect(page.locator(movedToTrashNotice)).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Automation_Trash_Restore_02.png',
      fullPage: fullPageSet,
    });

    // Restore from the trash one of trashed automation items
    await waitForSelectorToBeVisible(page, '.mailpoet-tab-trash');
    await page.locator('.mailpoet-tab-trash').click(); // click Trash tab
    await page.waitForLoadState('networkidle');
    await waitForSelectorToBeVisible(page, '.mailpoet-tab-trash.is-active');
    await page.$$('[aria-label="More"]')[0].click(); // click More
    await page.waitForLoadState('networkidle');
    await page.$$('.components-dropdown-menu__menu-item')[0].focus();
    await page.$$('.components-dropdown-menu__menu-item')[0].click(); // click Restore
    await page.waitForLoadState('networkidle');

    // Wait for the success notice message and confirm it
    const restoredFromTrashNotice =
      "//div[@class='notice-success'].//p[contains(text(),'was restored from the trash')]";
    describe(automationsPageTitle, () => {
      describe('automation-trash-restore: should be able to see restored from trash notice', () => {
        expect(page.locator(restoredFromTrashNotice)).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Automation_Trash_Restore_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default function automationTrashRestoreTest() {
  automationTrashRestore();
}
