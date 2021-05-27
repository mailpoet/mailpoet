import { StateType } from '../types';

export const createReducer = (defaultState: StateType) => (
  state: StateType = defaultState,
): StateType => state;
