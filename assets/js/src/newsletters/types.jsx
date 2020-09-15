import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import _ from 'underscore';
import { withRouter } from 'react-router-dom';
import { GlobalContext } from 'context/index.jsx';

import AutomaticEmailEventsList from 'newsletters/types/automatic_emails/events_list.jsx';
import AutomaticEmailEventGroupLogos from 'newsletters/types/automatic_emails/event_group_logos.jsx';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import ModalCloseIcon from 'common/modal/close_icon';

class NewsletterTypes extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isCreating: false,
    };
  }

  setupNewsletter = (type) => {
    if (type !== undefined) {
      this.props.history.push(`/new/${type}`);
      MailPoet.trackEvent('Emails > Type selected', {
        'MailPoet Free version': window.mailpoet_version,
        'Email type': type,
      });
    }
  };

  getAutomaticEmails = () => {
    if (!window.mailpoet_woocommerce_automatic_emails) return [];
    let automaticEmails = window.mailpoet_woocommerce_automatic_emails;
    if (this.props.filter) {
      automaticEmails = _.filter(automaticEmails, this.props.filter);
    }

    return _.map(automaticEmails, (automaticEmail) => {
      const email = automaticEmail;
      return (
        <React.Fragment key={email.slug}>
          {!this.props.filter && (
            <div className="mailpoet-newsletter-types-separator">
              <div className="mailpoet-newsletter-types-separator-line" />
              <div className="mailpoet-newsletter-types-separator-logo">
                {AutomaticEmailEventGroupLogos[email.slug] || null}
              </div>
              <div className="mailpoet-newsletter-types-separator-line" />
            </div>
          )}

          <AutomaticEmailEventsList
            email={email}
            history={this.props.history}
          />

          {email.slug === 'woocommerce' && this.getAdditionalTypes().map((type) => this.renderType(type), this)}
        </React.Fragment>
      );
    });
  }

  getAdditionalTypes = () => {
    const show = window.mailpoet_woocommerce_active;
    if (!show) {
      return [];
    }
    return [
      {
        slug: 'wc_transactional',
        title: MailPoet.I18n.t('wooCommerceCustomizerTypeTitle'),
        description: MailPoet.I18n.t('wooCommerceCustomizerTypeDescription'),
        action: (
          <Button
            automationId="customize_woocommerce"
            onClick={this.openWooCommerceCustomizer}
            tabIndex={0}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                this.openWooCommerceCustomizer();
              }
            }}
          >
            {MailPoet.I18n.t('customize')}
          </Button>
        ),
      },
    ];
  };

  openWooCommerceCustomizer = async () => {
    MailPoet.trackEvent('Emails > Type selected', {
      'MailPoet Free version': window.mailpoet_version,
      'Email type': 'wc_transactional',
    });
    let emailId = window.mailpoet_woocommerce_transactional_email_id;
    if (!emailId) {
      try {
        const response = await MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'settings',
          action: 'set',
          data: {
            'woocommerce.use_mailpoet_editor': 1,
          },
        });
        emailId = response.data.woocommerce.transactional_email_id;
        MailPoet.trackEvent('Emails > WooCommerce email customizer enabled', {
          'MailPoet Free version': window.mailpoet_version,
        });
      } catch (response) {
        if (response.errors.length > 0) {
          this.context.notices.error(
            response.errors.map((error) => <p key={error.message}>{error.message}</p>),
            { scroll: true }
          );
        }
        return;
      }
    }
    window.location.href = `?page=mailpoet-newsletter-editor&id=${emailId}`;
  };

  createNewsletter = (type) => {
    this.setState({ isCreating: true });
    MailPoet.trackEvent('Emails > Type selected', {
      'MailPoet Free version': window.mailpoet_version,
      'Email type': type,
    });
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type,
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
      },
    }).done((response) => {
      this.props.history.push(`/template/${response.data.id}`);
    }).fail((response) => {
      this.setState({ isCreating: false });
      if (response.errors.length > 0) {
        this.context.notices.error(
          response.errors.map((error) => <p key={error.message}>{error.message}</p>),
          { scroll: true }
        );
      }
    });
  }

  renderType = (type) => {
    const badgeClassName = (window.mailpoet_is_new_user === true) ? 'mailpoet_badge mailpoet_badge_video' : 'mailpoet_badge mailpoet_badge_video mailpoet_badge_video_grey';

    return (
      <div key={type.slug} data-type={type.slug} className="mailpoet-newsletter-type">
        <div className="mailpoet-newsletter-type-image" />
        <div className="mailpoet-newsletter-type-content">
          <Heading level={4}>
            {type.title}
            {' '}
            {type.beta ? `(${MailPoet.I18n.t('beta')})` : ''}
          </Heading>
          <p>{type.description}</p>
          { type.videoGuide && (
            <a className={badgeClassName} href={type.videoGuide} data-beacon-article={type.videoGuideBeacon} target="_blank" rel="noopener noreferrer">
              <span className="dashicons dashicons-format-video" />
              {MailPoet.I18n.t('seeVideoGuide')}
            </a>
          ) }
          <div className="mailpoet-flex-grow" />
          <div className="mailpoet-newsletter-type-action">
            {type.action}
          </div>
        </div>
      </div>
    );
  }

  render() {
    const createStandardNewsletter = _.partial(this.createNewsletter, 'standard');
    const createNotificationNewsletter = _.partial(this.setupNewsletter, 'notification');
    const createWelcomeNewsletter = _.partial(this.setupNewsletter, 'welcome');

    const defaultTypes = [
      {
        slug: 'standard',
        title: MailPoet.I18n.t('regularNewsletterTypeTitle'),
        description: MailPoet.I18n.t('regularNewsletterTypeDescription'),
        action: (
          <Button
            automationId="create_standard"
            onClick={createStandardNewsletter}
            tabIndex={0}
            disabled={this.state.isCreating}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                createStandardNewsletter();
              }
            }}
          >
            {MailPoet.I18n.t('create')}
          </Button>
        ),
      },
      {
        slug: 'welcome',
        title: MailPoet.I18n.t('welcomeNewsletterTypeTitle'),
        description: MailPoet.I18n.t('welcomeNewsletterTypeDescription'),
        videoGuide: 'https://kb.mailpoet.com/article/254-video-guide-to-welcome-emails',
        videoGuideBeacon: '5b05ebf20428635ba8b2aa53',
        action: (
          <Button
            onClick={createWelcomeNewsletter}
            automationId="create_welcome"
            disabled={this.state.isCreating}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                createWelcomeNewsletter();
              }
            }}
            tabIndex={0}
          >
            {MailPoet.I18n.t('setUp')}
          </Button>
        ),
      },
      {
        slug: 'notification',
        title: MailPoet.I18n.t('postNotificationNewsletterTypeTitle'),
        description: MailPoet.I18n.t('postNotificationNewsletterTypeDescription'),
        videoGuide: 'https://kb.mailpoet.com/article/210-video-guide-to-post-notifications',
        videoGuideBeacon: '59ba6fb3042863033a1cd5a5',
        action: (
          <Button
            automationId="create_notification"
            onClick={createNotificationNewsletter}
            disabled={this.state.isCreating}
            tabIndex={0}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                createNotificationNewsletter();
              }
            }}
          >
            {MailPoet.I18n.t('setUp')}
          </Button>
        ),
      },
    ];

    let types = Hooks.applyFilters('mailpoet_newsletters_types', [
      ...defaultTypes,
    ], this);
    if (this.props.filter) {
      types = types.filter(this.props.filter);
    }

    const templatesGETUrl = MailPoet.Ajax.constructGetUrl({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'getAll',
    });

    return (
      <>
        <link rel="prefetch" href={window.mailpoet_editor_javascript_url} as="script" />

        <div className="mailpoet-newsletter-types">
          <div className="mailpoet-newsletter-types-close">
            <button type="button" onClick={() => this.props.history.push('/')} className="mailpoet-modal-close">{ModalCloseIcon}</button>
          </div>

          {types.map((type) => this.renderType(type), this)}

          {this.getAutomaticEmails()}
        </div>

        <link rel="prefetch" href={templatesGETUrl} as="fetch" />
      </>
    );
  }
}

NewsletterTypes.contextType = GlobalContext;

NewsletterTypes.propTypes = {
  filter: PropTypes.func,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

NewsletterTypes.defaultProps = {
  filter: null,
};

export default withRouter(NewsletterTypes);
