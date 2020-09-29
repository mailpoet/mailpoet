import { select } from '@wordpress/data';

// eslint-disable-next-line import/prefer-default-export
export function* selectTemplate(templateId) {
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
  const url = select('mailpoet-form-editor-templates').getFormEditorUrl();

  window.location = url + res.data.id;
  return {};
}
