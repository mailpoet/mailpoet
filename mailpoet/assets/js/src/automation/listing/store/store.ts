import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { storeName } from './constants';
import { getInitialState } from './initial-state';
import { reducer } from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';

export const createStore = () => {
  const store = createReduxStore(storeName, {
    actions,
    controls,
    selectors,
    reducer,
    initialState: getInitialState(),
  });
  register(store);
  return store;
};

declare module '@wordpress/data' {
  interface StoreMap {
    [storeName]: ReturnType<typeof createStore>;
  }
}

export { actions, selectors };
