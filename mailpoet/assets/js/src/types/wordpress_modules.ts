/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */

// there are no @types/wordpress__interface yet
declare module '@wordpress/interface' {
  export const InterfaceSkeleton: any;
}

// there are no @types/wordpress__preferences yet
declare module '@wordpress/preferences' {
  import { StoreDescriptor } from '@wordpress/data';

  export const store: { name: 'core/preferences' } & StoreDescriptor;
}
