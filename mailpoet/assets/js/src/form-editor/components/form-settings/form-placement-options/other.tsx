import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { SidebarIcon } from './icons/sidebar-icon';
import { FormPlacementOption } from './form-placement-option';
import { storeName } from '../../../store';

export function Other(): JSX.Element {
  const { showPlacementSettings } = useDispatch(storeName);

  return (
    <FormPlacementOption
      active={false}
      label={__('Others (widget)', 'mailpoet')}
      icon={SidebarIcon}
      onClick={(): void => {
        void showPlacementSettings('others');
      }}
      canBeActive={false}
    />
  );
}
