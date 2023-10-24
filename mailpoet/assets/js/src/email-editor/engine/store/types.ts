import { EditorSettings, EditorColor } from '@wordpress/block-editor';

export enum SendingPreviewStatus {
  SUCCESS = 'success',
  ERROR = 'error',
}

export type ExperimentalSettings = {
  __experimentalFeatures: {
    color: {
      custom: boolean;
      text: boolean;
      background: boolean;
      customGradient: boolean;
      defaultPalette: boolean;
      palette: {
        default: EditorColor[];
      };
      gradients: {
        default: EditorColor[];
      };
    };
  };
};

export type EmailEditorSettings = EditorSettings & ExperimentalSettings;

export type State = {
  inserterSidebar: {
    isOpened: boolean;
  };
  listviewSidebar: {
    isOpened: boolean;
  };
  postId: number;
  editorSettings: EmailEditorSettings;
  preview: {
    deviceType: string;
    toEmail: string;
    isModalOpened: boolean;
    isSendingPreviewEmail: boolean;
    sendingPreviewStatus: SendingPreviewStatus | null;
  };
};

export type MailPoetEmailData = {
  id: number;
  subject: string;
  preheader: string;
  preview_url: string;
};

export type Feature = 'fullscreenMode' | 'showIconLabels' | 'fixedToolbar';
