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
  defaultListName,
  listsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { authenticate, selectInReact } from '../utils/helpers.js';

export async function listsComplexSegment() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  const complexSegmentName =
    'Complex Segment ' + Math.floor(Math.random() * 9999 + 1);

  // Go to the page
  await page.goto(
    `${baseURL}/wp-admin/admin.php?page=mailpoet-segments#/lists`,
    {
      waitUntil: 'networkidle',
    },
  );

  // Log in to WP Admin
  authenticate(page);

  // Wait for async actions
  await page.waitForNavigation({ waitUntil: 'networkidle' });

  await page.screenshot({
    path: screenshotPath + 'Lists_Complex_Segment_01.png',
    fullPage: fullPageSet,
  });

  // Click to add a new segment
  page.waitForSelector('[data-automation-id="new-segment"]');
  page.locator('[data-automation-id="new-segment"]').click();
  page
    .locator('[data-automation-id="input-name"]')
    .type(complexSegmentName, { delay: 25 });

  // Select "Subscribed to a list" action
  selectInReact(page, '#react-select-2-input', 'subscribed to list');
  selectInReact(page, '#react-select-4-input', defaultListName);
  page.waitForSelector('.mailpoet-form-notice-message');
  describe(listsPageTitle, () => {
    describe('should be able to see calculating message', () => {
      expect(
        page.locator('.mailpoet-form-notice-message').innerText(),
      ).to.contain('Calculating segment size…');
    });
  });
  page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + 'Lists_Complex_Segment_02.png',
    fullPage: fullPageSet,
  });

  // Click to add a new segment action
  page
    .locator(
      '#segments_container > form > div > div.mailpoet-segments-segments-section > button',
    )
    .click();

  // Select "Subscribed date" action
  selectInReact(page, '#react-select-5-input', 'subscribed date');
  page.waitForSelector('.mailpoet-form-notice-message');
  describe(listsPageTitle, () => {
    describe('should be able to see calculating message', () => {
      expect(
        page.locator('.mailpoet-form-notice-message').innerText(),
      ).to.contain('Calculating segment size…');
    });
  });

  await page.screenshot({
    path: screenshotPath + 'Lists_Complex_Segment_03.png',
    fullPage: fullPageSet,
  });

  // Click to add a new segment action
  page
    .locator(
      '#segments_container > form > div > div.mailpoet-segments-segments-section > button',
    )
    .click();

  // WordPress user role action has been automatically added
  // Select a WP user role
  selectInReact(page, '#react-select-8-input', 'Administrator');
  page.waitForSelector('.mailpoet-form-notice-message');
  page.waitForLoadState('networkidle');
  describe(listsPageTitle, () => {
    describe('should be able to see Calculating message', () => {
      expect(
        page.locator('.mailpoet-form-notice-message').innerText(),
      ).to.contain('Calculating segment size…');
    });
  });
  page
    .locator('[data-automation-id="dynamic-segment-condition-type-or"]')
    .click();
  page.waitForSelector('.mailpoet-form-notice-message');
  page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + 'Lists_Complex_Segment_04.png',
    fullPage: fullPageSet,
  });

  // Save the segment
  await page
    .locator(
      '#segments_container > form > div > div.mailpoet-form-actions > button > span',
    )
    .click();
  page.waitForSelector('[data-automation-id="filters_all"]', {
    state: 'visible',
  });
  describe(listsPageTitle, () => {
    describe('should be able to see Segment Updated message', () => {
      expect(page.locator('div.notice').innerText()).to.contain(
        'Segment successfully updated!',
      );
    });
  });
  await page.waitForLoadState('networkidle');

  // Thinking time and closing
  sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  page.close();
  browser.close();
}

export default async function listsComplexSegmentTest() {
  await listsComplexSegment();
}
