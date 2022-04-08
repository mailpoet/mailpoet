import { select } from '@wordpress/data';

import { CategoryActionType, CategoryType } from './types';

// eslint-disable-next-line @typescript-eslint/ban-types -- IDK what else could be the return value
export function* selectTemplate(
  templateId: string,
  templateName: string,
): object {
  yield { type: 'SELECT_TEMPLATE_START' };
  yield {
    type: 'TRACK_EVENT',
    name: 'Forms > Template selected',
    data: {
      'Template id': templateId,
      'Template name': templateName,
    },
    timeout: 200,
  };
  const url = select('mailpoet-form-editor-templates').getFormEditorUrl();

  window.location.href = `${url}${templateId}`;
  return {};
}

export function selectCategory(category: CategoryType): CategoryActionType {
  return {
    type: 'SELECT_CATEGORY',
    category,
  };
}
