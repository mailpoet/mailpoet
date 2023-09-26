import { BlockInsertionPoint } from './state_types';
import { CustomField } from './form_data_types';

export type ToggleAction = {
  type: string;
  toggleTo: boolean;
};

export type ToggleBlockInserterAction = {
  type: string;
  value: boolean | BlockInsertionPoint;
};

export type CustomFieldStartedAction = {
  type: 'CREATE_CUSTOM_FIELD_STARTED';
  customField: CustomField;
};

export type ToggleSidebarPanelAction = {
  type: 'TOGGLE_SIDEBAR_PANEL';
  id: string;
  toggleTo?: boolean;
};
