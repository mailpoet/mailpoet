import { State } from './types';

export function getInitialState(): State {
  return {
    taskList: {
      isTaskListHidden: window.mailpoet_homepage_data.task_list_dismissed,
      tasksStatus: window.mailpoet_homepage_data.task_list_status,
      canImportWooCommerceSubscribers:
        window.mailpoet_homepage_data.woo_customers_count > 0,
    },
  };
}
