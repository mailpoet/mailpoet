export const selectTemplateFailed = (state) => ({
  ...state,
  selectTemplateFailed: true,
  loading: false,
});

export const selectTemplateStarted = (state) => ({
  ...state,
  selectTemplateFailed: false,
  loading: true,
});

export const selectCategory = (state, action) => ({
  ...state,
  activeCategory: action.category,
});

export default (defaultState: object) => (state = defaultState, action) => {
  switch (action.type) {
    case 'SELECT_TEMPLATE_ERROR': return selectTemplateFailed(state);
    case 'SELECT_TEMPLATE_START': return selectTemplateStarted(state);
    case 'SELECT_CATEGORY': return selectCategory(state, action);
    default:
      return state;
  }
};
