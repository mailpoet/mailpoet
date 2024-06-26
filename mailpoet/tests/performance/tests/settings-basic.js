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
    describe(settingsPageTitle, () => {
      describe('settings-basic: should be able to see Settings Saved message', () => {
        expect(page.locator(locator)).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Settings_Basic_02.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function settingsBasicTest() {
  await settingsBasic();
}
