import { ColorPalette, FontSizePicker } from '@wordpress/components';

import './wordpress_modules';

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
  // eslint-disable-next-line @typescript-eslint/no-explicit-any,@typescript-eslint/naming-convention,no-underscore-dangle
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
