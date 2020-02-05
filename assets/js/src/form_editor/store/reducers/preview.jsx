export const showPreview = (state) => ({
  ...state,
  isPreviewShown: true,
});

export const hidePreview = (state) => ({
  ...state,
  isPreviewShown: false,
});
