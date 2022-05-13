import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature } from './types';

export const openSidebar =
  (key) =>
  ({ registry }) =>
    registry.dispatch(interfaceStore).enableComplementaryArea(storeName, key);

export const closeSidebar =
  () =>
  ({ registry }) =>
    registry.dispatch(interfaceStore).disableComplementaryArea(storeName);

export const toggleFeature =
  (feature: Feature) =>
  ({ registry }) =>
    registry.dispatch(preferencesStore).toggle(storeName, feature);
