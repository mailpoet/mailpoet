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

export default (defaultState: object) => (state = defaultState, action) => {
  switch (action.type) {
    case 'SELECT_TEMPLATE_ERROR': return selectTemplateFailed(state);
    case 'SELECT_TEMPLATE_START': return selectTemplateStarted(state);
    default:
      return state;
  }
};
