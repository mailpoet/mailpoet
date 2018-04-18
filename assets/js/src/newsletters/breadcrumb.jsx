import React from 'react';
import classNames from 'classnames';
import { Link } from 'react-router';
import MailPoet from 'mailpoet';

const Breadcrumb = React.createClass({
  getInitialState: function getInitialState() {
    const steps = this.props.steps || [
      {
        name: 'type',
        label: MailPoet.I18n.t('selectType'),
        link: '/new',
      },
      {
        name: 'template',
        label: MailPoet.I18n.t('template'),
      },
      {
        name: 'editor',
        label: MailPoet.I18n.t('designer'),
      },
      {
        name: 'send',
        label: MailPoet.I18n.t('send'),
      },
    ];
    return {
      step: null,
      steps,
    };
  },
  render: function render() {
    const steps = this.state.steps.map((step, index) => {
      const stepClasses = classNames(
        { mailpoet_current: (this.props.step === step.name) }
      );

      let label = step.label;

      if (step.link !== undefined && this.props.step !== step.name) {
        label = (
          <Link to={step.link}>{ step.label }</Link>
        );
      }

      return (
        <span key={`step-${step.label}`}>
          <span className={stepClasses}>
            { label }
          </span>
          { (index < (this.state.steps.length - 1)) ? ' > ' : '' }
        </span>
      );
    });

    return (
      <p className="mailpoet_breadcrumb">
        { steps }
      </p>
    );
  },
});

module.exports = Breadcrumb;

