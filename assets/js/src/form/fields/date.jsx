define([
  'react',
  'moment',
], (
  React,
  Moment
) => {
  function FormFieldDateYear(props) {
    const yearsRange = 100;
    const years = [];

    if (props.placeholder !== undefined) {
      years.push((
        <option value="" key={0}>{ props.placeholder }</option>
      ));
    }

    const currentYear = Moment().year();
    for (let i = currentYear; i >= currentYear - yearsRange; i -= 1) {
      years.push((
        <option
          key={i}
          value={i}
        >{ i }</option>
      ));
    }
    return (
      <select
        name={`${props.name}[year]`}
        value={props.year}
        onChange={props.onValueChange}
      >
        { years }
      </select>
    );
  }

  function FormFieldDateMonth(props) {
    const months = [];

    if (props.placeholder !== undefined) {
      months.push((
        <option value="" key={0}>{ props.placeholder }</option>
      ));
    }

    for (let i = 1; i <= 12; i += 1) {
      months.push((
        <option
          key={i}
          value={i}
        >{ props.monthNames[i - 1] }</option>
      ));
    }
    return (
      <select
        name={`${props.name}[month]`}
        value={props.month}
        onChange={props.onValueChange}
      >
        { months }
      </select>
    );
  }

  function FormFieldDateDay(props) {
    const days = [];

    if (props.placeholder !== undefined) {
      days.push((
        <option value="" key={0}>{ props.placeholder }</option>
      ));
    }

    for (let i = 1; i <= 31; i += 1) {
      days.push((
        <option
          key={i}
          value={i}
        >{ i }</option>
      ));
    }

    return (
      <select
        name={`${props.name}[day]`}
        value={props.day}
        onChange={props.onValueChange}
      >
        { days }
      </select>
    );
  }

  class FormFieldDate extends React.Component {
    constructor(props) {
      super(props);
      this.state = {
        year: '',
        month: '',
        day: '',
      };
    }
    componentDidMount() {
      this.extractDateParts();
    }
    componentDidUpdate(prevProps) {
      if (
        (this.props.item !== undefined && prevProps.item !== undefined)
        && (this.props.item.id !== prevProps.item.id)
      ) {
        this.extractDateParts();
      }
    }
    extractDateParts() {
      const value = (this.props.item[this.props.field.name] !== undefined)
        ? this.props.item[this.props.field.name].trim()
        : '';

      if (value === '') {
        return;
      }

      const dateTime = Moment(value);

      this.setState({
        year: dateTime.format('YYYY'),
        month: dateTime.format('M'),
        day: dateTime.format('D'),
      });
    }
    formatValue() {
      const dateType = this.props.field.params.date_type;

      let value;

      switch (dateType) {
        case 'year_month_day':
          value = {
            year: this.state.year,
            month: this.state.month,
            day: this.state.day,
          };
          break;

        case 'year_month':
          value = {
            year: this.state.year,
            month: this.state.month,
          };
          break;

        case 'month':
          value = {
            month: this.state.month,
          };
          break;

        case 'year':
          value = {
            year: this.state.year,
          };
          break;
        default:
          value = {
            value: 'invalid type',
          };
          break;
      }

      return value;
    }
    onValueChange(e) {
      // extract property from name
      const matches = e.target.name.match(/(.*?)\[(.*?)\]/);
      let field = null;
      let property = null;

      if (matches !== null && matches.length === 3) {
        field = matches[1];
        property = matches[2];

        const value = Number(e.target.value);

        this.setState({
          [`${property}`]: value,
        }, () => {
          this.props.onValueChange({
            target: {
              name: field,
              value: this.formatValue(),
            },
          });
        });
      }
    }
    render() {
      const monthNames = window.mailpoet_month_names || [];
      const dateFormats = window.mailpoet_date_formats || {};
      const dateType = this.props.field.params.date_type;
      const dateSelects = dateFormats[dateType][0].split('/');

      const fields = dateSelects.map((type) => {
        switch (type) {
          case 'YYYY':
            return (<FormFieldDateYear
              onValueChange={this.onValueChange.bind(this)}
              key={'year'}
              name={this.props.field.name}
              year={this.state.year}
              placeholder={this.props.field.year_placeholder}
            />);

          case 'MM':
            return (<FormFieldDateMonth
              onValueChange={this.onValueChange.bind(this)}
              key={'month'}
              name={this.props.field.name}
              month={this.state.month}
              monthNames={monthNames}
              placeholder={this.props.field.month_placeholder}
            />);

          case 'DD':
            return (<FormFieldDateDay
              onValueChange={this.onValueChange.bind(this)}
              key={'day'}
              name={this.props.field.name}
              day={this.state.day}
              placeholder={this.props.field.day_placeholder}
            />);

          default:
            return <div>Invalid date type</div>;
        }
      });

      return (
        <div>
          {fields}
        </div>
      );
    }
  }

  return FormFieldDate;
});
