import MailPoet from 'mailpoet';
import { useDispatch } from '@wordpress/data';
import Icon from './icons/sidebar_icon';
import FormPlacementOption from './form_placement_option';

function Other(): JSX.Element {
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={false}
      label={MailPoet.I18n.t('formPlacementOtherLabel')}
      icon={Icon}
      onClick={(): void => showPlacementSettings('others')}
      canBeActive={false}
    />
  );
}

export default Other;
