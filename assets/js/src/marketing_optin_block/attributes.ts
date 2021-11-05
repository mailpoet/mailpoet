/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const { defaultText } = getSetting('mailpoet_data');
export default {
  text: {
    type: 'string',
    default: defaultText,
  },
};
