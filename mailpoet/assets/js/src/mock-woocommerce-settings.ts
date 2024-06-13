/**
 * This is a module that mocks the WooCommerce settings module.
 * Some of its functions are imported in MailPoet Automation integrations.
 * In our code we trigger call of only getSetting() with a very specific argument.
 */
import { MailPoet } from './mailpoet';

export function getSetting(settingName: string) {
  if (settingName === 'currency') {
    return MailPoet.WooCommerceStoreConfig;
  }
  throw new Error(
    `Unexpected call of @woocommerce/settings mock getSetting() with ${settingName} argument.`,
  );
}

export function getAdminLink() {
  throw new Error(
    `Unexpected call of @woocommerce/settings mock getAdminLink()`,
  );
}
