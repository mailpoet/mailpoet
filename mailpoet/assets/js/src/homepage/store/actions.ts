import { saveTaskListDismissed } from 'homepage/store/controls';

export function* hideTaskList() {
  yield saveTaskListDismissed();
  return { type: 'SET_TASK_LIST_HIDDEN' };
}
