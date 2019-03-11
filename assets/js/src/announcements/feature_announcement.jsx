import React from 'react';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

class FeatureAnnouncement extends React.Component {
  constructor(props) {
    super(props);
    this.loadBeamer = this.loadBeamer.bind(this);
    this.beamerCallback = this.beamerCallback.bind(this);
    this.showBeamer = this.showBeamer.bind(this);

    this.state = {
      showDot: props.hasNews,
      beamerLoaded: typeof window.Beamer !== 'undefined',
      beamerInitialized: false,
    };
  }

  loadBeamer() {
    window.beamer_config = {
      product_id: 'VvHbhYWy7118',
      selector: '#beamer-empty-element',
      language: window.mailpoet_user_locale,
      callback: this.beamerCallback,
    };
    MailPoet.Modal.loading(true);
    window.mailpoet_feature_announcement_has_news = false;
    this.setState({ beamerLoaded: true });
    const s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = 'https://app.getbeamer.com/js/beamer-embed.js';
    document.getElementsByTagName('body')[0].appendChild(s);
  }

  beamerCallback() {
    // We show Beamer panel only on first callback after initialization
    if (this.state.beamerInitialized) {
      return;
    }
    this.showBeamer();
    this.setState({ beamerInitialized: true });
  }

  showBeamer(event) {
    if (event) {
      event.preventDefault();
    }
    if (!this.state.beamerLoaded) {
      this.loadBeamer();
      return;
    }
    this.setState({ showDot: false });
    MailPoet.Modal.loading(false);
    window.Beamer.show();
    const data = { last_announcement_seen: Math.floor(Date.now() / 1000) };
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'user_flags',
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
          onClick={this.showBeamer}
          className={buttonClasses}
          title={MailPoet.I18n.t('whatsNew')}
        >
          <span className="mailpoet_feature_announcement_icon dashicons dashicons-carrot" />
        </button>
        <span id="beamer-empty-element" />
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
