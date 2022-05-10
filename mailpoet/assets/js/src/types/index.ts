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
}
