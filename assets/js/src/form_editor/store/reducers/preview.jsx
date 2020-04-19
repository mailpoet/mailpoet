export const showPreview = (state) => ({
  ...state,
  isPreviewShown: true,
});

export const hidePreview = (state) => ({
  ...state,
  isPreviewShown: false,
  previewDataSaved: false,
});

export const previewDataSaved = (state) => ({
  ...state,
  previewDataSaved: true,
});

export const previewDataNotSaved = (state) => ({
  ...state,
  previewDataSaved: false,
});
