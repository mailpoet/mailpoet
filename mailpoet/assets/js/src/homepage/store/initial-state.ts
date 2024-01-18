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
      isNewUserForSenderDomainAuth:
        window.mailpoet_homepage_data.isNewUserForSenderDomainAuth,
      mssActive: window.mailpoet_mss_active,
      isFreeMailUser: window.mailpoet_homepage_data.isFreeMailUser,
    },
    productDiscovery: {
      isHidden: window.mailpoet_homepage_data.productDiscoveryDismissed,
      tasksStatus: window.mailpoet_homepage_data.productDiscoveryStatus,
    },
    upsell: {
      isHidden: window.mailpoet_homepage_data.upsellDismissed,
      upsellStatus: window.mailpoet_homepage_data.upsellStatus,
    },
    isWooCommerceActive: MailPoet.isWoocommerceActive,
    subscribersStats: window.mailpoet_homepage_data.subscribersStats,
    formsCount: window.mailpoet_homepage_data.formsCount,
  };
}
