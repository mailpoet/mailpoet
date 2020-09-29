import { TemplateType } from './types';

export default {
  getTemplates(state): Array<TemplateType> {
    return state.templates;
  },
  getFormEditorUrl(state): string {
    return state.formEditorUrl;
  },
  getSelectTemplateFailed(state): boolean {
    return state.selectTemplateFailed;
  },
  getLoading(state): boolean {
    return state.loading;
  },
};
