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

export function changePreviewDeviceType(previewDeviceType: string) {
  return {
    type: 'CHANGE_PREVIEW_DEVICE_TYPE',
    previewDeviceType,
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

export function* updateEmailProperty(name: string, subject: string) {
  const postId = select(storeName).getEmailPostId();
  // There can be a better way how to get the edited post data
  const editedPost = select(coreDataStore).getEditedEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const mailpoetData = editedPost?.mailpoet_data || {};
  yield dispatch(coreDataStore).editEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
    {
      mailpoet_data: {
        ...mailpoetData,
        [name]: subject,
      },
    },
  );
}
