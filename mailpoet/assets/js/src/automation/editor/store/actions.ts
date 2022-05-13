import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature } from './types';

export const toggleFeature =
  (feature: Feature) =>
  ({ registry }) =>
    registry.dispatch(preferencesStore).toggle(storeName, feature);
