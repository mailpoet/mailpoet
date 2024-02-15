import { createReduxStore, register } from '@wordpress/data';
import {
  ReduxStoreConfig,
  StoreDescriptor as GenericStoreDescriptor,
} from '@wordpress/data/build-types/types';
import { controls } from '@wordpress/data-controls';
import { Hooks } from 'wp-js-hooks';
import * as actions from './actions';
import { storeName } from './constants';
import { getInitialState } from './initial-state';
import { reducer } from './reducer';
import * as selectors from './selectors';
import { initializeFilters } from '../filters';

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
  initializeFilters();
  return store;
};

export interface AutomationsEditorStore {
  getActions(): EditorStoreConfig['actions'];
  getSelectors(): EditorStoreConfig['selectors'];
}

declare module '@wordpress/data' {
  interface StoreMap {
    [storeName]: GenericStoreDescriptor<
      ReduxStoreConfig<
        unknown,
        ReturnType<AutomationsEditorStore['getActions']>,
        ReturnType<AutomationsEditorStore['getSelectors']>
      >
    >;
  }
}

export { actions, selectors };
