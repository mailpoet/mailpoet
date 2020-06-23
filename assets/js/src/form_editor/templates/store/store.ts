/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import selectors from './selectors';
import createReducer from './reducer';
import * as actions from './actions';
import controls from './controls';

export default () => {
  const defaultState = {
    templates: (window as any).mailpoet_templates,
    formEditorUrl: (window as any).mailpoet_form_edit_url,
    selectTemplateFailed: false,
  };

  const config = {
    selectors,
    actions,
    controls,
    reducer: createReducer(defaultState),
    resolvers: {},
  };

  registerStore('mailpoet-form-editor-templates', config);
};
