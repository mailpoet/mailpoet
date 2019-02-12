import React, { Component } from 'react';
import PropTypes from 'prop-types';

class StepInputValidation extends Component {

  constructor(props) {
    super(props);
    this.state = {
      subscribersAgreed: false,
      sentOnceLastYear: false,
      understand: false,
    };
  }


  renderStepButtons() {
    return (
      <>
        <button
          className="button-primary wysija button"
          type="button"
          onClick={() => {
            console.log('previous step');
            this.props.navigate('step_method_selection', {trigger: true});
          }}
        >
          Previous step
        </button>
        &nbsp;&nbsp;
        <button
          type="button"
          className="button-primary wysija button-disabled"
          onClick={() => {
            // TODO only if all checkboxes are checked
            console.log('next step');
            this.props.navigate('step_data_manipulation', {trigger: true});
          }}
        >
          Next step
        </button>
      </>
    );
  }

  render() {
    return (
      <>
        <label htmlFor="subscribers_agreed">
          <input
            type="checkbox"
            checked={this.state.subscribersAgreed}
            onChange={e => this.setState({ subscribersAgreed: e.target.checked })}
            name="subscribers_agreed"
          />
          The subscribers on this list agreed to receive your emails
        </label>
        <p>
          Don’t email subscribers who didn’t signup to your list.
          If you do, consider yourself a spammer. Read the support article.
        </p>
        <label htmlFor="sent_once_last_year">
          <input
            type="checkbox"
            checked={this.state.sentOnceLastYear}
            onChange={e => this.setState({ sentOnceLastYear: e.target.checked })}
            name="sent_once_last_year"
          />
          You have sent to this list at least once in the last year
        </label>
        <p>
          Email addresses that no longer exist will bounce.
          Spam filters will be suspicious if 5% of your list bounces.
        </p>
        <p>
          Use services like BriteVerify, DataValidation or NeverBounce
          to clean your lists before sending with MailPoet.
        </p>
        <label htmlFor="understand">
          <input
            type="checkbox"
            checked={this.state.understand}
            onChange={e => this.setState({ understand: e.target.checked })}
            name="understand"
          />
          You understand the risk of not respecting the above
        </label>
        <p>
          If you send with the MailPoet Sending Service, we will automatically
          suspend your account if our systems detect bad behavior.
        </p>
        {this.renderStepButtons()}
      </>
    );
  }
}

StepInputValidation.propTypes = {
  navigate: PropTypes.func.isRequired,
};

export default StepInputValidation;
