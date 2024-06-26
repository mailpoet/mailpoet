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
  fromName,
  adminEmail,
} from '../config.js';
import { login } from '../utils/helpers.js';

export async function onboardingWizard() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the MailPoet Welcome Wizard page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-welcome-wizard#/steps/1`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#mailpoet_sender_form');
    await page.locator('input[name="senderName"]').fill(fromName);
    await page.locator('input[name="senderAddress"]').fill(adminEmail);
    await page.screenshot({
      path: screenshotPath + 'Onboarding_Wizard_01.png',
      fullPage: fullPageSet,
    });

    await page.waitForSelector('#mailpoet_sender_form');
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');

    await page
      .locator(
        '#mailpoet-wizard-3rd-party-libs > div > div > label:nth-child(1) > span',
      )
      .click();
    await page
      .locator(
        '#mailpoet-wizard-tracking > div.mailpoet-wizard-woocommerce-toggle > div > label:nth-child(1) > span',
      )
      .click();
    await page.locator('button[type="submit"]').click();

    sleep(1);

    await page.waitForSelector('span.mailpoet-form-yesno-yes');
    await page.locator('span.mailpoet-form-yesno-yes').click();
    await page.locator('button[type="submit"]').click();

    await page.locator('.mailpoet-wizard-step-content > p > a').click();

    sleep(2);

    await page.screenshot({
      path: screenshotPath + 'Onboarding_Wizard_02.png',
      fullPage: fullPageSet,
    });
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await page.waitForNavigation('networkidle');
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('[data-automation-id="send_with_settings_tab"]');

    await page.screenshot({
      path: screenshotPath + 'Onboarding_Wizard_03.png',
      fullPage: fullPageSet,
    });

    // Check if you see Send With tab at the end
    describe(settingsPageTitle, () => {
      describe('onboarding-wizard: should be able to see Send With tab present', () => {
        expect(
          page
            .locator('[data-automation-id="send_with_settings_tab"]')
            .innerText(),
        ).to.contain('Send With...');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Onboarding_Wizard_04.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function onboardingWizardTest() {
  await onboardingWizard();
}
