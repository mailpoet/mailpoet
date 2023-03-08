/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable import/no-duplicates -- importing within multiple "declare module" blocks is OK  */

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
