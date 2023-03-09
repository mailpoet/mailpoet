import { createReduxStore, register } from '@wordpress/data';
import { getInitialState } from './initial-state';
import * as actions from './actions';
import * as selectors from './selectors';
import { reducer } from './reducer';

export const storeName = 'mailpoet/homepage';
const controls = {};

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
