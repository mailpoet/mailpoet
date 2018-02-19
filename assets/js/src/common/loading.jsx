import React from 'react';

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