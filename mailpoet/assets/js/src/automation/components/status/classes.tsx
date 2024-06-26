import { AutomationStatus } from '../../listing/automation';

const SUCCESS_CLASS = 'success';
const WARNING_CLASS = 'warning';
const ERROR_CLASS = 'error';
const DEFAULT_CLASS = '';

type ClassProps = {
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

export const orderStatusClasses: ClassProps = {
  processing: SUCCESS_CLASS,
  'on-hold': WARNING_CLASS,
  failed: ERROR_CLASS,
  trash: ERROR_CLASS,
};
