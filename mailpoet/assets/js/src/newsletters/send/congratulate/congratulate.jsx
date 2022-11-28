import { Component } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import moment from 'moment';

import { Success } from './success.jsx';
import { Fail } from './fail.jsx';
import { Loading } from './loading.jsx';

const SECONDS_WAITING_FOR_SUCCESS = 20;
const SECONDS_MINIMUIM_LOADING_SCREEN_DISPLAYED = 6;
const SUCCESS_IMAGE_INDEX = Math.floor(Math.random() * 4);

function successPageClosed() {
  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'settings',
    action: 'set',
    data: { show_congratulate_after_first_newsletter: false },
  }).always(() => {
    window.location = window.mailpoet_emails_page;
  });
}

function renderSuccess(newsletter, testingPassed) {
  if (testingPassed) {
    MailPoet.trackEvent('Cron testing done', {
      'Cron is working': 'true',
    });
  }
  return (
    <Success
      illustrationImageUrl={
        window.mailpoet_congratulations_success_images[SUCCESS_IMAGE_INDEX]
      }
      MSSPitchIllustrationUrl={
        window.mailpoet_congratulations_success_images[SUCCESS_IMAGE_INDEX]
      }
      successClicked={successPageClosed}
      newsletter={newsletter}
      isWoocommerceActive={window.mailpoet_woocommerce_active}
      subscribersCount={window.mailpoet_subscribers_count}
      purchaseUrl={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
        MailPoet.subscribersCount,
        MailPoet.currentWpUserEmail,
        'starter',
        {
          utm_medium: 'first-newsletter-congratulations',
          utm_campaign: 'purchase',
        },
      )}
    />
  );
}

function renderFail() {
  MailPoet.trackEvent('Cron testing done', {
    'Cron is working': 'false',
  });
  return (
    <Fail
      failClicked={() => {
        window.location = window.mailpoet_emails_page;
      }}
    />
  );
}

function renderLoading(showRichLoadingScreen) {
  return (
    <Loading
      illustrationImageUrl={window.mailpoet_congratulations_loading_image}
      successClicked={successPageClosed}
      showRichLoadingScreen={showRichLoadingScreen}
    />
  );
}

class Congratulate extends Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: true,
      newsletter: null,
      testingPassed: false,
      timeStart: moment(),
      minimumLoadingTimePassed: false,
    };
    this.tick = this.tick.bind(this);
  }

  componentDidMount() {
    this.loadNewsletter(this.props.match.params.id);
    this.tick();
  }

  componentDidUpdate(prevProps) {
    if (prevProps.match.params.id !== this.props.match.params.id) {
      this.loadNewsletter(this.props.match.params.id);
    }
  }

  tick() {
    if (
      moment()
        .subtract(SECONDS_WAITING_FOR_SUCCESS, 'second')
        .isAfter(this.state.timeStart)
    ) {
      this.setState({ error: true, loading: false });
    }
    if (this.state.loading) {
      this.loadNewsletter(this.props.match.params.id);
    }
    if (
      moment()
        .subtract(SECONDS_MINIMUIM_LOADING_SCREEN_DISPLAYED, 'seconds')
        .isAfter(this.state.timeStart)
    ) {
      this.setState({ minimumLoadingTimePassed: true });
    }
    if (this.state.loading || !this.state.minimumLoadingTimePassed) {
      setTimeout(this.tick, 2000);
    }
  }

  loadNewsletter(id) {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id,
      },
    }).done((response) => this.newsletterLoaded(response.data));
  }

  newsletterLoaded(newsletter) {
    if (newsletter.type !== 'standard' || newsletter.status === 'scheduled') {
      this.setState({
        newsletter,
        loading: false,
        minimumLoadingTimePassed: true,
      });
    } else if (
      newsletter.status === 'sent' ||
      newsletter.status === 'sending'
    ) {
      this.setState({ newsletter, loading: false, testingPassed: true });
    } else {
      this.setState({ newsletter });
    }
  }

  renderContent() {
    if (this.state.loading || !this.state.minimumLoadingTimePassed) {
      return renderLoading(!this.state.newsletter);
    }
    if (this.state.error) {
      return renderFail();
    }
    return renderSuccess(this.state.newsletter, this.state.testingPassed);
  }

  render() {
    return (
      <>
        <style
          /* eslint-disable-next-line react/no-danger */
          dangerouslySetInnerHTML={{
            __html: 'body { background: #fff; overflow-x: hidden; }',
          }}
        />
        <div className="mailpoet-congratulate">
          <div className="mailpoet-gap-large" />
          {this.renderContent()}
        </div>
      </>
    );
  }
}

Congratulate.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
};

Congratulate.displayName = 'Congratulate';

export { Congratulate };
