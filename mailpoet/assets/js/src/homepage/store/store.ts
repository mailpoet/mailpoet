import {
  createReduxStore,
  register,
  StoreConfig,
  StoreDescriptor,
} from '@wordpress/data';
import { OmitFirstArgs } from 'types';

const storeName = 'mailpoet/homepage';
const actions = {};
const selectors = {};
const controls = {};
const reducer = (state) => state;
const initialState = {};

type StoreType = Omit<StoreDescriptor, 'name'> & {
  name: typeof storeName;
};
type State = {
  [K in string]: never;
};
type EditorStoreConfigType = StoreConfig<State>;

export const createStore = (): StoreType => {
  const storeConfig = {
    actions,
    controls,
    selectors,
    reducer,
    initialState,
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
