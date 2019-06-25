import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import React from 'react';
import { Link, withRouter } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';
import PropTypes from 'prop-types';
import NewsletterGeneralStats from './newsletter_stats.jsx';
import NewsletterStatsInfo from './newsletter_info.jsx';
import SubscriberEngagementListing from './subscriber_engagement.jsx';
import PurchasedProducts from './purchased_products.jsx';
import PremiumBanner from './premium_banner.jsx';

class CampaignStatsPage extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      item: {},
      loading: true,
      savingSegment: false,
      segmentCreated: false,
      segmentErrors: [],
    };
    this.handleCreateSegment = this.handleCreateSegment.bind(this);
  }

  componentDidMount() {
    const { match } = this.props;
    // Scroll to top in case we're coming
    // from the middle of a long newsletter listing
    window.scrollTo(0, 0);
    this.loadItem(match.params.id);
  }

  componentWillReceiveProps(props) {
    const { match } = this.props;
    if (match.params.id !== props.match.params.id) {
      this.loadItem(props.match.params.id);
    }
  }

  handleCreateSegment(group, newsletter, linkId) {
    const name = `${newsletter.subject} – ${group}`;
    this.setState({ savingSegment: true, segmentCreated: false, segmentErrors: [] });
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'dynamic_segments',
      action: 'save',
      data: {
        segmentType: 'email',
        action: group === 'unopened' ? 'notOpened' : group,
        newsletter_id: newsletter.id,
        link_id: linkId,
        name,
      },
    }).always(() => {
      this.setState({ savingSegment: false });
    }).done(() => {
      this.setState({
        segmentCreated: true,
        segmentName: name,
      });
    }).fail((response) => {
      this.setState({
        segmentErrors:
          response.errors.map(error => ((error.error === 409) ? MailPoet.I18n.t('segmentExists') : error.message)),
      });
    });
  }

  loadItem(id) {
    const { history } = this.props;
    this.setState({ loading: true });
    MailPoet.Modal.loading(true);

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: window.mailpoet_premium_active ? 'stats' : 'newsletters',
      action: window.mailpoet_premium_active ? 'get' : 'getWithStats',
      data: {
        id,
      },
    }).always(() => {
      MailPoet.Modal.loading(false);
    }).done((response) => {
      this.setState({
        loading: false,
        item: response.data,
      });
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map(error => error.message),
        { scroll: true }
      );
      this.setState({
        loading: false,
        item: {},
      }, () => {
        history.push('/');
      });
    });
  }

  renderCreateSegmentSuccess() {
    const { segmentCreated, segmentName } = this.state;
    let segmentCreatedSuccessMessage;

    if (segmentCreated) {
      let message = ReactStringReplace(
        MailPoet.I18n.t('successMessage'),
        /\[link\](.*?)\[\/link\]/g,
        (match, i) => (
          <a
            key={i}
            href="?page=mailpoet-newsletters#/new"
          >
            {match}
          </a>
        )
      );

      message = ReactStringReplace(message, '%s', () => segmentName);

      segmentCreatedSuccessMessage = (
        <div className="mailpoet_notice notice inline notice-success">
          <p>{message}</p>
        </div>
      );
    }

    return segmentCreatedSuccessMessage;
  }

  renderCreateSegmentError() {
    const { segmentErrors } = this.state;
    let error;

    if (segmentErrors.length > 0) {
      error = (
        <div>
          {segmentErrors.map(errorMessage => (
            <div className="mailpoet_notice notice inline error" key={`error-${errorMessage}`}>
              <p>{errorMessage}</p>
            </div>
          ))}
        </div>
      );
    }

    return error;
  }

  render() {
    const { item, loading, savingSegment } = this.state;
    const newsletter = item;
    const { match, location } = this.props;

    if (loading || !newsletter.queue) {
      return (
        <div>
          <h1 className="title">
            {MailPoet.I18n.t('statsTitle')}
            <Link
              className="page-title-action"
              to="/"
            >
              {MailPoet.I18n.t('backToList')}
            </Link>
          </h1>
        </div>
      );
    }

    return (
      <div>
        <h1 className="title">
          {`${MailPoet.I18n.t('statsTitle')}: ${newsletter.subject}`}
          <Link
            className="page-title-action"
            to="/"
          >
            {MailPoet.I18n.t('backToList')}
          </Link>
        </h1>

        <div className="mailpoet_stat_triple-spaced">
          <div className="mailpoet_stat_info">
            <NewsletterStatsInfo newsletter={newsletter} />
          </div>
          <div className="mailpoet_stat_general">
            <NewsletterGeneralStats newsletter={newsletter} />
          </div>
          <div style={{ clear: 'both' }} />
        </div>

        <h2>{MailPoet.I18n.t('clickedLinks')}</h2>

        <div className="mailpoet_stat_triple-spaced">
          {Hooks.applyFilters('mailpoet_newsletters_clicked_links_table', <PremiumBanner />, newsletter.clicked_links)}
        </div>

        <div className="mailpoet_stat_triple-spaced">
          <PurchasedProducts newsletter={newsletter} />
        </div>

        <h2>{MailPoet.I18n.t('subscriberEngagement')}</h2>

        {this.renderCreateSegmentSuccess()}
        {this.renderCreateSegmentError()}

        <SubscriberEngagementListing
          location={location}
          params={match.params}
          newsletter={newsletter}
          handleCreateSegment={this.handleCreateSegment}
          savingSegment={savingSegment}
        />
      </div>
    );
  }
}

CampaignStatsPage.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.object.isRequired,
  }).isRequired,
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(CampaignStatsPage);
