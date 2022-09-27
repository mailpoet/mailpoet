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

  if (filterType === 'email' && action === 'machineOpensAbsoluteCount')
    return '# of machine-opens';
  if (filterType === 'email' && action === 'opensAbsoluteCount')
    return '# of opens';
  if (filterType === 'woocommerce' && action === 'numberOfOrders')
    return '# of orders';
  if (filterType === 'email' && action === 'clicked') return 'clicked';
  if (filterType === 'email' && action === 'clickedAny')
    return 'clicked any email';
  if (filterType === 'userRole' && action === 'subscriberScore') return 'score';
  if (filterType === 'userRole' && action === 'subscribedToList')
    return 'subscribed to list';
  if (filterType === 'email' && action === 'opened') return 'opened';
  if (filterType === 'email' && action === 'machineOpened')
    return 'machine-opened';
  if (filterType === 'woocommerceMembership' && action === 'isMemberOf')
    return 'is active member of';
  if (
    filterType === 'woocommerceSubscription' &&
    action === 'hasActiveSubscription'
  )
    return 'has an active subscription';
  if (filterType === 'woocommerce' && action === 'customerInCountry')
    return 'is in country';
  if (filterType === 'userRole' && action === 'mailpoetCustomField')
    return 'MailPoet custom field';
  if (filterType === 'woocommerce' && action === 'purchasedCategory')
    return 'purchased in category';
  if (filterType === 'woocommerce' && action === 'purchasedProduct')
    return 'purchased product';
  if (filterType === 'userRole' && action === 'subscribedDate')
    return 'subscribed date';
  if (filterType === 'woocommerce' && action === 'totalSpent')
    return 'total spent';
  if (filterType === 'woocommerce' && action === 'totalSpent')
    return 'total spent';
  if (filterType === 'userRole' && action === 'wordpressRole')
    return 'WordPress user role';
  if (filterType === 'userRole' && action === 'subscriberTag')
    return 'subscriber tags';

  return '';
}
