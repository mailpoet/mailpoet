import { Component } from 'react';
import { MailPoet } from 'mailpoet';

export class Loading extends Component {
  componentDidMount() {
    MailPoet.Modal.loading(true);
  }

  componentWillUnmount() {
    MailPoet.Modal.loading(false);
  }

  render() {
    return null;
  }
}
