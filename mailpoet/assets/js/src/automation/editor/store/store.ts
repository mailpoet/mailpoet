import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { Hooks } from 'wp-js-hooks';
import * as actions from './actions';
import { storeName } from './constants';
import { getInitialState } from './initial_state';
import { reducer } from './reducer';
import * as selectors from './selectors';

const getConfig = () =>
  ({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any -- the "Action" type is missing thunks with "dispatch"
    actions: actions as any,
    controls,
    selectors,
    reducer,
    initialState: getInitialState(),
  } as const);

export type EditorStoreConfig = ReturnType<typeof getConfig>;

export const createStore = () => {
  const storeConfig = Hooks.applyFilters(
    'mailpoet.automation.editor.create_store',
    getConfig(),
  ) as EditorStoreConfig;
  const store = createReduxStore(storeName, storeConfig);
  register(store);
  return store;
};

declare module '@wordpress/data' {
  interface StoreMap {
    [storeName]: ReturnType<typeof createStore>;
  }
}

export { actions, selectors };
