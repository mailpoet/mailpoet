import { registerStore } from '@wordpress/data';
import { Settings } from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import * as controls from './controls';
import createReducer from './create_reducer';
import makeDefaultState from './make_default_state';

export const STORE_NAME = 'mailpoet-settings';

export const initStore = (data: Settings) => registerStore(STORE_NAME, {
  reducer: createReducer(makeDefaultState(data)),
  actions,
  selectors,
  controls,
  resolvers: {},
});
