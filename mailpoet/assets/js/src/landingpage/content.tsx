import { __, sprintf } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Heading } from 'common/typography/heading/heading';
import { Grid } from 'common/grid';

const Images = {
  heroImage: `${MailPoet.cdnUrl}landingpage/landingpage-hero-image.png`,
  featureImages: {
    icon_1: `${MailPoet.cdnUrl}landingpage/feature_icon_1.png`,
    icon_2: `${MailPoet.cdnUrl}landingpage/feature_icon_2.png`,
    icon_3: `${MailPoet.cdnUrl}landingpage/feature_icon_3.png`,
    icon_4: `${MailPoet.cdnUrl}landingpage/feature_icon_4.png`,
  },
  wooCommerceFeatureImages: {
    feature_1: `${MailPoet.cdnUrl}landingpage/woo_feature_automate_your_marketing.png`,
    feature_2: `${MailPoet.cdnUrl}landingpage/woo_feature_measure_revenue_per_email.png`,
    feature_3: `${MailPoet.cdnUrl}landingpage/woo_feature_let_your_brand_shine.png`,
    feature_4: `${MailPoet.cdnUrl}landingpage/woo_feature_rescue_abandoned_carts.png`,
  },
};

function Content() {
  return (
    <section className="landing-content">
      <div className="hero-section mailpoet-content-center">
        <img
          src={Images.heroImage}
          alt=""
          className="hero-image landingpage-images"
        />
        <br />
        <br />
        <Heading level={4}>
          {
            // translators: %s is number of websites using MailPoet, e.g., "700,000+"
            sprintf(
              __(
                'Powerful email marketing, trusted by %s websites',
                'mailpoet',
              ),
              '700,000+',
            )
          }
        </Heading>
        <br />

        <Grid.FourColumns className="landingpage-general-features">
          <div>
            <img
              src={Images.featureImages.icon_1}
              alt=""
              className="landingpage-feature-icon"
            />
            <strong>{__('Deliver beautiful emails', 'mailpoet')}</strong>
            <p>
              {__(
                'Choose from our pre-built templates or create your own with our drag-and-drop email builder.',
                'mailpoet',
              )}
            </p>
          </div>

          <div>
            <img
              src={Images.featureImages.icon_2}
              alt=""
              className="landingpage-feature-icon"
            />
            <strong>{__('Grow your mailing list', 'mailpoet')}</strong>
            <p>
              {__(
                'Use our custom sign-up forms to reach more subscribers while you deliver engaging content.',
                'mailpoet',
              )}
            </p>
          </div>

          <div>
            <img
              src={Images.featureImages.icon_3}
              alt=""
              className="landingpage-feature-icon"
            />
            <strong>{__('Reach the right people', 'mailpoet')}</strong>
            <p>
              {__(
                'From the first hello to repeated purchases, send emails to the right people at the right time.',
                'mailpoet',
              )}
            </p>
          </div>

          <div>
            <img
              src={Images.featureImages.icon_4}
              alt=""
              className="landingpage-feature-icon"
            />
            <strong>{__('Save time with automations', 'mailpoet')}</strong>
            <p>
              {__(
                'Set up smart triggers based on user behavior to automate list management, email sending, and more.',
                'mailpoet',
              )}
            </p>
          </div>
        </Grid.FourColumns>
      </div>

      <div className="mailpoet-gap" />

      <div className="landingpage-wooCommerce-features">
        <div className="mailpoet-content-center">
          <Heading level={2}>
            {__('MailPoet + WooCommerce', 'mailpoet')}
          </Heading>
          <p>
            {__(
              'Deliver hyper-relevant content at every stage of your customer’s journey',
              'mailpoet',
            )}
          </p>
        </div>

        <br />

        <Grid.TwoColumns className="landingpage-wooCommerce-feature-item">
          <div>
            <img
              src={Images.wooCommerceFeatureImages.feature_1}
              alt={__(
                'Automate customer communication feature Image',
                'mailpoet',
              )}
              className="landingpage-images"
            />
          </div>
          <div>
            <strong>{__('Automate customer communication', 'mailpoet')}</strong>
            <p>
              {__(
                'Drive sales and build loyalty through automated marketing messages that respond to your customer’s purchase data.',
                'mailpoet',
              )}
            </p>
          </div>
        </Grid.TwoColumns>
        <Grid.TwoColumns className="landingpage-wooCommerce-feature-item">
          <div>
            <img
              src={Images.wooCommerceFeatureImages.feature_2}
              alt={__('Measure revenue per email feature Image', 'mailpoet')}
              className="landingpage-images"
            />
          </div>
          <div>
            <strong>{__('Measure revenue per email', 'mailpoet')}</strong>
            <p>
              {__(
                'See how much revenue your campaign is bringing and make improvements based on auto-generated email statistics.',
                'mailpoet',
              )}
            </p>
          </div>
        </Grid.TwoColumns>
        <Grid.TwoColumns className="landingpage-wooCommerce-feature-item">
          <div>
            <img
              src={Images.wooCommerceFeatureImages.feature_3}
              alt={__('Let your brand shine feature Image', 'mailpoet')}
              className="landingpage-images"
            />
          </div>
          <div>
            <strong>{__('Let your brand shine', 'mailpoet')}</strong>
            <p>
              {__(
                "Use our inbuilt WooCommerce email customizer to design your store's transactional emails and build customer confidence.",
                'mailpoet',
              )}
            </p>
          </div>
        </Grid.TwoColumns>
        <Grid.TwoColumns className="landingpage-wooCommerce-feature-item">
          <div>
            <img
              src={Images.wooCommerceFeatureImages.feature_4}
              alt={__('Rescue abandoned carts feature Image', 'mailpoet')}
              className="landingpage-images"
            />
          </div>
          <div>
            <strong>{__('Rescue abandoned carts', 'mailpoet')}</strong>
            <p>
              {__(
                'Set up automated abandoned cart emails to win customers back and recover lost revenue.',
                'mailpoet',
              )}
            </p>
          </div>
        </Grid.TwoColumns>
      </div>
    </section>
  );
}
Content.displayName = 'Landingpage Content';

export { Content };
