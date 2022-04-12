import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { withFeatureAnnouncement } from './with_feature_announcement';

type Props = {
  hasNews: boolean;
  onBeamerClick: () => void;
};

function FeatureAnnouncementComponent({ hasNews, onBeamerClick }: Props) {
  const buttonClasses = classnames(
    'button mailpoet_feature_announcement_button',
    hasNews ? 'mailpoet_feature_announcement_dot' : '',
  );
  return (
    <div className="mailpoet_feature_announcement">
      <button
        type="button"
        onClick={onBeamerClick}
        className={buttonClasses}
        title={MailPoet.I18n.t('whatsNew')}
      >
        <span className="mailpoet_feature_announcement_icon dashicons dashicons-carrot" />
      </button>
      <span id="beamer-empty-element" />
    </div>
  );
}

const FeatureAnnouncement = withFeatureAnnouncement(
  FeatureAnnouncementComponent,
);
export { FeatureAnnouncement };
