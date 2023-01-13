import { __ } from '@wordpress/i18n';
import { Heading } from 'common/typography/heading/heading';
import { Grid } from 'common/grid';

type LandingpageImages = {
  heroImage: string;
  featureImages: {
    icon_1: string;
    icon_2: string;
    icon_3: string;
  };
  wooCommerceFeatureImages: {
    feature_1: string;
    feature_2: string;
    feature_3: string;
  };
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
const Images: LandingpageImages = window.mailpoet_landingpage_images;

function Content() {
  return (
    <div className="landing-content">
      <div className="hero-section mailpoet-content-center">
        <img
          src={Images.heroImage}
          alt=""
          className="hero-image landingpage-images"
        />
        <br />
        <br />
        <Heading level={4}>
          {__('Powering email marketing for 600,000+ websites', 'mailpoet')}
        </Heading>
        <br />

        <Grid.ThreeColumns className="landingpage-general-features">
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
        </Grid.ThreeColumns>
      </div>

      <div className="mailpoet-gap" />

      <div className="landingpage-wooCommerce-features">
        <div className="mailpoet-content-center">
          <Heading level={2}>
            {__('MailPoet + WooCommerce', 'mailpoet')}
          </Heading>
          <p>
            {__(
              'Hyper-relevant content for every stage of the customer’s journey',
              'mailpoet',
            )}
          </p>
        </div>

        <br />

        <Grid.TwoColumns className="landingpage-wooCommerce-feature-item">
          <div>
            <img
              src={Images.wooCommerceFeatureImages.feature_1}
              alt={__('Automate your marketing feature Image', 'mailpoet')}
              className="landingpage-images"
            />
          </div>
          <div>
            <strong>{__('Automate your marketing', 'mailpoet')}</strong>
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
      </div>
    </div>
  );
}
Content.displayName = 'Landingpage Content';

export { Content };
