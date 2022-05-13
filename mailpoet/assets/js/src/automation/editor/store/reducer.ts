import { Action } from '@wordpress/data';
import { State } from './types';

export function reducer(state: State, action: Action): State {
  switch (action.type) {
    default:
      return state;
  }
}
