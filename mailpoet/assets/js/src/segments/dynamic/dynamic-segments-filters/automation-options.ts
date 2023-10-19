import { MailPoet } from 'mailpoet';
import { sortFilters } from './sort-filters';
import { SegmentTypes } from '../types';

export enum AutomationsActionTypes {
  ENTERED_AUTOMATION = 'enteredAutomation',
  EXITED_AUTOMATION = 'exitedAutomation',
}

export const AutomationsOptions = [
  {
    value: AutomationsActionTypes.ENTERED_AUTOMATION,
    label: MailPoet.I18n.t('automationsEnteredAutomation'),
    group: SegmentTypes.Automations,
  },
  {
    value: AutomationsActionTypes.EXITED_AUTOMATION,
    label: MailPoet.I18n.t('automationsExitedAutomation'),
    group: SegmentTypes.Automations,
  },
].sort(sortFilters);
