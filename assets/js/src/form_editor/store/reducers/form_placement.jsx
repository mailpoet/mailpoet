import { curry } from 'lodash';

const formPlacement = curry((placement, state, action) => ({
  ...state,
  hasUnsavedChanges: true,
  formData: {
    ...state.formData,
    settings: {
      ...state.formData.settings,
      [placement]: action.place,
    },
  },
}));

export const placeFormBellowAllPosts = formPlacement('placeFormBellowAllPosts');

export const placeFormBellowAllPages = formPlacement('placeFormBellowAllPages');

export const placePopupFormOnAllPages = formPlacement('placePopupFormOnAllPages');

export const placePopupFormOnAllPosts = formPlacement('placePopupFormOnAllPosts');

export const setPopupFormDelay = (state, action) => ({
  ...state,
  hasUnsavedChanges: true,
  formData: {
    ...state.formData,
    settings: {
      ...state.formData.settings,
      popupFormDelay: action.delay,
    },
  },
});
