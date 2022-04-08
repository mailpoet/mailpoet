import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import Icon from './icons/below_pages_icon';
import FormPlacementOption from './form_placement_option';

function BelowPages(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );

  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.belowPosts.enabled}
      label={MailPoet.I18n.t('placeFormBellowPages')}
      icon={Icon}
      onClick={(): void => showPlacementSettings('below_post')}
      canBeActive
    />
  );
}

export default BelowPages;
