import { State } from './types';

export function getInitialState(): State {
  return {
    inserterSidebar: {
      isOpened: false,
    },
    listviewSidebar: {
      isOpened: false,
    },
  };
}
