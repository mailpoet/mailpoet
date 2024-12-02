/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { createReduxStore, register } from '@wordpress/data';

import * as selectors from './selectors';
import * as actions from './actions';
import { storeName } from './constants';
import reducer from './reducers';

export const newsletterStoreName = storeName;

export const createStore = () => {
  console.log('Creating store', storeName);
  const config = {
    reducer,
    selectors,
    actions,
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
