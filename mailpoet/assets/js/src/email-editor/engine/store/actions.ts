import { dispatch, select } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as coreDataStore } from '@wordpress/core-data';
import { apiFetch } from '@wordpress/data-controls';
import { storeName, mainSidebarEmailKey } from './constants';
import { SendingPreviewStatus, State } from './types';

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

export function changePreviewDeviceType(deviceType: string) {
  return {
    type: 'CHANGE_PREVIEW_STATE',
    state: { deviceType },
  } as const;
}

export function togglePreviewModal(isOpen: boolean) {
  return {
    type: 'CHANGE_PREVIEW_STATE',
    state: { isModalOpened: isOpen } as Partial<State['preview']>,
  } as const;
}

export function updateSendPreviewEmail(toEmail: string) {
  return {
    type: 'CHANGE_PREVIEW_STATE',
    state: { toEmail } as Partial<State['preview']>,
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

export function* requestSendingNewsletterPreview(
  newsletterId: number,
  email: string,
) {
  // Initiate sending
  yield {
    type: 'CHANGE_PREVIEW_STATE',
    state: {
      sendingPreviewStatus: null,
      isSendingPreviewEmail: true,
    } as Partial<State['preview']>,
  } as const;
  try {
    const url = window.MailPoetEmailEditor.json_api_root;
    const token = window.MailPoetEmailEditor.api_token;
    const method = 'POST';
    const body = new FormData();
    body.append('token', token);
    body.append('action', 'mailpoet');
    body.append('api_version', window.MailPoetEmailEditor.api_version);
    body.append('endpoint', 'newsletters');
    body.append('method', 'sendPreview');
    body.append('data[subscriber]', email);
    body.append('data[id]', newsletterId.toString());
    yield apiFetch({
      url,
      method,
      body,
    });

    yield {
      type: 'CHANGE_PREVIEW_STATE',
      state: {
        sendingPreviewStatus: SendingPreviewStatus.SUCCESS,
        isSendingPreviewEmail: false,
      },
    };
  } catch (errorResponse) {
    yield {
      type: 'CHANGE_PREVIEW_STATE',
      state: {
        sendingPreviewStatus: SendingPreviewStatus.ERROR,
        isSendingPreviewEmail: false,
      },
    };
  }
}
