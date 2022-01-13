export type TemplateType = {
  id: string;
  name: string;
  thumbnail: string;
};

export type TemplateData = {
  [formType: string]: Array<TemplateType>;
}

export enum CategoryType {
  Popup = 'popup',
  SlideIn = 'slide_in',
  FixedBar = 'fixed_bar',
  BelowPosts = 'below_posts',
  Others = 'others',
}

export type StateType = {
  templates: TemplateData;
  formEditorUrl: string;
  selectTemplateFailed: boolean;
  loading: boolean;
  activeCategory: CategoryType;
}

export type ActionType = {
  type: string;
}

export interface CategoryActionType extends ActionType {
  category: CategoryType;
}
