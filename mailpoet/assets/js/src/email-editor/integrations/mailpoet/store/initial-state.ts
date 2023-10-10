import { State } from './types';

export const getInitialState = (): State => ({
  savedState: 'saved',
  previewToEmail: window.MailPoetEmailEditor.current_wp_user_email,
  isSendingPreviewEmail: false,
  sendingPreviewStatus: null,
});
