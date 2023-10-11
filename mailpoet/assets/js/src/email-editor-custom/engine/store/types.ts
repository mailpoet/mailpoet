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
  previewDeviceType: string;
};

export type MailPoetEmailData = {
  id: number;
  subject: string;
  preheader: string;
  preview_url: string;
};

export type Feature = 'fullscreenMode' | 'showIconLabels';
