/**
 * External dependencies
 */
import { getSetting } from "@woocommerce/settings";

const { newsletterDefaultText } = getSetting("mailpoet_data");
export default {
  text: {
    type: "string",
    default: newsletterDefaultText,
  },
};
