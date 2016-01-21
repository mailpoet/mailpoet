define([
  'react',
  'moment',
], function(
  React,
  Moment
) {
  class FormFieldDateYear extends React.Component {
    render() {
      const years = [];
      const currentYear = Moment().year();
      for (let i = currentYear; i >= currentYear - 100; i--) {
        years.push((
          <option
            key={ i }
            value={ i }
         >{ i }</option>
        ));
      }
      return (
        <select
          name={ this.props.name + '[year]' }
          value={ this.props.year }
          onChange={ this.props.onValueChange }
        >
          { years }
        </select>
      );
    }
  }

  class FormFieldDateMonth extends React.Component {
    render() {
      const months = [];
      for (let i = 1; i <= 12; i++) {
        months.push((
          <option
            key={ i }
            value={ i }
         >{ this.props.monthNames[i - 1] }</option>
        ));
      }
      return (
        <select
          name={ this.props.name + '[month]' }
          value={ this.props.month }
          onChange={ this.props.onValueChange }
        >
          { months }
        </select>
      );
    }
  }

  class FormFieldDateDay extends React.Component {
    render() {
      const days = [];
      for (let i = 1; i <= 31; i++) {
        days.push((
          <option
            key={ i }
            value={ i }
          >{ i }</option>
        ));
      }

      return (
        <select
          name={ this.props.name + '[day]' }
          value={ this.props.day }
          onChange={ this.props.onValueChange }
        >
          { days }
        </select>
      );
    }
  }

  class FormFieldDate extends React.Component {
    constructor(props) {
      super(props);
      this.state = {
        year: Moment().year(),
        month: 1,
        day: 1
      }
    }
    componentDidMount() {
    }
    componentDidUpdate(prevProps, prevState) {
      if(
        (this.props.item !== undefined && prevProps.item !== undefined)
        && (this.props.item.id !== prevProps.item.id)
      ) {
        this.extractTimeStamp();
      }
    }
    extractTimeStamp() {
      const timeStamp = parseInt(this.props.item[this.props.field.name], 10);

      this.setState({
        year: Moment.unix(timeStamp).year(),
        month: Moment.unix(timeStamp).month() + 1,
        day: Moment.unix(timeStamp).date()
      });
    }
    onValueChange(e) {
      // extract property from name
      const matches = e.target.name.match(/.*?\[(.*?)\]/);
      let property = null;
      if(matches !== null && matches.length === 2) {
        property = matches[1];
      }
      let value = parseInt(e.target.value, 10);

      switch(property) {
        case 'year':
          this.setState({ year: value });
        break;

        case 'month':
          this.setState({ month: value });
        break;

        case 'day':
          this.setState({ day: value });
        break;
      }
    }
    render() {
      const monthNames = window.mailpoet_month_names || [];

      const dateType = this.props.field.params.date_type;

      const dateSelects = dateType.split('_');

      const fields = dateSelects.map(type => {
        switch(type) {
          case 'year':
            return (<FormFieldDateYear
              onValueChange={ this.onValueChange.bind(this) }
              ref={ 'year' }
              key={ 'year' }
              name={ this.props.field.name }
              year={ this.state.year }
            />);
          break;

          case 'month':
            return (<FormFieldDateMonth
              onValueChange={ this.onValueChange.bind(this) }
              ref={ 'month' }
              key={ 'month' }
              name={ this.props.field.name }
              month={ this.state.month }
              monthNames={ monthNames }
            />);
          break;

          case 'day':
            return (<FormFieldDateDay
              onValueChange={ this.onValueChange.bind(this) }
              ref={ 'day' }
              key={ 'day' }
              name={ this.props.field.name }
              day={ this.state.day }
            />);
          break;
        }
       });

      return (
        <div>
          {fields}
        </div>
      );
    }
  };

  return FormFieldDate;
});