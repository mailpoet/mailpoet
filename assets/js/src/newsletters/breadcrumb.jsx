define(
  [
    'react',
    'react-router',
    'classnames'
  ],
  function(
    React,
    Router,
    classNames
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
              label: MailPoetI18n.selectType,
              link: '/new'
            },
            {
              name: 'template',
              label: MailPoetI18n.template
            },
            {
              name: 'editor',
              label: MailPoetI18n.designer
            },
            {
              name: 'send',
              label: MailPoetI18n.send
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
