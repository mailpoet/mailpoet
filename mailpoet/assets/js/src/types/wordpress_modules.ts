/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable import/no-duplicates -- importing within multiple "declare module" blocks is OK  */

declare module '@wordpress/block-editor' {
  import * as blockEditorActions from '@wordpress/block-editor/store/actions';
  import * as blockEditorSelectors from '@wordpress/block-editor/store/selectors';
  import { StoreDescriptor as GenericStoreDescriptor } from '@wordpress/data/build-types/types';

  export * from '@wordpress/block-editor/index';

  export const store: { name: 'core/block-editor' } & GenericStoreDescriptor<{
    reducer: () => unknown;
    actions: typeof blockEditorActions;
    selectors: typeof blockEditorSelectors;
  }>;
}

// We need to use code/editor store but types are not available yet
declare module '@wordpress/editor' {
  import * as editorActions from '@wordpress/editor/store/actions';
  import * as editorSelectors from '@wordpress/editor/store/selectors';
  import { StoreDescriptor as GenericStoreDescriptor } from '@wordpress/data/build-types/types';

  export * from '@wordpress/editor/index';

  export const store: { name: 'core/editor' } & GenericStoreDescriptor<{
    reducer: () => unknown;
    actions: typeof editorActions;
    selectors: typeof editorSelectors;
  }>;
}

// We need to use code/edit-post store but types are not available yet
declare module '@wordpress/edit-post' {
  import * as editPostActions from '@wordpress/edit-post/store/actions';
  import * as editPostSelectors from '@wordpress/edit-post/store/selectors';
  import { StoreDescriptor as GenericStoreDescriptor } from '@wordpress/data/build-types/types';

  export * from '@wordpress/edit-post/index';

  export const store: { name: 'core/edit-post' } & GenericStoreDescriptor<{
    reducer: () => unknown;
    actions: typeof editPostActions;
    selectors: typeof editPostSelectors;
  }>;
}

// there are no @types/wordpress__interface yet
declare module '@wordpress/interface' {
  import { StoreDescriptor } from '@wordpress/data/build-types/types';

  export const store: { name: 'core/interface' } & StoreDescriptor<{
    reducer: () => unknown;
    selectors: {
      getActiveComplementaryArea: (
        state: unknown,
        scope: string,
      ) => string | undefined | null;
    };
  }>;
  export const ComplementaryArea: any;
  export const FullscreenMode: any;
  export const MoreMenuDropdown: any;
  export const InterfaceSkeleton: any;
  export const PinnedItems: any;
}

// there are no @types/wordpress__keyboard-shortcuts yet
declare module '@wordpress/keyboard-shortcuts' {
  import { StoreDescriptor } from '@wordpress/data/build-types/types';

  export const store: { name: 'core/keyboard-shortcuts' } & StoreDescriptor<{
    reducer: () => unknown;
    selectors: {
      getShortcutRepresentation: (state: unknown, scope: string) => unknown;
    };
    actions: {
      registerShortcut: (options: any) => object;
    };
  }>;
  export const ShortcutProvider: any;
  export const useShortcut: any;
}

// there are no @types/wordpress__preferences yet
declare module '@wordpress/preferences' {
  import { StoreDescriptor } from '@wordpress/data/build-types/types';

  export const store: { name: 'core/preferences' } & StoreDescriptor<{
    reducer: () => unknown;
    selectors: {
      get: <T>(state: unknown, scope: string, name: string) => T;
    };
  }>;
  export const PreferenceToggleMenuItem: any;
}

// Types in @types/wordpress__notices are outdated and build on top of @types/wordpress__data
declare module '@wordpress/notices' {
  import { StoreDescriptor } from '@wordpress/data/build-types/types';
  import { Notice } from '@wordpress/notices/index';

  export * from '@wordpress/notices/index';

  // We don't want to use the types from @types/wordpress__notices but the package
  // is installed anyway as a subdependency of @types/wordpress__components
  // The ignore comment is needed to allow overriding the store
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  export const store: { name: 'core/notices' } & StoreDescriptor<{
    reducer: () => unknown;
    actions: {
      createSuccessNotice: (content: string, options?: unknown) => void;
      createErrorNotice: (content: string, options?: unknown) => void;
      removeNotice: (id: string, context?: string) => void;
      createNotice: (
        status: 'error' | 'info' | 'success' | 'warning' | undefined,
        content: string,
        options?: unknown,
      ) => void;
    };
    selectors: {
      getNotices(state: unknown, context?: string): Notice[];
    };
  }>;
}
