import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

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
      label={MailPoet.I18n.t('placePopupFormOnPages')}
      icon={PopupIcon}
      onClick={(): void => {
        void showPlacementSettings('popup');
      }}
      canBeActive
    />
  );
}
