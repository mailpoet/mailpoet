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
