import { __, _x } from '@wordpress/i18n';
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

const keywords = [
  __('wait', 'mailpoet'),
  __('pause', 'mailpoet'),
  __('delay', 'mailpoet'),
  __('time', 'mailpoet'),
];
export const step: StepType = {
  key: 'core:delay',
  group: 'actions',
  title: () => _x('Delay', 'noun', 'mailpoet'),
  description: () =>
    __('Wait some time before proceeding with the steps below.', 'mailpoet'),
  subtitle: (data): string => {
    if (!data.args.delay || !data.args.delay_type) {
      return __('Not set up yet', 'mailpoet');
    }

    return getDelayInformation(
      data.args.delay_type as string,
      data.args.delay as number,
    );
  },
  keywords,
  foreground: '#7F54B3',
  background: '#f7edf7',
  icon: Icon,
  edit: Edit,
} as const;
