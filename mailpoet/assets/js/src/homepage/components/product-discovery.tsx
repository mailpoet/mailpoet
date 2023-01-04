import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';
import { moreVertical } from '@wordpress/icons';
import { DropdownMenu } from '@wordpress/components';
import { DiscoveryTask } from './discovery-task';

export function ProductDiscovery() {
  const { isHidden } = useSelect(
    (select) => ({
      isHidden: select(storeName).getIsProductDiscoveryHidden(),
    }),
    [],
  );
  const { hideProductDiscovery } = useDispatch(storeName);
  if (isHidden) return null;

  const tasks = [];
  tasks.push(
    <DiscoveryTask
      key="setUpWelcomeCampaign"
      title={MailPoet.I18n.t('setUpWelcomeCampaign')}
      description={MailPoet.I18n.t('setUpWelcomeCampaignDesc')}
      link="admin.php?page=mailpoet-automation-templates"
    />,
    <DiscoveryTask
      key="addSubscriptionForm"
      title={MailPoet.I18n.t('addSubscriptionForm')}
      description={MailPoet.I18n.t('addSubscriptionFormDesc')}
      link="admin.php?page=mailpoet-form-editor-template-selection"
    />,
    <DiscoveryTask
      key="sendFirstNewsletter"
      title={MailPoet.I18n.t('sendFirstNewsletter')}
      description={MailPoet.I18n.t('sendFirstNewsletterDesc')}
      link="admin.php?page=mailpoet-newsletters#/new"
    />,
    <DiscoveryTask
      key="setUpAbandonedCartEmail"
      title={MailPoet.I18n.t('setUpAbandonedCartEmail')}
      description={MailPoet.I18n.t('setUpAbandonedCartEmailDesc')}
      link="admin.php?page=mailpoet-newsletters#/new/woocommerce/woocommerce_abandoned_shopping_cart/conditions"
    />,
    <DiscoveryTask
      key="brandWooEmails"
      title={MailPoet.I18n.t('brandWooEmails')}
      description={MailPoet.I18n.t('brandWooEmailsDesc')}
      link="admin.php?page=mailpoet-settings#/woocommerce"
    />,
  );

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
      <ul>{tasks.map((item) => item)}</ul>
    </div>
  );
}
