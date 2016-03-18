define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
    'form/fields/select.jsx',
    'form/fields/selection.jsx',
    'form/fields/text.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    _,
    React,
    Router,
    MailPoet,
    Form,
    Select,
    Selection,
    Text,
    Breadcrumb
  ) {

    var intervalField = {
      name: 'interval',
      values: {
        'daily': 'Once a day at...',
        'weekly': 'Weekly on...',
        'monthly': 'Monthly on the...',
        'nthWeekDay': 'Monthly every...',
        'immediately': 'Immediately.',
      },
    };

    var SECONDS_IN_DAY = 86400;
    var TIME_STEP_SECONDS = 3600; // Default: 3600
    var numberOfTimeSteps = SECONDS_IN_DAY / TIME_STEP_SECONDS;
    var timeOfDayValues = _.object(_.map(
      _.times(numberOfTimeSteps, function(step) { return step * TIME_STEP_SECONDS; }),
      function(seconds) {
        var date = new Date(null);
        date.setSeconds(seconds);
        var timeLabel = date.toISOString().substr(11, 5);
        return [seconds, timeLabel];
      }
    ));
    var timeOfDayField = {
      name: 'timeOfDay',
      values: timeOfDayValues,
    };

    var weekDayField = {
      name: 'weekDay',
      values: {
        0: 'Sunday',
        1: 'Monday',
        2: 'Tuesday',
        3: 'Wednesday',
        4: 'Thursday',
        5: 'Friday',
        6: 'Saturday'
      },
    };

    var NUMBER_OF_DAYS_IN_MONTH = 28; // 28 for compatibility with MP2
    var monthDayField = {
      name: 'monthDay',
      values: _.object(_.map(
        _.times(NUMBER_OF_DAYS_IN_MONTH, function(day) { return day; }),
        function(day) {
          var suffixes = {
            0: 'st',
            1: 'nd',
            2: 'rd'
          };
          var suffix = suffixes[day] || 'th';

          return [day, (day + 1).toString() + suffix];
        },
      )),
    };

    var nthWeekDayField = {
      name: 'nthWeekDay',
      values: {
        '1': '1st',
        '2': '2nd',
        '3': '3rd',
        'L': 'last',
      },
    };

    var NewsletterWelcome = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          intervalType: 'immediate', // 'immediate'|'daily'|'weekly'|'monthly'
          timeOfDay: 0,
          weekDay: 1,
          monthDay: 0,
          nthWeekDay: 1,
        };
      },
      handleIntervalChange: function(event) {
        this.setState({
          intervalType: event.target.value,
        });
      },
      handleTimeOfDayChange: function(event) {
        this.setState({
          timeOfDay: event.target.value,
        });
      },
      handleWeekDayChange: function(event) {
        this.setState({
          weekDay: event.target.value,
        });
      },
      handleMonthDayChange: function(event) {
        this.setState({
          monthDay: event.target.value,
        });
      },
      handleNthWeekDayChange: function(event) {
        this.setState({
          nthWeekDay: event.target.value,
        });
      },
      handleNext: function() {
        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: 'notification',
            options: this.state,
          },
        }).done(function(response) {
          if(response.result && response.newsletter.id) {
            this.showTemplateSelection(response.newsletter.id);
          } else {
            if(response.errors.length > 0) {
              response.errors.map(function(error) {
                MailPoet.Notice.error(error);
              });
            }
          }
        }.bind(this));
      },
      showTemplateSelection: function(newsletterId) {
        this.history.pushState(null, `/template/${newsletterId}`);
      },
      render: function() {
        var timeOfDaySelection,
            weekDaySelection,
            monthDaySelection,
            nthWeekDaySelection;

        if (this.state.intervalType !== 'immediately') {
          timeOfDaySelection = (
            <Select
              field={timeOfDayField}
              item={this.state}
              onValueChange={this.handleTimeOfDayChange} />
          );
        }

        if (this.state.intervalType === 'weekly'
            || this.state.intervalType === 'nthWeekDay') {
          weekDaySelection = (
            <Select
              field={weekDayField}
              item={this.state}
              onValueChange={this.handleWeekDayChange} />
          );
        }

        if (this.state.intervalType === 'monthly') {
          monthDaySelection = (
            <Select
              field={monthDayField}
              item={this.state}
              onValueChange={this.handleMonthDayChange} />
          );
        }

        if (this.state.intervalType === 'nthWeekDay') {
          nthWeekDaySelection = (
            <Select
              field={nthWeekDayField}
              item={this.state}
              onValueChange={this.handleNthWeekDayChange} />
          );
        }

        return (
          <div>
            <h1>Post notifications</h1>
            <Breadcrumb step="type" />

            <Select
              field={intervalField}
              item={this.state}
              onValueChange={this.handleIntervalChange} />

            {nthWeekDaySelection}
            {monthDaySelection}
            {weekDaySelection}
            {timeOfDaySelection}

            <p className="submit">
              <input
                className="button button-primary"
                type="button"
                onClick={ this.handleNext }
                value="Next" />
            </p>
          </div>
        );
      },
    });

    return NewsletterWelcome;
  }
);
