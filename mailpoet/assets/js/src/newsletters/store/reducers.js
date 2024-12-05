import { ACTION_TYPES as types } from './action-types';
import { defaultNewsletterState } from './default-state';
import { NEWSLETTER_STANDARD_HEADERS } from './constants';

function makeNewsletterStandardRows(newsletter_data, columns = NEWSLETTER_STANDARD_HEADERS) {
    return newsletter_data.filter((item) => item.type == 'standard').map((item) => {
		return columns
		.filter((column) => column.display !== false)
		.map((column) => {
			let value;
			let display;

			switch (column.key) {
			case 'name':
			case 'subject':
				value = item[column.key];
				display = value || '';
				break;
			case 'status':
				value = item.status;
				display = value || '';
				break;
			case 'segments':
				value = item.segments;
				display = value && value.length ? value.map((s) => s.name).join(', ') : '';
				break;
			case 'statistics':
				value = item.statistics;
				display = `Clicked: ${value.clicked}, Opened: ${value.opened}`;
				break;
			case 'sent_at':
				value = item.sent_at;
				display = value ? new Date(value).toLocaleString() : '';
				break;
			default:
				value = item[column.key];
				display = value || '';
			}

			return { display, value };
		});
  });
}	

const reducer = (state = defaultNewsletterState, action) => {
  switch (action.type) {
    case types.RECEIVE_NEWSLETTERS:
      if (action.response) {
		const newsletterStandardRows = makeNewsletterStandardRows(action.response.data);
		if (state.newsletterStandardRows === newsletterStandardRows) {
		  return state; // No change in data
		} else {	
		return {
		...state,
		newsletterStandardRows: newsletterStandardRows,
		};
		}
      }
      break;
    case types.RECEIVE_ERROR:
      return {
        ...state,
        errors: [...state.errors, action.error],
        isLoading: false,
      };
    case types.FETCH_NEWSLETTERS_REQUEST:
      return {
        ...state,
        isLoading: true,
      };
    case types.FETCH_NEWSLETTERS_SUCCESS:
    case types.FETCH_NEWSLETTERS_FAILURE:
      return {
        ...state,
        isLoading: false,
      };
    default:
      break;
  }
  return state;
};

export default reducer;