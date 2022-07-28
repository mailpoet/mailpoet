import { MailPoet } from 'mailpoet';
import { useDispatch } from '@wordpress/data';
import { SidebarIcon } from './icons/sidebar_icon';
import { FormPlacementOption } from './form_placement_option';

export function Other(): JSX.Element {
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={false}
      label={MailPoet.I18n.t('formPlacementOtherLabel')}
      icon={SidebarIcon}
      onClick={(): void => {
        void showPlacementSettings('others');
      }}
      canBeActive={false}
    />
  );
}
