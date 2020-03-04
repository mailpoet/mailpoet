import { State, Settings } from './types';

export default function makeDefaultState(data: Settings): State {
  return {
    save: {
      inProgress: false,
      error: null,
    },
    data,
  };
}
