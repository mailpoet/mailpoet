import { callApi } from 'common/controls/call_api';

export function saveTaskListDismissed() {
  return callApi({
    endpoint: 'settings',
    action: 'set',
    method: 'POST',
    data: {
      'homepage.task_list_dismissed': true,
    },
  });
}

export function saveProductDiscoveryDismissed() {
  return callApi({
    endpoint: 'settings',
    action: 'set',
    method: 'POST',
    data: {
      'homepage.product_discovery_dismissed': true,
    },
  });
}

export function saveUpsellDismissed() {
  return callApi({
    endpoint: 'settings',
    action: 'set',
    method: 'POST',
    data: {
      'homepage.upsell_dismissed': true,
    },
  });
}
