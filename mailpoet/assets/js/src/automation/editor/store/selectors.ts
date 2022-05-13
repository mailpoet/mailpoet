import { createRegistrySelector } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature, State } from './types';

export const isFeatureActive = createRegistrySelector(
  (select) =>
    (_, feature: Feature): boolean =>
      select(preferencesStore).get(storeName, feature),
);

export const isSidebarOpened = createRegistrySelector(
  (select) => (): boolean =>
    !!select(interfaceStore).getActiveComplementaryArea(storeName),
);

export function isInserterSidebarOpened(state: State): boolean {
  return state.inserterSidebar.isOpened;
}
