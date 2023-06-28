/**
 * Internal dependencies
 */
import { baseURL, adminUsername, adminPassword } from '../config.js';
/* global Promise */

// WordPress login authorization
export async function login(page) {
  // Go to WP Admin login page
  await Promise.all([
    page.goto(`${baseURL}/wp-login.php`, { waitUntil: 'networkidle' }),
    page.waitForSelector('#user_login'),
  ]);
  // Enter login credentials and login
  await page.waitForLoadState('networkidle');
  await page.locator('input[name="log"]').type(`${adminUsername}`);
  await page.locator('input[name="pwd"]').type(`${adminPassword}`);
  // Wait for asynchronous operations to complete
  await Promise.all([
    page.waitForNavigation(),
    page.locator('input[name="wp-submit"]').click(),
  ]);
}

// Select a segment or a list from a select2 search field
export async function selectInSelect2(page, listName) {
  // Type a list name from a dropdown and hit Enter
  await page.locator('.select2-selection').type(listName);
  await page.keyboard.press('Enter');
}

// Select a segment or a list from a react search field
export async function selectInReact(page, reactSelector, reactValue) {
  // Type a list name from a dropdown and hit Enter
  await page.locator(reactSelector).type(reactValue);
  await page.keyboard.press('Enter');
}

// Wait and click the element with waiting for navigation
export async function waitAndClick(page, elementName) {
  await page.waitForSelector(elementName);
  await page.locator(elementName).click();
  await page.waitForNavigation();
}

// Wait for selector to be visible
export async function waitForSelectorToBeVisible(page, element) {
  await page.locator(element).waitFor({ state: 'visible' });
}
