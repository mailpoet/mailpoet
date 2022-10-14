import {
  createReduxStore,
  register,
  StoreConfig,
  StoreDescriptor,
} from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { storeName } from './constants';
import { getInitialState } from './initial_state';
import { reducer } from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { State } from './types';
import { OmitFirstArgs } from '../../../types';

type StoreType = Omit<StoreDescriptor, 'name'> & {
  name: typeof storeName;
};

export const createStore = (): StoreType => {
  const storeConfig = {
    actions,
    controls,
    selectors,
    reducer,
    initialState: getInitialState(),
  } as StoreConfig<State>;

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
