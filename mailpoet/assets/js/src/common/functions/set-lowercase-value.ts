import { curry } from 'lodash';

export const setLowercaseValue = curry(
  (setter: (value: string) => void, value: string) => {
    setter(value.toLowerCase());
  },
);
