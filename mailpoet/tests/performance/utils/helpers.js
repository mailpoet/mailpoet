/**
 * Internal dependencies
 */
import { baseURL, adminUsername, adminPassword } from '../config.js';
/* global Promise */

// WordPress login authorization
export async function login(page) {
  // Go to WP Admin login page
  Promise.all([
    page.goto(`${baseURL}/wp-admin/`, { waitUntil: 'networkidle' }),
    page.waitForSelector('#user_login'),
  ]);
  // Enter login credentials and login
  await page.waitForLoadState('networkidle');
  await page.locator('input[name="log"]').type(`${adminUsername}`);
  await page.locator('input[name="pwd"]').type(`${adminPassword}`);
  // Wait for asynchronous operations to complete
  return Promise.all([
    page.waitForNavigation(),
    page.locator('input[name="wp-submit"]').click(),
  ]);
}

// Select a segment or a list from a select2 search field
export function selectInSelect2(page, listName) {
  // Type a list name from a dropdown and hit Enter
  page.locator('.select2-selection').type(listName);
  page.keyboard.press('Enter');
}

// Select a segment or a list from a react search field
export function selectInReact(page, reactSelector, reactValue) {
  // Type a list name from a dropdown and hit Enter
  page.locator(reactSelector).type(reactValue);
  page.keyboard.press('Enter');
}

// Wait and click the element with waiting for navigation
export function waitAndClick(page, elementName) {
  page.waitForSelector(elementName);
  page.locator(elementName).click();
  page.waitForNavigation({ waitUntil: 'networkidle' });
}

// Wait for selector to be visible
export async function waitForSelectorToBeVisible(page, element) {
  await page.locator(element).waitFor({ state: 'visible' });
}
