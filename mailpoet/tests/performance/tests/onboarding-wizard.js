/**
 * External dependencies
 */
import { sleep } from 'k6';
import { chromium } from 'k6/experimental/browser';
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
  headlessSet,
  timeoutSet,
  settingsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { authenticate } from '../utils/helpers.js';

export async function onboardingWizard() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  // Go to the page
  await page.goto(
    `${baseURL}/wp-admin/admin.php?page=mailpoet-welcome-wizard#/steps/1`,
    {
      waitUntil: 'networkidle',
    },
  );

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await page.waitForNavigation({ waitUntil: 'networkidle' });
  await page.locator('#mailpoet_sender_form > a').click();
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
  await page
    .locator(
      '#mailpoet-wizard-container > div.mailpoet-steps-content > div > div.mailpoet-wizard-step-content > form > button > span',
    )
    .click();
  await page.waitForLoadState('networkidle');
  await page.locator('.mailpoet-wizard-step-content > p > a').click();
  sleep(2);
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  await page.keyboard.press('Enter');
  await page.waitForNavigation('networkidle');
  await page.waitForLoadState('networkidle');
  await page.waitForSelector('[data-automation-id="send_with_settings_tab"]');

  // Check if you see Send With tab at the end
  describe(settingsPageTitle, () => {
    describe('should be able to see Send With tab present', () => {
      expect(
        page
          .locator('[data-automation-id="send_with_settings_tab"]')
          .innerText(),
      ).to.contain('Send With...');
    });
  });

  // Take a screenshot of the finished test
  await page.screenshot({
    path: screenshotPath + '01_Onboarding_Wizard.png',
    fullPage: fullPageSet,
  });

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function onboardingWizardTest() {
  await onboardingWizard();
}
