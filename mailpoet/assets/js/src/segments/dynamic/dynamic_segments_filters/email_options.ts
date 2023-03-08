import { EmailActionTypes, SegmentTypes } from 'segments/dynamic/types';
import { MailPoet } from 'mailpoet';

export const EmailSegmentOptions = [
  {
    value: EmailActionTypes.OPENS_ABSOLUTE_COUNT,
    label: MailPoet.I18n.t('emailActionOpensAbsoluteCount'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
    label: MailPoet.I18n.t('emailActionMachineOpensAbsoluteCount'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.OPENED,
    label: MailPoet.I18n.t('emailActionOpened'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENED,
    label: MailPoet.I18n.t('emailActionMachineOpened'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED,
    label: MailPoet.I18n.t('emailActionClicked'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED_ANY,
    label: MailPoet.I18n.t('emailActionClickedAnyEmail'),
    group: SegmentTypes.Email,
  },
];
