import CurrencyFactory from '@woocommerce/currency';
import { MailPoet } from '../../../../../mailpoet';

export function formattedPrice(price: number): string {
  const storeCurrency = CurrencyFactory(MailPoet.WooCommerceStoreConfig);
  return storeCurrency.formatAmount(price);
}
