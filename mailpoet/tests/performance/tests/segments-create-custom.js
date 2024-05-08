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
  defaultListName,
  segmentsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, selectInReact, focusAndClick } from '../utils/helpers.js';

export async function segmentsCreateCustom() {
  const page = browser.newPage();

  try {
    const complexSegmentName =
      'Complex Segment ' + Math.floor(Math.random() * 9999 + 1);

    // Log in to WP Admin
    await login(page);

    // Go to the segments page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-segments`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_01.png',
      fullPage: fullPageSet,
    });

    // Click to add a new segment
    await page.waitForSelector('[data-automation-id="new-segment"]');
    await page.locator('[data-automation-id="new-segment"]').click();
    await page.waitForSelector('[data-automation-id="new-custom-segment"]');
    await page.locator('[data-automation-id="new-custom-segment"]').click();
    await page
      .locator('[data-automation-id="input-name"]')
      .type(complexSegmentName, { delay: 25 });

    // Select "Subscribed to a list" action
    await selectInReact(page, '#react-select-2-input', 'subscribed to list');
    await selectInReact(page, '#react-select-4-input', defaultListName);
    await page.waitForSelector('.mailpoet-form-notice-message');
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see calculating message 1st time', () => {
        expect(
          page.locator('.mailpoet-form-notice-message').innerText(),
        ).to.contain('Calculating segment size…');
      });
    });
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_02.png',
      fullPage: fullPageSet,
    });

    // Click to add a new segment action
    await page
      .locator('div.mailpoet-segments-conditions-bottom > button')
      .click();

    // Select "Subscribed date" action
    await selectInReact(page, '#react-select-5-input', 'subscribed date');
    await page.waitForSelector('.mailpoet-form-notice-message');
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see calculating message 2nd time', () => {
        expect(
          page.locator('.mailpoet-form-notice-message').innerText(),
        ).to.contain('Calculating segment size…');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_03.png',
      fullPage: fullPageSet,
    });

    // Click to add a new segment action
    await page
      .locator('div.mailpoet-segments-conditions-bottom > button')
      .click();

    // WordPress user role action has been automatically added
    // Select a WP user role
    await selectInReact(page, '#react-select-8-input', 'Administrator');
    await page.waitForSelector('.mailpoet-form-notice-message');
    await page.waitForLoadState('networkidle');
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see Calculating message 3rd time', () => {
        expect(
          page.locator('.mailpoet-form-notice-message').innerText(),
        ).to.contain('Calculating segment size…');
      });
    });
    await page
      .locator('[data-automation-id="dynamic-segment-condition-type-or"]')
      .click();
    await page.waitForSelector('.mailpoet-form-notice-message');
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_04.png',
      fullPage: fullPageSet,
    });

    // Save the segment
    const calculatedMessage =
      "//div[@class='mailpoet-form-field'].//span[starts-with(text(),'This segment has')]";
    await calculatedMessage.waitFor();
    await focusAndClick(page, 'div.mailpoet-form-actions > button');
    await page.waitForSelector('[data-automation-id="filters_all"]', {
      state: 'visible',
    });
    const segmentUpdatedMessage =
      "//div[@class='notice-success'].//p[starts-with(text(),'Segment successfully updated!')]";
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see Segment Updated message', () => {
        expect(page.locator(segmentUpdatedMessage)).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_05.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default async function segmentsCreateCustomTest() {
  await segmentsCreateCustom();
}
