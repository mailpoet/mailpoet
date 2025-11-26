/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const { optinEnabled, defaultText } = getSetting('mailpoet_data');
export const marketingOptinAttributes = {
  text: {
    type: 'string',
    default: defaultText,
  },
  lock: {
    type: 'object',
    default: {
      remove: !!optinEnabled,
      move: false,
    },
  },
};
