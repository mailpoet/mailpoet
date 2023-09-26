import { createReduxStore, register } from '@wordpress/data';
import {
  ReduxStoreConfig,
  StoreDescriptor as GenericStoreDescriptor,
} from '@wordpress/data/build-types/types';
import { controls } from '@wordpress/data-controls';
import * as actions from './actions';
import { storeName } from './constants';
import { getInitialState } from './initial-state';
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
  const storeConfig = getConfig();
  const store = createReduxStore(storeName, storeConfig);
  register(store);
  return store;
};

export interface EmailEditorStore {
  getActions(): EditorStoreConfig['actions'];
  getSelectors(): EditorStoreConfig['selectors'];
}

declare module '@wordpress/data' {
  interface StoreMap {
    [storeName]: GenericStoreDescriptor<
      ReduxStoreConfig<
        unknown,
        ReturnType<EmailEditorStore['getActions']>,
        ReturnType<EmailEditorStore['getSelectors']>
      >
    >;
  }
}

export { actions, selectors };
