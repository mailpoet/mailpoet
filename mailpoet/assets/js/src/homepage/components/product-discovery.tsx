import { MailPoet } from 'mailpoet';

export function ProductDiscovery() {
  return (
    <div className="mailpoet-homepage-section__container">
      <div className="mailpoet-homepage-section__heading">
        <h2>{MailPoet.I18n.t('startEngagingWithYourCustomers')}</h2>
      </div>
    </div>
  );
}
