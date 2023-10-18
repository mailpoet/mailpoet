import { ComponentProps, ComponentPropsWithoutRef, ComponentType } from 'react';
import { ColorPalette, FontSizePicker } from '@wordpress/components';
import { ConfirmDialog } from '@wordpress/components/build-types/confirm-dialog';
import { NumberControl } from '@wordpress/components/build-types/number-control';
import { FormTokenFieldProps } from '@wordpress/components/build-types/form-token-field/types';
// eslint-disable-next-line import/no-named-default
import { default as WPPopover } from '@wordpress/components/build-types/popover';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as keyboardShortutsStore } from '@wordpress/keyboard-shortcuts';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as noticesStore } from '@wordpress/notices';
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

import './wordpress-modules';

/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/naming-convention -- we have no control over 3rd-party naming conventions */
/* eslint-disable no-underscore-dangle -- we have no control over 3rd-party naming conventions */

export * from '../segments/dynamic/types';

declare module '@wordpress/components' {
  export const __experimentalConfirmDialog: ComponentType<
    ComponentProps<typeof ConfirmDialog>
  >;

  export const __experimentalNumberControl: ComponentType<
    ComponentProps<typeof NumberControl>
  >;

  // New property for declaring forward compatibility is not set on types
  // eslint-disable-next-line @typescript-eslint/no-shadow,@typescript-eslint/no-namespace
  export namespace CustomSelectControl {
    interface Props {
      __nextUnconstrainedWidth: boolean;
    }
  }

  // New property on Dropdown is not set in @types/wordpress__components
  // eslint-disable-next-line @typescript-eslint/no-shadow,@typescript-eslint/no-namespace
  export namespace Dropdown {
    interface Props {
      popoverProps?: Omit<
        ComponentPropsWithoutRef<typeof WPPopover>,
        'children'
      >;
    }
  }

  // New properties on FormTokenField is not set in @types/wordpress__components
  // eslint-disable-next-line @typescript-eslint/no-namespace
  export namespace FormTokenField {
    // eslint-disable-next-line @typescript-eslint/no-empty-interface -- There is no other usable way hot to tell define interface using other interface
    export interface Props extends FormTokenFieldProps {}
  }

  // Property "delay" is missing in Tooltip props
  // eslint-disable-next-line @typescript-eslint/no-namespace
  export namespace Tooltip {
    export interface Props {
      delay?: number;
    }
  }

  // Property "className" is missing in Slot props
  // See https://github.com/WordPress/gutenberg/tree/c5c8c167e35980ea144975169543fb842c5297fa/packages/components/src/slot-fill
  // "Slot with bubblesVirtually set to true also accept an optional className to add to the slot container."
  // eslint-disable-next-line @typescript-eslint/no-namespace
  export namespace Slot {
    export interface Props {
      className?: string;
    }
  }

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

  interface StoreMap {
    [blockEditorStore.name]: typeof blockEditorStore;
    [keyboardShortutsStore.name]: typeof keyboardShortutsStore;
    [interfaceStore.name]: typeof interfaceStore;
    [preferencesStore.name]: typeof preferencesStore;
    [noticesStore.name]: typeof noticesStore;
  }
}

declare module '@wordpress/block-editor' {
  export const __experimentalLibrary: any;
  export const __experimentalListView: any;

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
