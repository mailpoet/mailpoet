import { PureComponent } from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import PropTypes from 'prop-types';
import Badge from 'common/badge/badge';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';

class AutomaticEmailEvent extends PureComponent {
  render() {
    const event = this.props.event;
    const disabled = event.soon;

    let action;
    if (this.props.premium) {
      action = (
        <a href="?page=mailpoet-premium" target="_blank">
          {MailPoet.I18n.t('premiumFeatureLink')}
        </a>
      );
    } else if (event.actionButtonLink && event.actionButtonTitle) {
      action = (
        <a
          href={event.actionButtonLink}
          target="_blank"
          rel="noopener noreferrer"
        >
          {event.actionButtonTitle}
        </a>
      );
    } else {
      const onClick = !disabled
        ? _.partial(this.props.eventsConfigurator, event.slug)
        : null;
      action = (
        <Button
          disabled={disabled}
          onClick={onClick}
          role="presentation"
          automationId={`create_${event.slug}`}
          onKeyDown={(keyEvent) => {
            if (
              ['keydown', 'keypress'].includes(keyEvent.type) &&
              ['Enter', ' '].includes(keyEvent.key)
            ) {
              keyEvent.preventDefault();
              onClick();
            }
          }}
        >
          {event.actionButtonTitle || MailPoet.I18n.t('setUp')}
        </Button>
      );
    }

    return (
      <div data-type={event.slug} className="mailpoet-newsletter-type">
        <div className="mailpoet-newsletter-type-image">
          {event.badge && <Badge title={event.badge.text} />}
        </div>
        <div className="mailpoet-newsletter-type-content">
          <Heading level={4}>
            {event.title} {event.soon && `(${MailPoet.I18n.t('soon')})`}
          </Heading>
          <p>{event.description}</p>
          <div className="mailpoet-flex-grow" />
          <div className="mailpoet-newsletter-type-action">{action}</div>
        </div>
      </div>
    );
  }
}

AutomaticEmailEvent.defaultProps = {
  premium: false,
};

AutomaticEmailEvent.propTypes = {
  premium: PropTypes.bool,
  eventsConfigurator: PropTypes.func.isRequired,
  event: PropTypes.shape({
    slug: PropTypes.string.isRequired,
    actionButtonLink: PropTypes.string,
    title: PropTypes.string.isRequired,
    soon: PropTypes.bool,
    badge: PropTypes.shape({
      style: PropTypes.string,
      text: PropTypes.string,
    }),
    description: PropTypes.string.isRequired,
    actionButtonTitle: PropTypes.string,
  }).isRequired,
};

export default AutomaticEmailEvent;
