import { resetLocaleData, setLocaleData } from '@wordpress/i18n';
import { getEmailSegmentOptions } from '../../../../assets/js/src/segments/dynamic/dynamic-segments-filters/email-options';
import {
  getWooCommerceMembershipOptions,
  getWooCommerceOptions,
  getWooCommerceSubscriptionOptions,
} from '../../../../assets/js/src/segments/dynamic/dynamic-segments-filters/woocommerce-options';

describe('segments/dynamic filter options', () => {
  // Translations are registered after the modules are imported
  beforeEach(() =>
    setLocaleData(
      {
        clicked: ['angeklickt'],
        'average order value': ['durchschnittlicher Auftragswert'],
        'is member of': ['ist Mitglied von'],
        'has active subscription': ['hat ein aktives Abonnement'],
      } as unknown as Record<string, string[]>,
      'mailpoet',
    ),
  );

  afterEach(() => resetLocaleData());

  it('translates the email options', () => {
    const labels = getEmailSegmentOptions().map((option) => option.label);
    expect(labels).to.include('angeklickt');
  });

  it('translates the WooCommerce options', () => {
    const labels = getWooCommerceOptions().map((option) => option.label);
    expect(labels).to.include('durchschnittlicher Auftragswert');
    expect(getWooCommerceMembershipOptions()[0].label).to.equal(
      'ist Mitglied von',
    );
    expect(getWooCommerceSubscriptionOptions()[0].label).to.equal(
      'hat ein aktives Abonnement',
    );
  });
});
