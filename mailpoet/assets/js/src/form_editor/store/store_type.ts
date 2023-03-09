// Temporary type definition for the form store
import {
  ReduxStoreConfig,
  StoreDescriptor,
} from '@wordpress/data/build-types/types';
import { State } from './state_types';
import { selectors } from './selectors';
import * as actions from './actions';

export const store = { name: 'mailpoet-form-editor' } as StoreDescriptor<
  ReduxStoreConfig<State, typeof actions, typeof selectors>
>;
