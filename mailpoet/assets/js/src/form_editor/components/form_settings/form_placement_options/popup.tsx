import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './icons/popup_icon';

function Popup(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.popup.enabled}
      label={MailPoet.I18n.t('placePopupFormOnPages')}
      icon={Icon}
      onClick={(): void => showPlacementSettings('popup')}
      canBeActive
    />
  );
}

export default Popup;
