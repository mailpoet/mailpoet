import { MailPoet } from 'mailpoet';

let trackingDataLoading = null;

export function getTrackingData() {
  if (!trackingDataLoading) {
    trackingDataLoading = MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'analytics',
      action: 'getTrackingData',
    });
  }
  return trackingDataLoading;
}

export function mapFilterType(filter) {
  const action = filter.action;
  const filterType = filter.type;

  if (filterType === 'automations') {
    switch (action) {
      case 'enteredAutomation':
        return 'entered automation';
      case 'exitedAutomation':
        return 'exited automation';
      default:
        return '';
    }
  }

  // Email
  if (filterType === 'email') {
    switch (action) {
      case 'machineOpensAbsoluteCount':
        return 'number of machine-opens';
      case 'opensAbsoluteCount':
        return 'number of opens';
      case 'clicked':
        return 'clicked';
      case 'clickedAny':
        return 'clicked any email';
      case 'opened':
        return 'opened';
      case 'machineOpened':
        return 'machine-opened';
      case 'wasSent':
        return 'was sent';
      default:
        return '';
    }
  }
  // User Role
  if (filterType === 'userRole') {
    switch (action) {
      case 'subscriberTag':
        return 'subscriber tags';
      case 'subscribedToList':
        return 'subscribed to list';
      case 'subscriberScore':
        return 'score';
      case 'wordpressRole':
        return 'WordPress user role';
      case 'lastEngagementDate':
        return 'last engagement date';
      case 'lastClickDate':
        return 'last click date';
      case 'lastPurchaseDate':
        return 'last purchase date';
      case 'lastOpenDate':
        return 'last open date';
      case 'lastPageViewDate':
        return 'last page view date';
      case 'lastSendingDate':
        return 'last sending date';
      case 'subscriberFirstName':
        return 'first name';
      case 'subscriberLastName':
        return 'last name';
      case 'subscriberEmail':
        return 'email';
      case 'subscribedViaForm':
        return 'subscribed via form';
      default:
        return '';
    }
  }
  // WooCommerce
  if (filterType === 'woocommerce') {
    switch (action) {
      case 'customerInCountry':
        return 'is in country';
      case 'customerInPostalCode':
        return 'postal code';
      case 'customerInCity':
        return 'city';
      case 'purchasedCategory':
        return 'purchased in category';
      case 'purchasedProduct':
        return 'purchased product';
      case 'subscribedDate':
        return 'subscribed date';
      case 'totalSpent':
        return 'total spent';
      case 'purchaseDate':
        return 'purchase date';
      case 'averageSpent':
        return 'average order value';
      case 'singleOrderValue':
        return 'single order value';
      case 'usedPaymentMethod':
        return 'used payment method';
      case 'usedShippingMethod':
        return 'used shipping method';
      case 'numberOfReviews':
        return 'number of reviews';
      case 'usedCouponCode':
        return 'used coupon code';
      case 'numberOfOrders':
        return 'number of orders';
      default:
        return '';
    }
  }
  // WooCommerce Subscription
  if (
    filterType === 'woocommerceSubscription' &&
    action === 'hasActiveSubscription'
  )
    return 'has an active subscription';
  // WooCommerce Membership
  if (filterType === 'woocommerceMembership' && action === 'isMemberOf')
    return 'is active member of';

  return '';
}
