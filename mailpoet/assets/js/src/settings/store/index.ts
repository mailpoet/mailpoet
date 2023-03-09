import { createReduxStore, register } from '@wordpress/data';
import * as actions from './actions';
import * as selectors from './selectors';
import * as controls from './controls';
import { createReducer } from './create_reducer';
import { makeDefaultState } from './make_default_state';
import { STORE_NAME } from './store_name';
import { OmitFirstArgs } from '../../types';

declare module '@wordpress/data' {
  function select(key: typeof STORE_NAME): OmitFirstArgs<typeof selectors>;
  function dispatch(key: typeof STORE_NAME): typeof actions;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const initStore = () => {
  const store = createReduxStore(STORE_NAME, {
    reducer: createReducer(makeDefaultState()),
    actions,
    selectors,
    controls,
    resolvers: {},
  });
  register(store);
  return store;
};

export const store: ReturnType<typeof initStore> = {
  name: STORE_NAME,
  instantiate: (registry) => initStore().instantiate(registry),
};
