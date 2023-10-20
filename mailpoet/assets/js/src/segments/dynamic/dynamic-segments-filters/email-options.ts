import { __ } from '@wordpress/i18n';
import { sortFilters } from './sort-filters';
import { EmailActionTypes, SegmentTypes } from '../types';

export const EmailSegmentOptions = [
  {
    value: EmailActionTypes.CLICKED,
    label: __('clicked', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED_ANY,
    label: __('clicked any email', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENED,
    label: __('machine-opened', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.NUMBER_RECEIVED,
    label: __('number of emails received', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
    label: __('number of machine-opens', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.OPENS_ABSOLUTE_COUNT,
    label: __('number of opens', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.OPENED,
    label: __('opened', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.WAS_SENT,
    label: __('was sent', 'mailpoet'),
    group: SegmentTypes.Email,
  },
].sort(sortFilters);
