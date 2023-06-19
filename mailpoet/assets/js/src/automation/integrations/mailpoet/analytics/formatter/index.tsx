import CurrencyFactory from '@woocommerce/currency/build';
import { MailPoet } from '../../../../../mailpoet';

export function formattedPrice(price: number): string {
  const storeCurrency = CurrencyFactory(MailPoet.WooCommerceStoreConfig);
  return storeCurrency.formatAmount(price);
}
