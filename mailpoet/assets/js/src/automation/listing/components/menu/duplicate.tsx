import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Automation, AutomationStatus } from '../../automation';

export const useDuplicateButton = (
  automation: Automation,
): Item | undefined => {
  const { duplicateAutomation } = useDispatch(storeName);

  if (automation.status === AutomationStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'duplicate',
    control: {
      title: __('Duplicate', 'mailpoet'),
      icon: null,
      onClick: () => duplicateAutomation(automation),
    },
  };
};
