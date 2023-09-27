import { dispatch, select } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as coreDataStore } from '@wordpress/core-data';
import { storeName, mainSidebarEmailKey } from './constants';

export function toggleInserterSidebar() {
  return {
    type: 'TOGGLE_INSERTER_SIDEBAR',
  } as const;
}

export function toggleListviewSidebar() {
  return {
    type: 'TOGGLE_LISTVIEW_SIDEBAR',
  } as const;
}

export function* openSidebar(key = mainSidebarEmailKey) {
  yield dispatch(interfaceStore).enableComplementaryArea(storeName, key);
}

export const closeSidebar = () => {
  dispatch(interfaceStore).disableComplementaryArea(storeName);
};

export function* saveEditedEmail() {
  const postId = select(storeName).getEmailPostId();
  // This returns a promise
  yield dispatch(coreDataStore).saveEditedEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
    {},
  );
  // Todo Notice when promise is resolved
}
