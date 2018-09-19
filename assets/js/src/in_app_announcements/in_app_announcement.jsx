import React from 'react';
import InAppAnnouncementDot from './in_app_announcement_dot.jsx';

class InAppAnnouncement extends React.Component {
  render() {
    if (this.props.newUser !== null &&
      window.mailpoet_is_new_user !== this.props.newUser
    ) {
      return null;
    }

    if (this.props.validUntil < (new Date().getTime() / 1000)) {
      return null;
    }

    if (this.props.premiumUser !== null &&
      window.mailpoet_premium_active !== this.props.premiumUser
    ) {
      return null;
    }

    return (
      <InAppAnnouncementDot
        className={this.props.className}
        width={this.props.width}
        height={this.props.height}
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
  width: React.PropTypes.string,
  height: React.PropTypes.string,
  className: React.PropTypes.string,
  children: React.PropTypes.element.isRequired,
  validUntil: React.PropTypes.number,
  newUser: (props, propName, componentName) => (
    validateBooleanWithWindowDependency(props, propName, componentName, 'mailpoet_is_new_user')
  ),
  premiumUser: (props, propName, componentName) => (
    validateBooleanWithWindowDependency(props, propName, componentName, 'mailpoet_premium_active')
  ),
};

InAppAnnouncement.defaultProps = {
  width: '900px',
  height: '600px',
  className: null,
  validUntil: null,
  newUser: null,
  premiumUser: null,
};

module.exports = InAppAnnouncement;
