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
  segmentsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, focusAndClick } from '../utils/helpers.js';

export async function segmentsSelectTemplate() {
  const page = browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the segments page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-segments#/segment-templates`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForSelector('.wp-heading-inline');
    await page.screenshot({
      path: screenshotPath + 'Segments_Select_Template_01.png',
      fullPage: fullPageSet,
    });

    // Select any segment's template on page
    await Promise.all([
      page.$$('.mailpoet-templates-card')[0].click(), // this will randomly pick
      page.waitForNavigation(),
      page.waitForSelector('[data-automation-id="select-segment-action"]'),
      page.waitForLoadState('networkidle'),
    ]);

    // Save the segment
    await focusAndClick(page, 'button[type="submit"]');

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Select_Template_02.png',
      fullPage: fullPageSet,
    });

    await page.waitForSelector('[data-automation-id="select_all"]');
    const locator =
      "//div[@class='notice-success'].//p[starts-with(text(),'Segment successfully updated!')]";
    describe(segmentsPageTitle, () => {
      describe('segments-select-template: should be able to see Segment saved message', () => {
        expect(page.locator(locator)).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Select_Template_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    page.close();
    browser.context().close();
  }
}

export default async function segmentsSelectTemplateTest() {
  await segmentsSelectTemplate();
}
