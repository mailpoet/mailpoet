import { registerStore } from '@wordpress/data';
import actions from './actions.jsx';
import reducer from './reducer.jsx';
import selectors from './selectors.jsx';

const config = {
  reducer,
  actions,
  selectors,
  controls: {},
  resolvers: {},
};

export default () => (registerStore('mailpoet-form-editor', config));
