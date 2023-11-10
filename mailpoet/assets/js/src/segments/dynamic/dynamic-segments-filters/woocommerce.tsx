import { useSelect } from '@wordpress/data';
import { FilterProps, WooCommerceFormItem } from '../types';
import {
  DateFieldsDefaultBefore,
  validateDateField,
} from './fields/date-fields';
import { storeName } from '../store';
import { WooCommerceActionTypes } from './woocommerce-options';
import {
  PurchasedProductFields,
  validatePurchasedProduct,
} from './fields/woocommerce/purchased-product';
import {
  PurchasedCategoryFields,
  validatePurchasedCategory,
} from './fields/woocommerce/purchased-category';
import {
  CustomerInCountryFields,
  validateCustomerInCountry,
} from './fields/woocommerce/customer-in-country';
import {
  NumberOfOrdersFields,
  validateNumberOfOrders,
} from './fields/woocommerce/number-of-orders';
import {
  SingleOrderValueFields,
  validateSingleOrderValue,
} from './fields/woocommerce/single-order-value';
import {
  TotalSpentFields,
  validateTotalSpent,
} from './fields/woocommerce/total-spent';
import {
  AverageSpentFields,
  validateAverageSpent,
} from './fields/woocommerce/average-spent';
import {
  UsedPaymentMethodFields,
  validateUsedPaymentMethod,
} from './fields/woocommerce/used-payment-method';
import {
  UsedShippingMethodFields,
  validateUsedShippingMethod,
} from './fields/woocommerce/used-shipping-method';
import { TextField, validateTextField } from './fields/text-field';
import {
  NumberOfReviewsFields,
  validateNumberOfReviews,
} from './fields/woocommerce/number-of-reviews';
import {
  UsedCouponCodeFields,
  validateUsedCouponCode,
} from './fields/woocommerce/used-coupon-code';
import {
  PurchasedWithAttributeFields,
  validatePurchasedWithAttribute,
} from './fields/woocommerce/purchased-with-attribute';

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
  if (
    [
      WooCommerceActionTypes.NUMBER_OF_ORDERS,
      WooCommerceActionTypes.NUMBER_OF_ORDERS_WITH_COUPON,
    ].includes(formItems.action as WooCommerceActionTypes)
  ) {
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
  if (formItems.action === WooCommerceActionTypes.USED_SHIPPING_METHOD) {
    return validateUsedShippingMethod(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.PURCHASE_DATE) {
    return validateDateField(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.NUMBER_OF_REVIEWS) {
    return validateNumberOfReviews(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.USED_COUPON_CODE) {
    return validateUsedCouponCode(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.FIRST_ORDER) {
    return validateDateField(formItems);
  }
  if (formItems.action === WooCommerceActionTypes.PURCHASED_WITH_ATTRIBUTE) {
    return validatePurchasedWithAttribute(formItems);
  }
  if (
    [
      WooCommerceActionTypes.CUSTOMER_IN_POSTAL_CODE,
      WooCommerceActionTypes.CUSTOMER_IN_CITY,
    ].includes(formItems.action as WooCommerceActionTypes)
  ) {
    return validateTextField(formItems);
  }
  return true;
}

const componentsMap = {
  [WooCommerceActionTypes.CUSTOMER_IN_COUNTRY]: CustomerInCountryFields,
  [WooCommerceActionTypes.CUSTOMER_IN_CITY]: TextField,
  [WooCommerceActionTypes.CUSTOMER_IN_POSTAL_CODE]: TextField,
  [WooCommerceActionTypes.NUMBER_OF_ORDERS]: NumberOfOrdersFields,
  [WooCommerceActionTypes.NUMBER_OF_ORDERS_WITH_COUPON]: NumberOfOrdersFields,
  [WooCommerceActionTypes.NUMBER_OF_REVIEWS]: NumberOfReviewsFields,
  [WooCommerceActionTypes.PURCHASE_DATE]: DateFieldsDefaultBefore,
  [WooCommerceActionTypes.PURCHASED_PRODUCT]: PurchasedProductFields,
  [WooCommerceActionTypes.PURCHASED_CATEGORY]: PurchasedCategoryFields,
  [WooCommerceActionTypes.PURCHASED_WITH_ATTRIBUTE]:
    PurchasedWithAttributeFields,
  [WooCommerceActionTypes.SINGLE_ORDER_VALUE]: SingleOrderValueFields,
  [WooCommerceActionTypes.TOTAL_SPENT]: TotalSpentFields,
  [WooCommerceActionTypes.AVERAGE_SPENT]: AverageSpentFields,
  [WooCommerceActionTypes.USED_COUPON_CODE]: UsedCouponCodeFields,
  [WooCommerceActionTypes.USED_PAYMENT_METHOD]: UsedPaymentMethodFields,
  [WooCommerceActionTypes.USED_SHIPPING_METHOD]: UsedShippingMethodFields,
  [WooCommerceActionTypes.FIRST_ORDER]: DateFieldsDefaultBefore,
};

export function WooCommerceFields({ filterIndex }: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const Component = componentsMap[segment.action];

  if (!Component) {
    return null;
  }

  return <Component filterIndex={filterIndex} />;
}
