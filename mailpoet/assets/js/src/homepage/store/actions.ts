import {
  saveProductDiscoveryDismissed,
  saveTaskListDismissed,
} from 'homepage/store/controls';

export function* hideTaskList() {
  yield saveTaskListDismissed();
  return { type: 'SET_TASK_LIST_HIDDEN' };
}

export function* hideProductDiscovery() {
  yield saveProductDiscoveryDismissed();
  return { type: 'SET_PRODUCT_DISCOVERY_HIDDEN' };
}
