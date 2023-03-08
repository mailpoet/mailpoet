import { select } from '@wordpress/data';
import {
  ReduxStoreConfig,
  StoreDescriptor,
} from '@wordpress/data/build-types/types';

import { CategoryActionType, CategoryType, StateType } from './types';
import { selectors } from './selectors';

// workaround to avoid import cycles
const store = { name: 'mailpoet-form-editor-templates' } as StoreDescriptor<
  ReduxStoreConfig<StateType, null, typeof selectors>
>;

// eslint-disable-next-line @typescript-eslint/ban-types -- IDK what else could be the return value
export function* selectTemplate(templateId: string, templateName: string) {
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
  const url = select(store).getFormEditorUrl();

  window.location.href = `${url}${templateId}`;
  return {};
}

export function selectCategory(category: CategoryType): CategoryActionType {
  return {
    type: 'SELECT_CATEGORY',
    category,
  };
}
