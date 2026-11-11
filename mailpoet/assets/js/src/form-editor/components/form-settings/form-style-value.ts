import { formStyles as defaultFormStyles } from 'form-editor/store/defaults';

// Styles that can be left unset, so the form fields inherit them from the theme
const clearableStyles = ['inputPadding'];

export function getFormStyleValue(property: string, value: unknown): unknown {
  if (clearableStyles.includes(property)) {
    return value;
  }
  return value ?? defaultFormStyles[property];
}
