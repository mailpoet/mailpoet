import { PromisifiedActionCreators } from '@wordpress/data/build-types/types';
import { useDispatch } from '@wordpress/data';
import * as actions from '../actions';
import { STORE_NAME } from '../store-name';

type Actions = PromisifiedActionCreators<typeof actions>;

export function useActions(): Actions {
  return useDispatch(STORE_NAME);
}

export function useAction<Key extends keyof Actions>(key: Key): Actions[Key] {
  return useDispatch(STORE_NAME)[key];
}
