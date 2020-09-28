export type TemplateType = {
  id: string
  name: string
};

export type TemplateData = {
  [formType: string]: Array<TemplateType>
}
