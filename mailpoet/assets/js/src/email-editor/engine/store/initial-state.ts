import { State } from './types';

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
    editorSettings: {
      allowedBlockTypes: [
        'core/paragraph',
        'core/heading',
        'core/column',
        'core/columns',
        'core/image',
      ],
    },
    preview: {
      deviceType: 'Desktop',
      toEmail: '',
      isModalOpened: false,
      isSendingPreviewEmail: false,
      sendingPreviewStatus: null,
    },
  };
}
