/* eslint-disable no-shadow */
/* eslint-disable import/no-unresolved */
/**
 * Internal dependencies
 */
import { adminUsername, adminPassword } from '../config.js';
/* global Promise */

// WordPress login authorization
export function login(page) {
  // Enter login credentials and login
  page.locator('input[name="log"]').type(`${adminUsername}`);
  page.locator('input[name="pwd"]').type(`${adminPassword}`);
  // Wait for asynchronous operations to complete
  return Promise.all([
    page.waitForNavigation(),
    page.locator('input[name="wp-submit"]').click(),
  ]);
}
