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

export type EmailTheme = {
  version: number;
  styles: EmailStyles;
};

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
    background: {
      content: string;
      layout: string;
    };
    text: string;
  };
  typography: {
    fontSize: string;
    fontFamily: string;
    fontStyle: string;
    fontWeight: string;
    letterSpacing: string;
    lineHeight: string;
    textDecoration: string;
    textTransform:
      | 'none'
      | 'capitalize'
      | 'uppercase'
      | 'lowercase'
      | 'full-width'
      | 'full-size-kana';
  };
  elements: {
    heading: {
      color: {
        text: string;
      };
      typography: {
        fontSize: string;
        fontFamily: string;
        fontStyle: string;
        fontWeight: string;
        letterSpacing: string;
        lineHeight: string;
        textDecoration: string;
        textTransform:
          | 'none'
          | 'capitalize'
          | 'uppercase'
          | 'lowercase'
          | 'full-width'
          | 'full-size-kana';
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
  layout: EmailEditorLayout;
  theme: EmailTheme;
  autosaveInterval: number;
  cdnUrl: string;
  isPremiumPluginActive: boolean;
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
