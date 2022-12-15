import {
  createReduxStore,
  register,
  StoreConfig,
  StoreDescriptor,
} from '@wordpress/data';
import { OmitFirstArgs } from 'types';
import { getInitialState } from './initial-state';
import * as actions from './actions';
import * as selectors from './selectors';
import { reducer } from './reducer';
import { State } from './types';

export const storeName = 'mailpoet/homepage';
const controls = {};

type StoreType = Omit<StoreDescriptor, 'name'> & {
  name: typeof storeName;
};
type EditorStoreConfigType = StoreConfig<State>;
export const createStore = (): StoreType => {
  const storeConfig = {
    actions,
    controls,
    selectors,
    reducer,
    initialState: getInitialState(),
  } as EditorStoreConfigType;
  const store = createReduxStore<State>(storeName, storeConfig) as StoreType;
  register(store);
  return store;
};

export type StoreKey = typeof storeName | StoreType;

declare module '@wordpress/data' {
  function select(key: StoreKey): OmitFirstArgs<typeof selectors>;
  function dispatch(key: StoreKey): typeof actions;
}
