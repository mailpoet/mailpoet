/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import selectors from './selectors';
import createReducer from './reducer';

export default () => {
  const defaultState = {
    templates: (window as any).mailpoet_templates,
    formEditorUrl: (window as any).mailpoet_form_edit_url,
  };

  const config = {
    selectors,
    reducer: createReducer(defaultState),
    resolvers: {},
  };

  registerStore('mailpoet-form-editor-templates', config);
};
