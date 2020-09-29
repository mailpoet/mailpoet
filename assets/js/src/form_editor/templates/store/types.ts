export type TemplateType = {
  id: string
  name: string
  thumbnail: string,
};

export type TemplateData = {
  [formType: string]: Array<TemplateType>
}
