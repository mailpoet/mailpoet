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
