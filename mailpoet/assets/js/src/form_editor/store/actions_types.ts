import { BlockInsertionPoint } from './state_types';

export type ToggleAction = {
  type: string;
  toggleTo: boolean;
};

export type ToggleBlockInserterAction = {
  type: string;
  value: boolean | BlockInsertionPoint;
};
