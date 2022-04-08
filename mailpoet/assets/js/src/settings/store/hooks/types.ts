/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * Omits the first item in a tuple type
 * Tail<[number, string, boolean]> gives [string, boolean]
 */
export type Tail<T extends any[]> = ((...args: T) => void) extends (
  _: any,
  ...args: infer Others
) => void
  ? Others
  : never;

/**
 * Takes a function type and returns a new function
 * type with same signature except the first parameter.
 * ExcludeFirstParam<(x: number, y: string) => boolean>
 *  gives (y: string) => boolean
 */
export type ExcludeFirstParam<F extends (...args: any[]) => any> = (
  ...args: Tail<Parameters<F>>
) => ReturnType<F>;

export type ValueAndSetter<T> = [T, (value: T) => any];
