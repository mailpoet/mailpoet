import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { FormPlacementOption } from './form-placement-option';
import { PopupIcon } from './icons/popup-icon';
import { storeName } from '../../../store';

export function Popup(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch(storeName);

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.popup.enabled}
      label={__('Pop-up', 'mailpoet')}
      icon={PopupIcon}
      onClick={(): void => {
        void showPlacementSettings('popup');
      }}
      canBeActive
    />
  );
}
