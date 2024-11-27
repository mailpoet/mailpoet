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
  settingsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login } from '../utils/helpers.js';

export async function settingsBasic() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Settings page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-settings#/basics`,
      {
        waitUntil: 'networkidle',
        timeout: 120000,
      },
    );

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Settings_Basic_01.png',
      fullPage: fullPageSet,
    });

    // Click to save the settings
    await page.locator('[data-automation-id="settings-submit-button"]').click();
    await page.waitForSelector('div.notice');
    await page.waitForLoadState('networkidle');

    // Check if there's notice about saved settings
    const locator =
      "//div[@class='notice-success'].//p[starts-with(text(),'Settings saved')]";
    const noticeElement = await page.locator(locator);
    describe(settingsPageTitle, () => {
      describe('settings-basic: should be able to see Settings Saved message', async () => {
        expect(noticeElement).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Settings_Basic_02.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function settingsBasicTest() {
  await settingsBasic();
}
