import React from 'react';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

class FeatureAnnouncement extends React.Component {
  constructor(props) {
    super(props);
    this.loadBeamer = this.loadBeamer.bind(this);
    this.beamerCallback = this.beamerCallback.bind(this);

    this.state = {
      showDot: props.hasNews,
      beamerLoaded: typeof window.Beamer !== 'undefined',
    };
  }

  loadBeamer(e) {
    e.preventDefault();
    if (!this.state.beamerLoaded) {
      window.beamer_config = {
        product_id: 'VvHbhYWy7118',
        selector: 'beamer-selector',
        language: window.mailpoet_user_locale,
        callback: this.beamerCallback,
      };
      MailPoet.Modal.loading(true);
      this.setState({ beamerLoaded: true });
      window.mailpoet_feature_announcement_has_news = false;
      const s = document.createElement('script');
      s.type = 'text/javascript';
      s.src = 'https://app.getbeamer.com/js/beamer-embed.js';
      document.getElementsByTagName('body')[0].appendChild(s);
    }
  }

  beamerCallback() {
    this.setState({ showDot: false });
    MailPoet.Modal.loading(false);
    window.Beamer.show();
    const data = { last_announcement_seen: window.mailpoet_last_announcement_seen || {} };
    const userId = window.mailpoet_current_wp_user.ID;
    data.last_announcement_seen[userId] = Math.floor(Date.now() / 1000);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    });
  }

  render() {
    const buttonClasses = classNames(
      'button mailpoet_feature_announcement_button',
      this.state.showDot ? 'mailpoet_feature_announcement_dot' : ''
    );
    return (
      <div className="mailpoet_feature_announcement">
        <button
          type="button"
          id="beamer-selector"
          onClick={this.loadBeamer}
          className={buttonClasses}
          title={MailPoet.I18n.t('whatsNew')}
        >
          <span className="mailpoet_feature_announcement_icon dashicons dashicons-carrot" />
        </button>
      </div>

    );
  }
}

FeatureAnnouncement.propTypes = {
  hasNews: PropTypes.bool,
};

FeatureAnnouncement.defaultProps = {
  hasNews: false,
};

export default FeatureAnnouncement;
