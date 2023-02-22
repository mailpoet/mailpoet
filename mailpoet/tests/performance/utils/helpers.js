/* eslint-disable import/no-unresolved */
/* eslint-disable import/no-default-export */
/**
 * Internal dependencies
 */
import { adminUsername, adminPassword } from '../config.js';
/* global Promise */

// WordPress login authorization
export function authenticate(page) {
  // Enter login credentials and login
  page.waitForNavigation({ waitUntil: 'networkidle' });
  page.locator('input[name="log"]').type(`${adminUsername}`);
  page.locator('input[name="pwd"]').type(`${adminPassword}`);
  // Wait for asynchronous operations to complete
  return Promise.all([
    page.waitForNavigation(),
    page.locator('input[name="wp-submit"]').click(),
  ]);
}

// Select a segment or a list from a select2 search field
export function selectInSelect2(page, listName) {
  // Click and write a list name from a dropdown
  page.locator('.select2-selection').type(listName);
  page.keyboard.press('Enter');
}

// Wait and click the element with waiting for navigation
export function waitAndClick(page, elementName) {
  page.waitForSelector(elementName);
  page.locator(elementName).click();
  page.waitForNavigation({ waitUntil: 'networkidle' });
}
