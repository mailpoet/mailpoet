import React from 'react';
import MailPoet from 'mailpoet';

class Loading extends React.Component {
  componentWillMount() {
    MailPoet.Modal.loading(true);
  }
  componentWillUnmount() {
    MailPoet.Modal.loading(false);
  }
  render() {
    return null;
  }
}

export default Loading;
