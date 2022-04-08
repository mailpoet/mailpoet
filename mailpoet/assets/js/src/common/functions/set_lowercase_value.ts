import { curry } from 'lodash';

const setLowercaseValue = curry(
  (setter: (value: string) => void, value: string) => {
    setter(value.toLowerCase());
  },
);

export default setLowercaseValue;
