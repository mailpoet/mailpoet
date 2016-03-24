define(
  [
    'react',
    'react-router',
    'classnames',
    'mailpoet'
  ],
  function(
    React,
    Router,
    classNames,
    MailPoet
  ) {
    var Link = Router.Link;

    var Breadcrumb = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          step: null,
          steps: [
            {
              name: 'type',
              label: MailPoet.I18n.t('selectType'),
              link: '/new'
            },
            {
              name: 'template',
              label: MailPoet.I18n.t('template')
            },
            {
              name: 'editor',
              label: MailPoet.I18n.t('designer')
            },
            {
              name: 'send',
              label: MailPoet.I18n.t('send')
            }
          ]
        };
      },
      render: function() {
        var steps = this.state.steps.map(function(step, index) {
          var stepClasses = classNames(
            { 'mailpoet_current': (this.props.step === step.name) }
          );

          var label = step.label;

          if(step['link'] !== undefined && this.props.step !== step.name) {
            label = (
              <Link to={ step.link }>{ step.label }</Link>
            );
          }

          return (
            <span key={ 'step-'+index }>
              <span className={ stepClasses }>
                { label }
              </span>
              { (index < (this.state.steps.length - 1) ) ? ' > ' : '' }
            </span>
          );
        }.bind(this));

        return (
          <p className="mailpoet_breadcrumb">
            { steps }
          </p>
        );
      }
    });

    return Breadcrumb;
  }
);
