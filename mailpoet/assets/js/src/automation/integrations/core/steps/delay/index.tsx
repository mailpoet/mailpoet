import { Icon } from './icon';
import { Edit } from './edit';
import { StepType } from '../../../../editor/store/types';
import { DelayTypeOptions } from './types/delayTypes';

const getCorrectDelayTypeName = (
  delayTypeValue: string,
  isSingular: boolean,
): string =>
  DelayTypeOptions.reduce((previousValue, current): string => {
    if (current.value !== delayTypeValue) {
      return previousValue;
    }
    return isSingular ? current.singular : current.plural;
  }, '');

export const step: StepType = {
  key: 'core:delay',
  group: 'actions',
  title: 'Delay',
  description: 'Wait some time before proceeding with the steps below',
  subtitle: (data): string => {
    if (!data.args.delay || !data.args.delay_type) {
      return 'Not set up yet.';
    }

    return `Wait for ${data.args.delay as string} ${getCorrectDelayTypeName(
      data.args.delay_type as string,
      data.args.delay === 1,
    )}`;
  },
  icon: Icon(),
  edit: Edit,
} as const;
