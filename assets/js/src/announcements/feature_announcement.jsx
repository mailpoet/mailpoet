import React from 'react';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

let beamerLoaded = false;

const loadBeamer = (e) => {
  let s;
  e.preventDefault();
  if (!beamerLoaded) {
    MailPoet.Modal.loading(true);
    beamerLoaded = true;
    window.mailpoet_feature_announcement_has_news = false;
    s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = 'https://app.getbeamer.com/js/beamer-embed.js';
    document.getElementsByTagName('body')[0].appendChild(s);
  }
};

const FeatureAnnouncement = (props) => {
  const buttonClasses = classNames(
    'button mailpoet_feature_announcement_button',
    props.hasNews ? 'mailpoet_feature_announcement_dot' : ''
  );

  return (
    <div className="mailpoet_feature_announcement">
      <a href="" id="beamer-selector" onClick={loadBeamer} className={buttonClasses} title={MailPoet.I18n.t('whatsNew')}>
        <span className="mailpoet_feature_announcement_icon dashicons dashicons-carrot" />
      </a>
    </div>
  );
};

FeatureAnnouncement.propTypes = {
  hasNews: PropTypes.bool,
};

FeatureAnnouncement.defaultProps = {
  hasNews: false,
};

export default FeatureAnnouncement;
