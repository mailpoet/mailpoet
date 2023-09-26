import { MailPoet } from 'mailpoet';
import { useDispatch } from '@wordpress/data';
import { SidebarIcon } from './icons/sidebar_icon';
import { FormPlacementOption } from './form_placement_option';
import { storeName } from '../../../store';

export function Other(): JSX.Element {
  const { showPlacementSettings } = useDispatch(storeName);

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
