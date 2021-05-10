import { select } from '@wordpress/data';
import MailPoet from 'mailpoet';

import {
  CategoryActionType,
  CategoryType,
} from './types';

// eslint-disable-next-line @typescript-eslint/ban-types -- IDK what else could be the return value
export function* selectTemplate(templateId: string, templateName: string): object {
  yield { type: 'SELECT_TEMPLATE_START' };
  const { res, success, error } = yield {
    type: 'CALL_API',
    endpoint: 'forms',
    action: 'create',
    data: {
      'template-id': templateId,
    },
  };
  if (!success) {
    return { type: 'SELECT_TEMPLATE_ERROR', error };
  }
  yield {
    type: 'TRACK_EVENT',
    name: 'Forms > Template selected',
    data: {
      'MailPoet Free version': MailPoet.version,
      'Template id': templateId,
      'Template name': templateName,
    },
    timeout: 200,
  };
  const url = select('mailpoet-form-editor-templates').getFormEditorUrl();

  window.location.href = `${url}${res.data.id}`;
  return {};
}

export function selectCategory(category: CategoryType): CategoryActionType {
  return {
    type: 'SELECT_CATEGORY',
    category,
  };
}
