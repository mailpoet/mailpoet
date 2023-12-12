import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { storeName } from './constants';
import { getInitialState } from './initial-state';
import { legacyReducer } from './legacy-reducer';
import { reducer } from './reducer';
import { State } from './types';
import * as actions from './actions';
import * as legacyActions from './legacy-actions';
import * as selectors from './selectors';

export const createStore = () => {
  const store = createReduxStore(storeName, {
    actions: { ...actions, ...legacyActions },
    controls,
    selectors,
    reducer: (state: State, action) =>
      legacyReducer(reducer(state, action), action),
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
