import { createRegistrySelector } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature } from './types';

export const isFeatureActive = createRegistrySelector(
  (select) =>
    (_, feature: Feature): boolean =>
      select(preferencesStore).get(storeName, feature),
);
