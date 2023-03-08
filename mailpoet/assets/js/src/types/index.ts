import { ComponentProps, ComponentType } from 'react';
import { ColorPalette, FontSizePicker } from '@wordpress/components';
import { ConfirmDialog } from '@wordpress/components/build-types/confirm-dialog';
import {
  ActionCreatorsOf,
  AnyConfig,
  ConfigOf,
  CurriedSelectorsOf,
  MapSelect,
  StoreDescriptor as GenericStoreDescriptor,
  UseSelectReturn,
} from '@wordpress/data/build-types/types';

/* eslint-disable-next-line import/no-extraneous-dependencies -- we're only defining types here  */
import {
  Composite,
  CompositeGroup,
  CompositeItem,
  useCompositeState,
} from 'reakit/Composite';

import './wordpress_modules';

/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/naming-convention -- we have no control over 3rd-party naming conventions */
/* eslint-disable no-underscore-dangle -- we have no control over 3rd-party naming conventions */

export * from '../segments/dynamic/types';

// Inspired by: https://neliosoftware.com/blog/adding-typescript-to-wordpress-data-stores/
export type OmitFirstArg<F> = F extends (
  first: unknown,
  ...args: infer P
) => infer R
  ? (...args: P) => R
  : never;

export type OmitFirstArgs<O extends object> = {
  [K in keyof O]: OmitFirstArg<O[K]>;
};

declare module '@wordpress/block-editor' {
  export const __experimentalLibrary: any;

  // types for 'useSetting' are missing in @types/wordpress__block-editor
  export function useSetting(path: string): unknown;
  export function useSetting(path: 'color.palette'): ColorPalette.Color[];
  export function useSetting(
    path: 'typography.fontSizes',
  ): FontSizePicker.FontSize[];

  // types for 'gradients' are missing in @types/wordpress__block-editor
  export interface EditorSettings {
    gradients: {
      name: string;
      slug: string;
      gradient: string;
    }[];
  }
}

declare module '@wordpress/components' {
  export const __experimentalConfirmDialog: ComponentType<
    ComponentProps<typeof ConfirmDialog>
  >;
  export const __experimentalText: any;
  export const __unstableComposite: typeof Composite;
  export const __unstableCompositeGroup: typeof CompositeGroup;
  export const __unstableCompositeItem: typeof CompositeItem;
  export const __unstableUseCompositeState: typeof useCompositeState;

  export const SearchControl: any;
  export const ToolbarItem: any;
}

// fix and improve some @wordpress/data types
declare module '@wordpress/data' {
  export function select<T extends GenericStoreDescriptor<AnyConfig>>(
    store: T,
  ): CurriedSelectorsOf<T>;

  export function dispatch<T extends GenericStoreDescriptor<any>>(
    store: T,
  ): ActionCreatorsOf<ConfigOf<T>>;

  // in @wordpress/data, the "deps" is not optional (while in reality it is)
  export function useSelect<T extends MapSelect | GenericStoreDescriptor<any>>(
    mapSelect: T,
    deps?: unknown[],
  ): UseSelectReturn<T>;

  // function "batch" is missing in data registry
  export function useRegistry(): {
    batch: (callback: () => void) => void;
  };

  // types for "createRegistrySelector" are not correct
  export function createRegistrySelector<
    S extends typeof select,
    T extends (state: any, ...args: any) => any,
  >(registrySelector: (select: S) => T): T;
}
