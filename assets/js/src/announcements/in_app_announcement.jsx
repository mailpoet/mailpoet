import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import InAppAnnouncementDot from './in_app_announcement_dot.jsx';

class InAppAnnouncement extends React.Component {
  constructor(props) {
    super(props);
    this.saveDisplayed = this.saveDisplayed.bind(this);

    this.state = {
      announcementsSettings: window.mailpoet_in_app_announcements || null,
    };
  }

  saveDisplayed() {
    const settings = Object.assign({}, this.state.announcementsSettings);
    settings.displayed.push(this.props.showOnlyOnceSlug);
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data: { in_app_announcements: settings },
    }).always(() => (this.setState({ announcementsSettings: settings })));
  }

  render() {
    if (this.props.showToNewUser !== null
      && window.mailpoet_is_new_user !== this.props.showToNewUser
    ) {
      return null;
    }

    if (this.props.validUntil !== null
      && this.props.validUntil < new Date()
    ) {
      return null;
    }

    if (this.props.showToPremiumUser !== null
      && window.mailpoet_premium_active !== this.props.showToPremiumUser
    ) {
      return null;
    }

    if (this.props.showOnlyOnceSlug
      && this.state.announcementsSettings.displayed.includes(this.props.showOnlyOnceSlug)
    ) {
      return null;
    }

    return (
      <InAppAnnouncementDot
        className={this.props.className}
        width={this.props.width}
        height={this.props.height}
        onUserTrigger={() => {
          if (!this.props.showOnlyOnceSlug) { return; }
          this.saveDisplayed();
        }}
      >
        {this.props.children}
      </InAppAnnouncementDot>
    );
  }
}

const validateBooleanWithWindowDependency = (props, propName, componentName, windowProperty) => {
  const propValue = props[propName];
  if (propValue !== null && propValue !== true && propValue !== false) {
    return new Error(`Invalid property in ${componentName}. newUser must be of type boolean`);
  }
  if (propValue !== null && typeof window[windowProperty] === 'undefined') {
    return new Error(
      `Missing data for evaluation of ${componentName} display condition. ${propName} requires window.${windowProperty}`
    );
  }
  return null;
};

InAppAnnouncement.propTypes = {
  width: PropTypes.string,
  height: PropTypes.string,
  className: PropTypes.string,
  children: PropTypes.element.isRequired,
  validUntil: PropTypes.instanceOf(Date),
  showToNewUser: (props, propName, componentName) => (
    validateBooleanWithWindowDependency(props, propName, componentName, 'mailpoet_is_new_user')
  ),
  showToPremiumUser: (props, propName, componentName) => (
    validateBooleanWithWindowDependency(props, propName, componentName, 'mailpoet_premium_active')
  ),
  showOnlyOnceSlug: (props, propName, componentName) => {
    const propValue = props[propName];
    if (propValue !== null && typeof propValue !== 'string') {
      return new Error(`Invalid property in ${componentName}. ${propName} must be of type string`);
    }
    if (propValue === null) {
      return null;
    }
    if (
      typeof window.mailpoet_in_app_announcements === 'undefined'
    ) {
      return new Error(
        `Missing data for evaluation of ${componentName} display condition. ${propName} requires window.mailpoet_in_app_announcements`
      );
    }
    return null;
  },
};

InAppAnnouncement.defaultProps = {
  width: '900px',
  height: '600px',
  className: null,
  validUntil: null,
  showToNewUser: null,
  showToPremiumUser: null,
  showOnlyOnceSlug: null,
};

export default InAppAnnouncement;
