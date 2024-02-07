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
}

// Wait for selector to be visible
export async function waitForSelectorToBeVisible(page, element) {
  await page.locator(element).waitFor({ state: 'visible' });
}

// Add an item to the automation workflow
export async function addActionTriggerItemToWorkflow(page, actionName) {
  await page.locator('.components-search-control__input').type(actionName);
  await page.keyboard.press('Tab');
  await page.keyboard.press('Tab');
  await page.keyboard.press('Enter');
}

// Add value to an action in automations workflow
export async function addValueToActionInWorkflow(page, actionValue) {
  await page.locator('.components-form-token-field__input').type(actionValue);
  await page.keyboard.press('Enter');
}

// Activate the automation workflow while in the workflow
export async function activateWorkflow(page) {
  await Promise.all([
    page.locator('.editor-post-publish-button').click(),
    page
      .locator('.mailpoet-automation-activate-panel__header-activate-button')
      .click(),
    page.waitForSelector('.components-snackbar__content'),
  ]);
}

// Click button with text
export async function buttonWithText(page, buttonText) {
  const button = page.locator(`//button[text()="${buttonText}"]`);
  await button.click();
}

// Click to design email in the workflow and save it
export async function designEmailInWorkflow(page) {
  // Click Design automation email button
  await Promise.all([
    page.waitForNavigation(),
    page.locator('.mailpoet-automation-button-sidebar-primary').click(),
  ]);
  await page.waitForLoadState('networkidle');
  await page.waitForSelector('[data-automation-id="templates-standard"]');

  // Switch to a Standard templates tab and select the 2nd template
  await page.locator('[data-automation-id="templates-standard"]').click();
  await Promise.all([
    page.waitForNavigation(),
    page.locator('[data-automation-id="select_template_1"]').click(),
  ]);
  await page.waitForSelector('.mailpoet_loading');
  await page.waitForLoadState('networkidle');

  // Click to save and get back to the workflow
  await Promise.all([
    page.waitForNavigation(),
    page.locator('input[value="Save and continue"').click(),
  ]);
  await page.waitForLoadState('networkidle');
}
