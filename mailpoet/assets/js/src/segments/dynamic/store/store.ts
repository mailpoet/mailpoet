/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { createReduxStore, register } from '@wordpress/data';
import * as selectors from './selectors';
import { createReducer } from './reducer';
import * as actions from './actions';
import * as controls from './controls';
import { getInitialState } from './initial-state';
import { storeName } from './constants';

export const createStore = () => {
  const defaultState = getInitialState();
  const config = {
    selectors,
    actions,
    controls,
    reducer: createReducer(defaultState),
    resolvers: {},
  };

  const store = createReduxStore(storeName, config);
  register(store);
  return store;
};

declare module '@wordpress/data' {
  interface StoreMap {
    [storeName]: ReturnType<typeof createStore>;
  }
}
