import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';
import { moreVertical } from '@wordpress/icons';
import { DropdownMenu } from '@wordpress/components';

export function ProductDiscovery() {
  const { isHidden } = useSelect(
    (select) => ({
      isHidden: select(storeName).getIsProductDiscoveryHidden(),
    }),
    [],
  );
  const { hideProductDiscovery } = useDispatch(storeName);
  if (isHidden) return null;
  return (
    <div className="mailpoet-homepage-section__container">
      <div className="mailpoet-homepage-section__heading">
        <h2>{MailPoet.I18n.t('startEngagingWithYourCustomers')}</h2>
        <DropdownMenu
          label={MailPoet.I18n.t('hideList')}
          icon={moreVertical}
          controls={[
            {
              title: MailPoet.I18n.t('hideList'),
              onClick: hideProductDiscovery,
              icon: null,
            },
          ]}
        />
      </div>
    </div>
  );
}
