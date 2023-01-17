import { MailPoet } from 'mailpoet';
import { moreVertical } from '@wordpress/icons';
import { DropdownMenu } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';
import { DiscoveryTask } from './discovery-task';
import { ContentSection } from './content-section';

type Props = {
  onHide: () => void;
};

export function ProductDiscovery({ onHide }: Props): JSX.Element {
  const { tasksStatus, isWooCommerceActive, isDiscoveryDone } = useSelect(
    (select) => ({
      tasksStatus: select(storeName).getProductDiscoveryTasksStatus(),
      isWooCommerceActive: select(storeName).getIsWooCommerceActive(),
      isDiscoveryDone: select(storeName).getIsProductDiscoveryDone(),
    }),
    [],
  );
  const tasks = [];
  tasks.push(
    <DiscoveryTask
      key="setUpWelcomeCampaign"
      title={MailPoet.I18n.t('setUpWelcomeCampaign')}
      description={MailPoet.I18n.t('setUpWelcomeCampaignDesc')}
      link="admin.php?page=mailpoet-automation-templates"
      imgSrc={`${MailPoet.cdnUrl}homepage/welcome-email-illustration.png`}
      isDone={tasksStatus.setUpWelcomeCampaign}
      doneMessage={MailPoet.I18n.t('setUpWelcomeCampaignDone')}
    />,
    <DiscoveryTask
      key="addSubscriptionForm"
      title={MailPoet.I18n.t('addSubscriptionForm')}
      description={MailPoet.I18n.t('addSubscriptionFormDesc')}
      link="admin.php?page=mailpoet-form-editor-template-selection"
      imgSrc={`${MailPoet.cdnUrl}homepage/subscription-form-illustration.png`}
      isDone={tasksStatus.addSubscriptionForm}
      doneMessage={MailPoet.I18n.t('addSubscriptionFormDone')}
    />,
  );
  if (!isWooCommerceActive) {
    tasks.push(
      <DiscoveryTask
        key="sendFirstNewsletter"
        title={MailPoet.I18n.t('sendFirstNewsletter')}
        description={MailPoet.I18n.t('sendFirstNewsletterDesc')}
        link="admin.php?page=mailpoet-newsletters#/new"
        imgSrc={`${MailPoet.cdnUrl}homepage/newsletter-illustration.png`}
        isDone={tasksStatus.sendFirstNewsletter}
        doneMessage={MailPoet.I18n.t('sendFirstNewsletterDone')}
      />,
    );
  } else {
    tasks.push(
      <DiscoveryTask
        key="setUpAbandonedCartEmail"
        title={MailPoet.I18n.t('setUpAbandonedCartEmail')}
        description={MailPoet.I18n.t('setUpAbandonedCartEmailDesc')}
        link="admin.php?page=mailpoet-newsletters#/new/woocommerce/woocommerce_abandoned_shopping_cart/conditions"
        imgSrc={`${MailPoet.cdnUrl}homepage/woo-cart-email-illustration.png`}
        isDone={tasksStatus.setUpAbandonedCartEmail}
        doneMessage={MailPoet.I18n.t('setUpAbandonedCartEmailDone')}
      />,
      <DiscoveryTask
        key="brandWooEmails"
        title={MailPoet.I18n.t('brandWooEmails')}
        description={MailPoet.I18n.t('brandWooEmailsDesc')}
        link="admin.php?page=mailpoet-settings#/woocommerce"
        imgSrc={`${MailPoet.cdnUrl}homepage/woo-transactional-email-illustration.png`}
        isDone={tasksStatus.brandWooEmails}
        doneMessage={MailPoet.I18n.t('brandWooEmailsDone')}
      />,
    );
  }
  return (
    <>
      <ContentSection
        className="mailpoet-homepage-product-discovery"
        heading={MailPoet.I18n.t('startEngagingWithYourCustomers')}
        headingAfter={
          <DropdownMenu
            label={MailPoet.I18n.t('hideList')}
            icon={moreVertical}
            controls={[
              {
                title: MailPoet.I18n.t('hideList'),
                onClick: onHide,
                icon: null,
              },
            ]}
          />
        }
      >
        <ul>{tasks.map((item) => item)}</ul>
      </ContentSection>
      {isDiscoveryDone ? (
        <p className="mailpoet-task-list__all-set">
          {MailPoet.I18n.t('allDone')}{' '}
          <a
            href="#"
            onClick={(e) => {
              e.preventDefault();
              onHide();
            }}
          >
            {MailPoet.I18n.t('dismissTasks')}
          </a>
        </p>
      ) : null}
    </>
  );
}
