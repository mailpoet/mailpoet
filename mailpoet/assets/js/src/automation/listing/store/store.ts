import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { storeName } from './constants';
import { getInitialState } from './initial_state';
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

export const store: ReturnType<typeof createStore> = {
  name: storeName,
  instantiate: (registry) => createStore().instantiate(registry),
};

export { actions, selectors };
