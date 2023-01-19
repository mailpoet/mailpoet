import { MailPoet } from 'mailpoet';
import { State } from './types';

export function getInitialState(): State {
  return {
    taskList: {
      isTaskListHidden: window.mailpoet_homepage_data.taskListDismissed,
      tasksStatus: window.mailpoet_homepage_data.taskListStatus,
      canImportWooCommerceSubscribers:
        window.mailpoet_homepage_data.wooCustomersCount > 0,
      hasImportedSubscribers:
        window.mailpoet_homepage_data.subscribersCount > 10,
    },
    productDiscovery: {
      isHidden: window.mailpoet_homepage_data.productDiscoveryDismissed,
      tasksStatus: window.mailpoet_homepage_data.productDiscoveryStatus,
    },
    upsell: {
      isHidden: window.mailpoet_homepage_data.upsellDismissed,
    },
    isWooCommerceActive: MailPoet.isWoocommerceActive,
  };
}
