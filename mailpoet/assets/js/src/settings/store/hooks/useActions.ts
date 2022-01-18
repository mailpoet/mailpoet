import { useDispatch } from '@wordpress/data';
import * as actions from '../actions';
import { STORE_NAME } from '..';

type Actions = typeof actions

export function useActions(): Actions {
  return useDispatch(STORE_NAME);
}

export function useAction<Key extends keyof Actions>(key: Key): Actions[Key] {
  return useDispatch(STORE_NAME)[key];
}
