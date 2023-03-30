/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * External dependencies
 */
import { sleep } from 'k6';
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
} from '../config.js';
import { authenticate } from '../utils/helpers.js';

export async function onboardingWizzard() {
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
	await page.locator('[data-automation-id="check-yes-google-fonts"]').click();
	await page.locator('[data-automation-id="check-yes-help-improve"]').click();
	await page.locator('form > .mailpoet-wizard-continue-button').click();
	await page.waitForLoadState('networkidle');
	await page.locator('.mailpoet-wizard-step-content > p > a').click();
	sleep(2);
	await page.keyboard.press('Tab');
	await page.keyboard.press('Tab');
	await page.keyboard.press('Tab');
	await page.keyboard.press('Enter');
	await page.waitForNavigation('networkidle');
	await page.waitForLoadState('networkidle');

	sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
	page.close();
	browser.close();
}

export default async function onboardingWizzardTest() {
	await onboardingWizzard();
}
