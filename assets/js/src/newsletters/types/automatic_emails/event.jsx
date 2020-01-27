import React from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import PropTypes from 'prop-types';

class AutomaticEmailEvent extends React.PureComponent {
  render() {
    const event = this.props.event;
    const disabled = event.soon;

    let action;
    if (this.props.premium) {
      action = (
        <a
          href="?page=mailpoet-premium"
          target="_blank"
        >
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
      const onClick = !disabled ? _.partial(this.props.eventsConfigurator, event.slug) : null;
      action = (
        <a
          className="button button-primary"
          disabled={disabled}
          onClick={onClick}
          role="presentation"
          data-automation-id={`create_${event.slug}`}
          onKeyDown={(keyEvent) => {
            if ((['keydown', 'keypress'].includes(keyEvent.type) && ['Enter', ' '].includes(keyEvent.key))
            ) {
              keyEvent.preventDefault();
              onClick();
            }
          }}
        >
          {event.actionButtonTitle || MailPoet.I18n.t('setUp')}
        </a>
      );
    }

    return (
      <li data-type={event.slug}>
        <div className="mailpoet_thumbnail">
          {event.thumbnailImage ? <img src={event.thumbnailImage} alt="" /> : null}
        </div>
        <div className="mailpoet_boxes_content">
          <div className="mailpoet_description">
            <div className="title_and_badge">
              <h3>
                {event.title}
                {' '}
                {event.soon ? `(${MailPoet.I18n.t('soon')})` : ''}
              </h3>
              {event.badge ? (
                <span className={`mailpoet_badge mailpoet_badge_${event.badge.style}`}>
                  {event.badge.text}
                </span>
              ) : ''}
            </div>
            <p>{event.description}</p>
          </div>
          <div className="mailpoet_actions">
            {action}
          </div>
        </div>
      </li>
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
    thumbnailImage: PropTypes.string,
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
