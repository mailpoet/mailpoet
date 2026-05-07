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
  clickFirstSelector,
  clickMenuItemByText,
  waitForSelectorToBeVisible,
} from '../utils/helpers.js';

const automationActionMenuSelector =
  '[data-automation-id="automation_listing"] tbody tr:first-child button[aria-label="Actions"][aria-haspopup="menu"]';

export async function automationTrashRestore() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Automations page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-automation`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.waitForSelector(automationActionMenuSelector);
    await page.screenshot({
      path: screenshotPath + 'Automation_Trash_Restore_01.png',
      fullPage: fullPageSet,
    });

    // Move to trash one of the existing automation listing
    await clickFirstSelector(page, automationActionMenuSelector);
    await clickMenuItemByText(page, 'Trash');
    await page.waitForLoadState('networkidle');
    await page.locator('div.components-flex > button.is-primary').focus();
    await page.locator('div.components-flex > button.is-primary').click(); // click Move to trash
    await page.waitForLoadState('networkidle');

    // Wait for the success notice message and confirm it
    const movedToTrashNotice =
      "//div[@class='notice-success'].//p[contains(text(),'was moved to the trash')]";
    const noticeElement = await page.locator(movedToTrashNotice);
    describe(automationsPageTitle, () => {
      describe('automation-trash-restore: should be able to see moved to trash notice', async () => {
        expect(noticeElement).to.exist;
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
    await page.waitForSelector(automationActionMenuSelector);
    await clickFirstSelector(page, automationActionMenuSelector);
    await page.waitForLoadState('networkidle');
    await clickMenuItemByText(page, 'Restore');
    await page.waitForLoadState('networkidle');

    // Wait for the success notice message and confirm it
    const restoredFromTrashNotice =
      "//div[@class='notice-success'].//p[contains(text(),'was restored from the trash')]";
    const noticeElementTwo = await page.locator(restoredFromTrashNotice);
    describe(automationsPageTitle, () => {
      describe('automation-trash-restore: should be able to see restored from trash notice', async () => {
        expect(noticeElementTwo).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Automation_Trash_Restore_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default function automationTrashRestoreTest() {
  automationTrashRestore();
}
