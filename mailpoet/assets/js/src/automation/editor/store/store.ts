import { createReduxStore, register, StoreDescriptor } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import * as actions from './actions';
import { storeName } from './constants';
import { initialState } from './initial_state';
import { reducer } from './reducer';
import * as selectors from './selectors';
import { State } from './types';
import { OmitFirstArgs } from '../../../types';

type StoreType = Omit<StoreDescriptor, 'name'> & {
  name: typeof storeName;
};

export const store = createReduxStore<State>(storeName, {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- the "Action" type is missing thunks with "dispatch"
  actions: actions as any,
  controls,
  selectors,
  reducer,
  initialState,
}) as StoreType;

type StoreKey = typeof storeName | StoreType;

declare module '@wordpress/data' {
  function select(key: StoreKey): OmitFirstArgs<typeof selectors>;
  function dispatch(key: StoreKey): typeof actions;
}

register(store);
