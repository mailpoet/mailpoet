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

export const placeFixedBarFormOnAllPages = formPlacement('placeFixedBarFormOnAllPages');

export const placeFixedBarFormOnAllPosts = formPlacement('placeFixedBarFormOnAllPosts');

export const formPlacementDelay = curry((delayFormName, state, action) => ({
  ...state,
  hasUnsavedChanges: true,
  formData: {
    ...state.formData,
    settings: {
      ...state.formData.settings,
      [delayFormName]: action.delay,
    },
  },
}));

export const setPopupFormDelay = formPlacementDelay('popupFormDelay');

export const setFixedBarFormDelay = formPlacementDelay('fixedBarFormDelay');

export const setFixedBarFormPosition = (state, action) => ({
  ...state,
  hasUnsavedChanges: true,
  formData: {
    ...state.formData,
    settings: {
      ...state.formData.settings,
      fixedBarFormPosition: action.position,
    },
  },
});
