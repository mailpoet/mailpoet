import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import { FormPlacementOption } from './form_placement_option';
import { PopupIcon } from './icons/popup_icon';

export function Popup(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

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
