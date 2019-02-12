import React, { Component } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';

const renderServicesMessage = () => {
  let message = ReactStringReplace(MailPoet.I18n.t('useServices'), '%1$s', () => (
    <a
      href="https://www.briteverify.com"
      target="_blank"
      rel="noreferrer noopener"
    >
      BriteVerify
    </a>
  ));
  message = ReactStringReplace(message, '%2$s', () => (
    <a
      href="https://www.datavalidation.com/"
      target="_blank"
      rel="noreferrer noopener"
    >
      DataValidation
    </a>
  ));
  message = ReactStringReplace(message, '%3$s', () => (
    <a
      href="https://neverbounce.com/"
      target="_blank"
      rel="noreferrer noopener"
    >
      NeverBounce
    </a>
  ));
  return message;
}

class StepInputValidation extends Component {

  constructor(props) {
    super(props);
    this.state = {
      subscribersAgreed: false,
      sentOnceLastYear: false,
      understand: false,
    };
  }

  isFormValid() {
    return this.state.subscribersAgreed
      && this.state.sentOnceLastYear
      && this.state.understand;
  }

  renderStepButtons() {
    const nextStepClasses = classNames(
      'button-primary',
      'wysija',
      { 'button-disabled': !this.isFormValid() },
    );
    return (
      <>
        <button
          className="button-primary wysija button"
          type="button"
          onClick={() => this.props.navigate('step_method_selection', { trigger: true })}
        >
          {MailPoet.I18n.t('previousStep')}
        </button>
        &nbsp;&nbsp;
        <button
          type="button"
          className={nextStepClasses}
          onClick={() => {
            if (this.isFormValid()) {
              this.props.navigate('step_data_manipulation', { trigger: true });
            }
          }}
        >
          {MailPoet.I18n.t('nextStep')}
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
          {MailPoet.I18n.t('subscribersAgreed')}
        </label>
        <p>
          {MailPoet.I18n.t('dontEmailSubscribers')}
          {' '}
          <a
            href="https://kb.mailpoet.com/article/127-checklist-before-importing-subscribers"
            target="_blank"
            rel="noreferrer noopener"
          >
            {MailPoet.I18n.t('readSupportArticle')}
          </a>
        </p>
        <label htmlFor="sent_once_last_year">
          <input
            type="checkbox"
            checked={this.state.sentOnceLastYear}
            onChange={e => this.setState({ sentOnceLastYear: e.target.checked })}
            name="sent_once_last_year"
          />
          {MailPoet.I18n.t('sentOnceYear')}
        </label>
        <p>
          {MailPoet.I18n.t('emailAddressesWillBounce')}
        </p>
        <p>
          {renderServicesMessage()}
        </p>
        <label htmlFor="understand">
          <input
            type="checkbox"
            checked={this.state.understand}
            onChange={e => this.setState({ understand: e.target.checked })}
            name="understand"
          />
          {MailPoet.I18n.t('youUnderstand')}
        </label>
        <p>
          {MailPoet.I18n.t('weWillSuspend')}
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
