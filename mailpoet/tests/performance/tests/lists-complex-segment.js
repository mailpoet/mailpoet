/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep, check } from 'k6';
import { chromium } from 'k6/experimental/browser';
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
import { authenticate, selectInReact } from '../utils/helpers.js';

export async function listsComplexSegment() {
  const browser = chromium.launch({
    headless: headlessSet,
    timeout: timeoutSet,
  });
  const page = browser.newPage();

  try {
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
    page.waitForLoadState('networkidle');

    // Click to add a new segment action
    page
      .locator(
        '#segments_container > form > div > div.mailpoet-segments-segments-section > button',
      )
      .click();

    // Select "Subscribed date" action
    selectInReact(page, '#react-select-5-input', 'subscribed date');

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
    check(page, {
      'confirmation message is visible for segment calculation': page
        .locator('.mailpoet-form-notice-message')
        .isVisible(),
    });
    page
      .locator('[data-automation-id="dynamic-segment-condition-type-or"]')
      .click();
    page.waitForSelector('.mailpoet-form-notice-message');
    page.waitForLoadState('networkidle');
    check(page, {
      'confirmation message is visible for segment calculation': page
        .locator('.mailpoet-form-notice-message')
        .isVisible(),
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
    check(page, {
      'segments tab is visible': page
        .locator('[data-automation-id="dynamic-segments-tab"]')
        .isVisible(),
    });
    check(page, {
      'segment is saved successfully': page
        .locator('.notice-success')
        .textContent('Segment successfully updated!'),
    });
    await page.waitForLoadState('networkidle');
  } finally {
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
    page.close();
    browser.close();
  }
}

export default async function listsComplexSegmentTest() {
  await listsComplexSegment();
}
