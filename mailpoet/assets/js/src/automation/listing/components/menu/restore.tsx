import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Automation, AutomationStatus } from '../../automation';

export const useRestoreButton = (automation: Automation): Item | undefined => {
  const { restoreAutomation } = useDispatch(storeName);

  if (automation.status !== AutomationStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'restore',
    control: {
      title: __('Restore', 'mailpoet'),
      icon: null,
      onClick: () => restoreAutomation(automation, AutomationStatus.DRAFT),
    },
  };
};
