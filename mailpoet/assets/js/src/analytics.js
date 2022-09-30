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

  // Email
  if (filterType === 'email') {
    switch (action) {
      case 'machineOpensAbsoluteCount':
        return '# of machine-opens';
      case 'opensAbsoluteCount':
        return '# of opens';
      case 'numberOfOrders':
        return '# of orders';
      case 'clicked':
        return 'clicked';
      case 'clickedAny':
        return 'clicked any email';
      case 'opened':
        return 'opened';
      case 'machineOpened':
        return 'machine-opened';
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
      case 'mailpoetCustomField':
        return 'MailPoet custom field';
      default:
        return '';
    }
  }
  // WooCommerce
  if (filterType === 'woocommerce')
    switch (action) {
      case 'customerInCountry':
        return 'is in country';
      case 'purchasedCategory':
        return 'purchased in category';
      case 'purchasedProduct':
        return 'purchased product';
      case 'subscribedDate':
        return 'subscribed date';
      case 'totalSpent':
        return 'total spent';
      default:
        return '';
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
