export enum SendingPreviewStatus {
  SUCCESS = 'success',
  ERROR = 'error',
}

export type State = {
  inserterSidebar: {
    isOpened: boolean;
  };
  listviewSidebar: {
    isOpened: boolean;
  };
  postId: number;
  editorSettings: {
    allowedBlockTypes: string[];
  };
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

export type Feature = 'fullscreenMode' | 'showIconLabels';
