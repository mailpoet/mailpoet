export const selectTemplateFailed = (state) => ({
  ...state,
  selectTemplateFailed: true,
});

export default (defaultState: object) => (state = defaultState, action) => {
  switch (action.type) {
    case 'SELECT_TEMPLATE_ERROR': return selectTemplateFailed(state);
    default:
      return state;
  }
};
