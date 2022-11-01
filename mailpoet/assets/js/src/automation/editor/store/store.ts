import { createReduxStore, register, StoreDescriptor } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { Hooks } from 'wp-js-hooks';
import * as actions from './actions';
import { storeName } from './constants';
import { getInitialState } from './initial_state';
import { reducer } from './reducer';
import * as selectors from './selectors';
import { State } from './types';
import { OmitFirstArgs } from '../../../types';
import { EditorStoreConfigType } from '../../types/filters';

type StoreType = Omit<StoreDescriptor, 'name'> & {
  name: typeof storeName;
};

export const createStore = (): StoreType => {
  const storeConfig = Hooks.applyFilters(
    'mailpoet.automation.editor.create_store',
    {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any -- the "Action" type is missing thunks with "dispatch"
      actions: actions as any,
      controls,
      selectors,
      reducer,
      initialState: getInitialState(),
    } as EditorStoreConfigType,
  ) as EditorStoreConfigType;

  const store = createReduxStore<State>(storeName, storeConfig) as StoreType;
  register(store);
  return store;
};

export type StoreKey = typeof storeName | StoreType;

declare module '@wordpress/data' {
  function select(key: StoreKey): OmitFirstArgs<typeof selectors>;
  function dispatch(key: StoreKey): typeof actions;
}

export { actions, selectors };
