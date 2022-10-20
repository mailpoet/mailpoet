import { __ } from '@wordpress/i18n';
import { Icon } from './icon';
import { Edit } from './edit';
import { StepType } from '../../../../editor/store/types';
import { DelayTypeOptions } from './types/delayTypes';

const getDelayInformation = (delayTypeValue: string, value: number): string =>
  DelayTypeOptions.reduce((previousValue, current): string => {
    if (current.value !== delayTypeValue) {
      return previousValue;
    }
    return current.subtitle(value);
  }, '');

export const step: StepType = {
  key: 'core:delay',
  group: 'actions',
  title: __('Delay', 'mailpoet'),
  foreground: '#7F54B3',
  background: '#f7edf7',
  description: __(
    'Wait some time before proceeding with the steps below',
    'mailpoet',
  ),
  subtitle: (data): string => {
    if (!data.args.delay || !data.args.delay_type) {
      return __('Not set up yet.', 'mailpoet');
    }

    return getDelayInformation(
      data.args.delay_type as string,
      data.args.delay as number,
    );
  },
  icon: Icon,
  edit: Edit,
} as const;
