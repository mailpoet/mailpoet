import { useSelect } from '@wordpress/data';
import { WooCommerceFormItem, FilterProps } from '../types';
import { DateFields, validateDateField } from './fields/date_fields';
import { storeName } from '../store';
import { WooCommerceActionTypes } from './woocommerce_options';
import {
  PurchasedProductFields,
  validatePurchasedProduct,
} from './fields/woocommerce/purchased_product';
import {
  PurchasedCategoryFields,
  validatePurchasedCategory,
} from './fields/woocommerce/purchased_category';
import {
  CustomerInCountryFields,
  validateCustomerInCountry,
} from './fields/woocommerce/customer_in_country';
import {
  NumberOfOrdersFields,
  validateNumberOfOrders,
} from './fields/woocommerce/number_of_orders';
import {
  SingleOrderValueFields,
  validateSingleOrderValue,
} from './fields/woocommerce/single_order_value';
import {
  TotalSpentFields,
  validateTotalSpent,
} from './fields/woocommerce/total_spent';
import {
  AverageSpentFields,
  validateAverageSpent,
} from './fields/woocommerce/average_spent';
import {
  UsedPaymentMethodFields,
  validateUsedPaymentMethod,
} from './fields/woocommerce/used_payment_method';

export function validateWooCommerce(formItems: WooCommerceFormItem): boolean {
  if (
    !Object.values(WooCommerceActionTypes).some((v) => v === formItems.action)
  ) {
    return false;
  }
  if (formItems.action === WooCommerceActionTypes.PURCHASED_CATEGORY) {
    return validatePurchasedCategory(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.PURCHASED_PRODUCT) {
    return validatePurchasedProduct(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.CUSTOMER_IN_COUNTRY) {
    return validateCustomerInCountry(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.NUMBER_OF_ORDERS) {
    return validateNumberOfOrders(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.TOTAL_SPENT) {
    return validateTotalSpent(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.SINGLE_ORDER_VALUE) {
    return validateSingleOrderValue(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.AVERAGE_SPENT) {
    return validateAverageSpent(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.USED_PAYMENT_METHOD) {
    return validateUsedPaymentMethod(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.PURCHASE_DATE) {
    return validateDateField(formItems);
  }
  return true;
}

const componentsMap = {
  [WooCommerceActionTypes.CUSTOMER_IN_COUNTRY]: CustomerInCountryFields,
  [WooCommerceActionTypes.NUMBER_OF_ORDERS]: NumberOfOrdersFields,
  [WooCommerceActionTypes.PURCHASE_DATE]: DateFields,
  [WooCommerceActionTypes.PURCHASED_PRODUCT]: PurchasedProductFields,
  [WooCommerceActionTypes.PURCHASED_CATEGORY]: PurchasedCategoryFields,
  [WooCommerceActionTypes.SINGLE_ORDER_VALUE]: SingleOrderValueFields,
  [WooCommerceActionTypes.TOTAL_SPENT]: TotalSpentFields,
  [WooCommerceActionTypes.AVERAGE_SPENT]: AverageSpentFields,
  [WooCommerceActionTypes.USED_PAYMENT_METHOD]: UsedPaymentMethodFields,
};

export function WooCommerceFields({ filterIndex }: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const Component = componentsMap[segment.action];
  return <Component filterIndex={filterIndex} />;
}
