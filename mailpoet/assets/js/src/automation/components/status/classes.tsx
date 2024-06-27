import { AutomationStatus } from '../../listing/automation';

export const SUCCESS_CLASS = 'success';
export const WARNING_CLASS = 'warning';
export const ERROR_CLASS = 'error';
export const DEFAULT_CLASS = '';

export type ClassProps = {
  [key: string]: string;
};

export const automationStatusClasses: ClassProps = {
  [AutomationStatus.ACTIVE]: SUCCESS_CLASS,
  [AutomationStatus.DEACTIVATING]: WARNING_CLASS,
  [AutomationStatus.DRAFT]: DEFAULT_CLASS,
  [AutomationStatus.TRASH]: DEFAULT_CLASS,
};

export const automationRunStatusClasses: ClassProps = {
  running: WARNING_CLASS,
  cancelled: ERROR_CLASS,
  complete: DEFAULT_CLASS,
  failed: ERROR_CLASS,
};
