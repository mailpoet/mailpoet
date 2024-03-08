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
        theme: EditorColor[];
      };
      gradients: {
        default: EditorColor[];
      };
    };
  };
};

export type EmailEditorSettings = EditorSettings & ExperimentalSettings;

export type EmailStyles = {
  spacing: {
    blockGap: string;
    padding: {
      bottom: string;
      left: string;
      right: string;
      top: string;
    };
  };
  color: {
    background: string;
    text: string;
  };
  blocks: {
    "core/heading": {
      elements: {
        h1: {
          color: {
            text: string;
          };
          typography: {
            fontFamily: string;
            fontWeight: string;
          };
        };
      };
    };
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
  styles: EmailStyles;
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
