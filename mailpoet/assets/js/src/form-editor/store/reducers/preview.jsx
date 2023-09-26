export const showPreview = (state) => ({
  ...state,
  isPreviewShown: true,
});

export const hidePreview = (state) => ({
  ...state,
  isPreviewShown: false,
  isPreviewReady: false,
});

export const previewDataSaved = (state) => ({
  ...state,
  isPreviewReady: true,
});

export const previewDataNotSaved = (state) => ({
  ...state,
  isPreviewReady: false,
});

export const changePreviewSettings = (state, { settings }) => ({
  ...state,
  previewSettings: settings,
});
