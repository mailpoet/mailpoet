export const placeFormBellowAllPosts = (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    hasUnsavedChanges: true,
    settings: {
      ...state.formData.settings,
      placeFormBellowAllPosts: action.place,
    },
  },
});

export const placeFormBellowAllPages = (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    hasUnsavedChanges: true,
    settings: {
      ...state.formData.settings,
      placeFormBellowAllPages: action.place,
    },
  },
});
