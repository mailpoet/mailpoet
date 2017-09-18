define(
  [
    'react',
    'react-router',
    'classnames',
    'mailpoet',
  ],
  (
    React,
    Router,
    classNames,
    MailPoet
  ) => {
    const Link = Router.Link;

    const Breadcrumb = React.createClass({
      getInitialState: function () {
        return {
          step: null,
          steps: [
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
          ],
        };
      },
      render: function () {
        const steps = this.state.steps.map((step, index) => {
          const stepClasses = classNames(
            { mailpoet_current: (this.props.step === step.name) }
          );

          let label = step.label;

          if (step['link'] !== undefined && this.props.step !== step.name) {
            label = (
              <Link to={step.link}>{ step.label }</Link>
            );
          }

          return (
            <span key={'step-'+index}>
              <span className={stepClasses}>
                { label }
              </span>
              { (index < (this.state.steps.length - 1) ) ? ' > ' : '' }
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

    return Breadcrumb;
  }
);
