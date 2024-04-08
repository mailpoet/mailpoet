// Taken from:
//   https://github.com/WordPress/gutenberg/blob/%40wordpress/compose%406.25.0/packages/compose/src/hooks/use-debounced-input/index.js
//
// Once we upgrade do @wordpress/compose >= 6.25, we can remove this file and use the hook from the package.
import { useDebounce } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';

/**
 * Helper hook for input fields that need to debounce the value before using it.
 *
 * @param {any} defaultValue The default value to use.
 * @return {[string, Function, string]} The input value, the setter and the debounced input value.
 */
export default function useDebouncedInput(defaultValue = '') {
  const [input, setInput] = useState(defaultValue);
  const [debouncedInput, setDebouncedState] = useState(defaultValue);

  const setDebouncedInput = useDebounce(setDebouncedState, 250);

  useEffect(() => {
    setDebouncedInput(input);
  }, [input, setDebouncedInput]);

  return [input, setInput, debouncedInput] as const;
}
