import { createReduxStore, register } from '@wordpress/data';
import * as actions from './actions';
import * as selectors from './selectors';
import * as controls from './controls';
import { createReducer } from './create-reducer';
import { makeDefaultState } from './make-default-state';
import { STORE_NAME } from './store-name';

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

declare module '@wordpress/data' {
  interface StoreMap {
    [STORE_NAME]: ReturnType<typeof initStore>;
  }
}
