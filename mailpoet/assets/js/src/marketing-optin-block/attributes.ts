/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const { defaultText } = getSetting('mailpoet_data');
export const marketingOptinAttributes = {
  text: {
    type: 'string',
    default: defaultText,
  },
};
