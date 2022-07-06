import jQuery from 'jquery';

/**
 * Parsely Helper functions
 * @see http://parsleyjs.org/doc/index.html
 */

/**
 * Alias to Parsely isValid method
 *
 * Check if the field is valid or not. Does not affect UI nor fires events.
 *
 * @param {string} domElementSelector jQuery DOM Selector
 * @returns {boolean|null} true if all ok or null if some validations are still pending
 */
export const isFieldValid = (domElementSelector: string) =>
  jQuery(domElementSelector).parsley().isValid();

/**
 * Alias to Parsely validate method
 *
 * Validate Field. Fires events and affects UI.
 *
 * @param {string} domElementSelector jQuery DOM Selector
 * @returns {boolean|null} true if all ok or null if some validations are still pending
 */
export const validateField = (domElementSelector: string) =>
  jQuery(domElementSelector).parsley().validate();

/**
 * Check if an Error message with this fieldName already exist on the DOM
 * @param {string} parsleyFieldName Parsely Error name
 * @returns {boolean}
 */
export const doesErrorFieldExist = (parsleyFieldName: string) =>
  !!jQuery(document).find(`.parsley-${parsleyFieldName}`).length;

/**
 * Alias to Parsely addError and updateError methods
 *
 * If an Error message with this fieldName already exist on the DOM, Update the Error message
 * else
 * Create a new Error message
 * @param {string} domElementSelector jQuery DOM Selector
 * @param {string} parsleyFieldName Parsely Error name
 * @param {string} errorMessage
 */
export const addOrUpdateError = (
  domElementSelector: string,
  parsleyFieldName: string,
  errorMessage: string,
) => {
  if (doesErrorFieldExist(parsleyFieldName)) {
    jQuery(domElementSelector).parsley().updateError(parsleyFieldName, {
      message: errorMessage,
      updateClass: true,
    });
  } else {
    jQuery(domElementSelector).parsley().addError(parsleyFieldName, {
      message: errorMessage,
      updateClass: true,
    });
  }
};

/**
 * Alias to Parsely removeError method
 *
 * Remove an already present error message.
 *
 * @param {string} domElementSelector jQuery DOM Selector
 * @param {string} parsleyFieldName Parsely Error name
 * @returns
 */
export const removeError = (
  domElementSelector: string,
  parsleyFieldName: string,
) => {
  if (!doesErrorFieldExist(parsleyFieldName)) return; // do nothing if error message does not exist

  jQuery(domElementSelector)
    .parsley()
    .removeError(parsleyFieldName, { updateClass: true });
};
