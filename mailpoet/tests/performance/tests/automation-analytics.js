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
import { login } from '../utils/helpers.js';

export async function automationAnalytics() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Automation Analytics page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-automation-analytics&id=142`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Automation_Analytics_01.png',
      fullPage: fullPageSet,
    });

    // Check Emails tab
    await page.locator('.mailpoet-analytics-tab-emails').click();
    await page.waitForSelector('.mailpoet-automation-analytics-email-name');
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-analytics: should be able to see Emails tab loaded', () => {
        expect(page.$$('.mailpoet-automation-analytics-email-name')).to.exist;
      });
    });

    // Check Orders tab
    await page.locator('.mailpoet-analytics-tab-orders').click();
    await page.waitForSelector('.mailpoet-analytics-filter-controls');
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-analytics: should be able to see Orders tab loaded', () => {
        expect(page.$$('.mailpoet-analytics-filter-controls')).to.exist;
      });
    });

    // Check Subscribers tab
    await page.locator('.mailpoet-analytics-tab-subscribers').click();
    await page.waitForSelector('.mailpoet-analytics-multiselect');
    // Switch to second page using pagination
    await page.locator('.woocommerce-pagination__page-picker-input').fill('2');
    await page.locator('.components-text-control__input').click();
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-analytics: should be able to see Subscribers items loaded', () => {
        expect(page.$$('.woocommerce-table__item')[0]).to.exist;
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Automation_Analytics_02.png',
      fullPage: fullPageSet,
    });

    // Filter results by date range Today
    await page.locator('.woocommerce-dropdown-button').click();
    await page.locator('//span[contains(text(),"Today")]').click();
    await page.locator('.woocommerce-filters-date__button').click();
    await page.waitForLoadState('networkidle');

    // Filter results by date range Year to date
    await page.locator('.woocommerce-dropdown-button').click();
    await page.locator('//span[contains(text(),"Year to date")]').click();
    await page.locator('.woocommerce-filters-date__button').click();
    await page.waitForLoadState('networkidle');

    describe(automationsPageTitle, () => {
      describe('automation-analytics: should be able to see items in the listing', () => {
        expect(page.$$('.mailpoet-analytics-orders__customer')[0]).to.exist;
      });
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default function automationAnalyticsTest() {
  automationAnalytics();
}
