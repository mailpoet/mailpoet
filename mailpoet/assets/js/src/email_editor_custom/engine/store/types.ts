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
};

export type MailPoetEmailData = {
  id: number;
};
