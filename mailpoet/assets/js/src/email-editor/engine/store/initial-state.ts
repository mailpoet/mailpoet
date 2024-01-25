import { State } from './types';
import {
  getEditorLayout,
  getEditorSettings,
  getEmailLayoutStyles,
} from './settings';

export function getInitialState(): State {
  const searchParams = new URLSearchParams(window.location.search);
  const postId = parseInt(searchParams.get('postId'), 10);
  return {
    inserterSidebar: {
      isOpened: false,
    },
    listviewSidebar: {
      isOpened: false,
    },
    postId,
    editorSettings: getEditorSettings(),
    layoutStyles: getEmailLayoutStyles(),
    layout: getEditorLayout(),
    autosaveInterval: 60,
    preview: {
      deviceType: 'Desktop',
      toEmail: window.MailPoetEmailEditor.current_wp_user_email,
      isModalOpened: false,
      isSendingPreviewEmail: false,
      sendingPreviewStatus: null,
    },
  };
}
