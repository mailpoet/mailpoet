/**
 * Storybook has an issue in version 6.0 that addon for actions is the cause of many errors.
 *
 * Example:
 * "Warning: This synthetic event is reused for performance reasons. If you're seeing this,
 *  you're adding a new property in the synthetic event object. The property is never released.
 *  See https://fb.me/react-event-pooling for more information."
 *
 * This action is the workaround for this issue.
 */

import { action as brokenAction } from '@storybook/addon-actions'; // eslint-disable-line import/no-extraneous-dependencies

export const action: typeof brokenAction = (actionName) => {
  const beacon = brokenAction(actionName);
  return (eventObj, ...args) => {
    beacon({ ...eventObj, view: undefined }, ...args);
  };
};
