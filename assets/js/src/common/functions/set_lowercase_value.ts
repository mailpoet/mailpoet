import { curry } from 'lodash';

const setLowercaseValue = curry((setter: (value: string) => any, value: string) => {
  setter(value.toLowerCase());
});

export default setLowercaseValue;
