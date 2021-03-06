/* eslint-disable @typescript-eslint/no-explicit-any */

declare module 'wp-js-hooks' {
  function addFilter(name: string, namespace: string, callback: (...args: any[]) => any): void;
  function applyFilters(name: string, ...args: any[]): any;
}
