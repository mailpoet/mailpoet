import { TemplateData, StateType } from './types';

export default {
  getTemplates(state: StateType): TemplateData {
    return state.templates;
  },
  getFormEditorUrl(state: StateType): string {
    return state.formEditorUrl;
  },
  getSelectTemplateFailed(state: StateType): boolean {
    return state.selectTemplateFailed;
  },
  getLoading(state: StateType): boolean {
    return state.loading;
  },
  getSelectedCategory(state: StateType): string {
    return state.activeCategory;
  },
};
