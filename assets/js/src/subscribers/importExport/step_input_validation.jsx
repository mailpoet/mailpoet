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
      key="BriteVerify"
    >
      BriteVerify
    </a>
  ));
  message = ReactStringReplace(message, '%2$s', () => (
    <a
      href="https://www.datavalidation.com/"
      target="_blank"
      rel="noreferrer noopener"
      key="DataValidation"
    >
      DataValidation
    </a>
  ));
  message = ReactStringReplace(message, '%3$s', () => (
    <a
      href="https://neverbounce.com/"
      target="_blank"
      rel="noreferrer noopener"
      key="NeverBounce"
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
      <div className="import_step_buttons">
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
      </div>
    );
  }

  render() {
    return (
      <div className="import_validation_step">
        <label htmlFor="subscribers_agreed">
          <input
            type="checkbox"
            checked={this.state.subscribersAgreed}
            onChange={e => this.setState({ subscribersAgreed: e.target.checked })}
            name="subscribers_agreed"
            id="subscribers_agreed"
          />
          {MailPoet.I18n.t('subscribersAgreed')}
        </label>
        <p className="description">
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
            id="sent_once_last_year"
          />
          {MailPoet.I18n.t('sentOnceYear')}
        </label>
        <p className="description">
          {MailPoet.I18n.t('emailAddressesWillBounce')}
        </p>
        <p className="description">
          {renderServicesMessage()}
        </p>
        <label htmlFor="understand">
          <input
            type="checkbox"
            checked={this.state.understand}
            onChange={e => this.setState({ understand: e.target.checked })}
            name="understand"
            id="understand"
          />
          {MailPoet.I18n.t('youUnderstand')}
        </label>
        <p className="description">
          {MailPoet.I18n.t('weWillSuspend')}
        </p>
        {this.renderStepButtons()}
      </div>
    );
  }
}

StepInputValidation.propTypes = {
  navigate: PropTypes.func.isRequired,
};

export default StepInputValidation;
