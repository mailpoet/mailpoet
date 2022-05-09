import { registerStore } from '@wordpress/data';
import * as actions from './actions';
import * as selectors from './selectors';
import * as controls from './controls';
import { createReducer } from './create_reducer';
import { makeDefaultState } from './make_default_state';
import { STORE_NAME } from './store_name';
import { OmitFirstArgs } from '../../types';

declare module '@wordpress/data' {
  function select(key: typeof STORE_NAME): OmitFirstArgs<typeof selectors>;
  function dispatch(key: typeof STORE_NAME): typeof actions;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const initStore = (window: any) =>
  registerStore(STORE_NAME, {
    reducer: createReducer(makeDefaultState(window)),
    actions,
    selectors,
    controls,
    resolvers: {},
  });
