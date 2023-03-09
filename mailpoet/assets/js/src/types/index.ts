import { ComponentProps, ComponentType } from 'react';
import { ColorPalette, FontSizePicker } from '@wordpress/components';
import { ConfirmDialog } from '@wordpress/components/build-types/confirm-dialog';
import {
  ActionCreatorsOf,
  ConfigOf,
  CurriedSelectorsOf,
  DataRegistry,
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
  // Derive typings for select(), dispatch(), useSelect(), and useDispatch()calls
  // by store name. The StoreMap interface can be augmented to add custom stores.
  interface StoreMap {
    [key: string]: StoreDescriptor;
  }

  type TKey = keyof StoreMap;
  type TStore<T> = T extends keyof StoreMap ? StoreMap[T] : never;
  type TSelectors<T> = CurriedSelectorsOf<TStore<T>>;
  type TActions<T> = ActionCreatorsOf<ConfigOf<TStore<T>>>;
  type TSelectFunction = <T extends TKey | StoreDescriptor>(
    store: T,
  ) => T extends TKey ? TSelectors<T> : CurriedSelectorsOf<T>;
  type TMapSelect = (select: TSelectFunction, registry: DataRegistry) => any;

  // select('store-name')
  function select<T extends string>(store: T): TSelectors<T>;

  // fix return type for select(storeDescriptor)
  export function select<T extends GenericStoreDescriptor<any>>(
    store: T,
  ): CurriedSelectorsOf<T>;

  // dispatch('store-name')
  function dispatch<T extends string>(store: T): TActions<T>;

  // fix return type for dispatch(storeDescriptor)
  export function dispatch<T extends GenericStoreDescriptor<any>>(
    store: T,
  ): ActionCreatorsOf<ConfigOf<T>>;

  // function "batch" is missing in data registry
  export function useRegistry(): {
    batch: (callback: () => void) => void;
  };

  // useSelect((select) => select('store-name') => ...)
  // useSelect((select) => select(storeDescriptor) => ...)
  export function useSelect<T extends TMapSelect>(
    mapSelect: T,
    deps?: unknown[],
  ): ReturnType<T>;

  // useSelect(storeDescriptor)
  export function useSelect<T extends StoreDescriptor>(
    store: T,
    deps?: unknown[],
  ): UseSelectReturn<T>;

  // useSelect('store-name')
  export function useSelect<T extends string>(
    store: T,
    deps?: unknown[],
  ): UseSelectReturn<TStore<T>>;

  // useDispatch('store-name')
  export function useDispatch<T extends string>(store: T): TActions<T>;

  // types for "createRegistrySelector" are not correct
  export function createRegistrySelector<
    S extends typeof select,
    T extends (state: any, ...args: any) => any,
  >(registrySelector: (select: S) => T): T;
}
