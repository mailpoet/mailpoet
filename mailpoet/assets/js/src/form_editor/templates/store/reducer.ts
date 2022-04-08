import { StateType, CategoryActionType, ActionType } from './types';

export const selectTemplateFailed = (state: StateType): StateType => ({
  ...state,
  selectTemplateFailed: true,
  loading: false,
});

export const selectTemplateStarted = (state: StateType): StateType => ({
  ...state,
  selectTemplateFailed: false,
  loading: true,
});

export const selectCategory = (
  state: StateType,
  action: CategoryActionType,
): StateType => ({
  ...state,
  activeCategory: action.category,
});

export default (defaultState: StateType) =>
  (
    state: StateType = defaultState, // eslint-disable-line @typescript-eslint/default-param-last
    action: ActionType,
  ): StateType => {
    switch (action.type) {
      case 'SELECT_TEMPLATE_ERROR':
        return selectTemplateFailed(state);
      case 'SELECT_TEMPLATE_START':
        return selectTemplateStarted(state);
      case 'SELECT_CATEGORY':
        return selectCategory(state, action as CategoryActionType);
      default:
        return state;
    }
  };
