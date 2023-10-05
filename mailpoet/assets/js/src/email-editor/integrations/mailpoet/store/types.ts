export enum SendingPreviewStatus {
  SUCCESS = 'success',
  ERROR = 'error',
}

export type State = {
  savedState: 'unsaved' | 'saving' | 'saved';
  previewToEmail: string;
  isSendingPreviewEmail: boolean;
  sendingPreviewStatus: SendingPreviewStatus | null;
};
