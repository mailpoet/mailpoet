import React from 'react';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

class Breadcrumb extends React.Component {
  constructor(props) {
    super(props);
    const steps = props.steps || [
      {
        name: 'type',
        label: MailPoet.I18n.t('selectType'),
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

    this.state = {
      steps,
    };
  }

  render() {
    const steps = this.state.steps.map((step, index) => {
      const stepClasses = classNames(
        { mailpoet_current: (this.props.step === step.name) }
      );

      return (
        <span key={`step-${step.label}`}>
          <span className={stepClasses}>
            { step.label }
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
  }
}

Breadcrumb.propTypes = {
  steps: PropTypes.arrayOf(PropTypes.object),
  step: PropTypes.string,
};

Breadcrumb.defaultProps = {
  steps: undefined,
  step: null,
};

export default Breadcrumb;
