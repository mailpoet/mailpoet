import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { AutomationStatus } from '../../automation';
import { AutomationItem, storeName } from '../../store';

export const useRestoreButton = (
  automation: AutomationItem,
): Item | undefined => {
  const { restoreAutomation, restoreLegacyAutomation } = useDispatch(storeName);

  const restore = automation.isLegacy
    ? restoreLegacyAutomation
    : restoreAutomation;

  if (automation.status !== AutomationStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'restore',
    control: {
      title: __('Restore', 'mailpoet'),
      icon: null,
      onClick: () => restore(automation, AutomationStatus.DRAFT),
    },
  };
};
