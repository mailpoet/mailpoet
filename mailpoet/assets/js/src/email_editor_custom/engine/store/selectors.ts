import { createRegistrySelector } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { storeName } from './constants';

export const isSidebarOpened = createRegistrySelector(
  (select) => (): boolean =>
    !!select(interfaceStore).getActiveComplementaryArea(storeName),
);
