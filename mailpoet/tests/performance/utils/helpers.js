/**
 * Internal dependencies
 */
import {
  baseURL,
  adminUsername,
  adminPassword,
  fromName,
  adminEmail,
  fullPageSet,
  screenshotPath,
} from '../config.js';
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
  await page.waitForLoadState('networkidle');
}

// Hide stale authorization notices in the shared performance test site.
// The tests measure MailPoet page performance, not long-lived fixture warnings.
export async function gotoMailPoetNewsletterPage(page, url) {
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    if (document.getElementById('mailpoet-performance-notice-overrides')) {
      return;
    }
    const style = document.createElement('style');
    style.id = 'mailpoet-performance-notice-overrides';
    style.textContent =
      [
        '[data-id="mailpoet_authorization_error"]',
        '[data-notice="unauthorized-email-in-newsletters-addresses-notice"]',
        '.mailpoet-js-error-unauthorized-emails-notice',
      ].join(',') + '{display:none!important;}';
    document.head.appendChild(style);
  });
  await page.waitForLoadState('networkidle');
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

// Focus and click the element
export async function focusAndClick(page, element) {
  await page.locator(element).waitFor({ trial: true });
  await page.locator(element).focus();
  await page.locator(element).click();
}

// Wait and click the element
export async function waitAndClick(page, element) {
  await page.waitForSelector(element);
  await page.locator(element).click();
}

// Wait and type on the element
export async function waitAndType(page, element, text) {
  await page.locator(element).waitFor({ state: 'visible' });
  await page.locator(element).type(text, { delay: 25 });
}

// Wait for selector to be visible
export async function waitForSelectorToBeVisible(page, element) {
  await page.locator(element).waitFor({ state: 'visible' });
}

// Wait for selector to be available
export async function waitForSelectorToBeClickable(page, element) {
  await page.locator(element).waitFor({ trial: true });
}

export async function waitForDataViews(
  page,
  rootSelector = '.mailpoet-dataviews',
) {
  await page.waitForSelector(
    `${rootSelector} table, ${rootSelector} .dataviews-no-results`,
  );
}

export async function typeInDataViewsSearch(page, text, options = {}) {
  await page.locator('.dataviews-search input').type(text, options);
}

export async function selectDataViewsPage(
  page,
  rootSelector = '.mailpoet-dataviews',
) {
  await page
    .locator(`${rootSelector} table thead input[type="checkbox"]`)
    .click();
  await page.waitForSelector(`${rootSelector} .dataviews-bulk-actions-footer`);
}

export async function clickDataViewsAction(
  page,
  actionLabel,
  rootSelector = '.mailpoet-dataviews',
) {
  const result = await page.evaluate(
    ({ label, selector }) => {
      const root = document.querySelector(selector);
      if (!root) return false;

      const clickMatching = (scope) => {
        const elements = Array.from(
          scope.querySelectorAll('button, [role="menuitem"]'),
        );
        const action = elements.find(
          (element) => element.textContent.trim() === label,
        );
        if (!action) return false;
        action.click();
        return true;
      };

      const bulkFooter = root.querySelector('.dataviews-bulk-actions-footer');
      if (bulkFooter && clickMatching(bulkFooter)) return true;

      const menuToggle = bulkFooter?.querySelector(
        'button[aria-haspopup="menu"]',
      );
      if (menuToggle) {
        menuToggle.click();
        return 'menu';
      }

      return false;
    },
    { label: actionLabel, selector: rootSelector },
  );

  if (result === 'menu') {
    await clickMenuItemByText(page, actionLabel);
    return;
  }

  if (!result) {
    throw new Error(`No DataViews action found with text "${actionLabel}".`);
  }
}

// Add an item to the automation workflow
export async function addActionTriggerItemToWorkflow(page, actionName) {
  await page.locator('.components-input-control__input').type(actionName);
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
    page.waitForLoadState('networkidle'),
  ]);
}

// Click to design email in the workflow and save it
export async function designEmailInWorkflow(page) {
  // Fill the sender email and name
  await page.locator('input[type="text"]').waitFor({ state: 'visible' });
  await page.locator('input[type="text"]').fill(fromName);
  await page.locator('input[type="text"]').type(' '); // to avoid flakiness
  await page.locator('input[type="email"]').fill(adminEmail);

  await page.screenshot({
    path: screenshotPath + `Design_Email_In_Workflow_${Date.now()}.png`,
    fullPage: fullPageSet,
  });

  // Click Design automation email button
  await Promise.all([
    page.waitForNavigation(),
    page.locator('.mailpoet-automation-button-sidebar-primary').click(),
  ]);

  await page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + `Design_Email_In_Workflow_${Date.now()}.png`,
    fullPage: fullPageSet,
  });

  // Switch to a Standard templates tab and select the 2nd template
  await page
    .locator('[data-automation-id="templates-standard"]')
    .waitFor({ state: 'visible' });
  await page.locator('[data-automation-id="templates-standard"]').click();
  await Promise.all([
    page.waitForNavigation(),
    page.locator('[data-automation-id="select_template_1"]').click(),
  ]);

  await page.waitForLoadState('networkidle');

  await page.screenshot({
    path: screenshotPath + `Design_Email_In_Workflow_${Date.now()}.png`,
    fullPage: fullPageSet,
  });

  // Click to save and get back to the workflow
  await page
    .locator('input[value="Return back to the Automation"]')
    .waitFor({ state: 'visible' });
  try {
    await page.locator('#mailpoet_modal_close').click({ timeout: 3000 });
  } catch (error) {
    console.log("Newsletter tutorial video wasn't present.");
  }

  await Promise.all([
    page.waitForNavigation(),
    page.locator('input[value="Return back to the Automation"]').click(),
  ]);
  await page.waitForLoadState('networkidle');
}

// Wait and click first selector
export async function clickFirstSelector(page, selector) {
  // Wait for the selector to be available
  await page.waitForSelector(selector);

  // Get all selectors
  const selectors = await page.$$(selector);

  // Ensure that the first selector exists before trying to click
  if (selectors.length > 0) {
    await selectors[0].focus();
    await selectors[0].click();
  } else {
    throw new Error('No selector found on the page.');
  }
}

export async function clickMenuItemByText(page, text) {
  await page.waitForSelector('.components-popover__content [role="menuitem"]');
  const clicked = await page.evaluate((menuItemText) => {
    const menuItems = Array.from(
      document.querySelectorAll(
        '.components-popover__content [role="menuitem"]',
      ),
    );
    const menuItem = menuItems.find(
      (element) => element.textContent.trim() === menuItemText,
    );
    if (!menuItem) {
      return false;
    }
    menuItem.click();
    return true;
  }, text);
  if (!clicked) {
    throw new Error(`No menu item found with text "${text}".`);
  }
}
