import { createRegistrySelector } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { Feature } from '../../../automation/editor/store';
import { storeName } from './constants';
import { State } from './types';

export const isFeatureActive = createRegistrySelector(
  (select) =>
    (_, feature: Feature): boolean =>
      !!select(preferencesStore).get(storeName, feature),
);

export const isSidebarOpened = createRegistrySelector(
  (select) => (): boolean =>
    !!select(interfaceStore).getActiveComplementaryArea(storeName),
);

export const hasEdits = createRegistrySelector((select) => (): boolean => {
  const postId = select(storeName).getEmailPostId();
  return !!select(coreDataStore).hasEditsForEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
});

export function getEmailPostId(state: State): number {
  return state.postId;
}

export function isInserterSidebarOpened(state: State): boolean {
  return state.inserterSidebar.isOpened;
}

export function isListviewSidebarOpened(state: State): boolean {
  return state.listviewSidebar.isOpened;
}

export function getInitialEditorSettings(
  state: State,
): State['editorSettings'] {
  return state.editorSettings;
}
