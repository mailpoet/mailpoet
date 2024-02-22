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

export type EmailLayoutStyles = {
  width: string;
  background: string;
  padding: {
    bottom: string;
    left: string;
    right: string;
    top: string;
  };
};

export type EmailEditorLayout = {
  type: string;
  contentSize: string;
};

export type State = {
  inserterSidebar: {
    isOpened: boolean;
  };
  listviewSidebar: {
    isOpened: boolean;
  };
  settingsSidebar: {
    activeTab: string;
  };
  postId: number;
  editorSettings: EmailEditorSettings;
  layoutStyles: EmailLayoutStyles;
  layout: EmailEditorLayout;
  autosaveInterval: number;
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

export type Feature =
  | 'fullscreenMode'
  | 'showIconLabels'
  | 'fixedToolbar'
  | 'focusMode';
