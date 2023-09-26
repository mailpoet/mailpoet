import { dispatch } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { storeName } from './constants';

export function toggleInserterSidebar() {
  return {
    type: 'TOGGLE_INSERTER_SIDEBAR',
  } as const;
}

export function toggleListviewSidebar() {
  return {
    type: 'TOGGLE_LISTVIEW_SIDEBAR',
  } as const;
}

export const openSidebar = () => {
  dispatch(interfaceStore).enableComplementaryArea(storeName);
};

export const closeSidebar = () => {
  dispatch(interfaceStore).disableComplementaryArea(storeName);
};
