const FIRST_NAME_HEADERS = ['first', 'first name', 'firstname', 'given name'];
const LAST_NAME_HEADERS = ['last', 'last name', 'lastname'];

const getMatchedNameColumnId = (headerName) => {
  const normalizedHeaderName = String(headerName).trim().toLowerCase();
  if (FIRST_NAME_HEADERS.includes(normalizedHeaderName)) {
    return 'first_name';
  }
  if (LAST_NAME_HEADERS.includes(normalizedHeaderName)) {
    return 'last_name';
  }
  return 'ignore';
};

export const matchColumns = (subscribers, header) => {
  const displayedColumns = [];
  const displayedColumnsIds = [];
  let columnData;
  let columnId;
  let headerName;
  let headerNameMatch;
  // go through all elements of the first row in subscribers data
  Object.keys(subscribers[0]).forEach((i) => {
    columnData = subscribers[0][i];
    columnId = 'ignore'; // set default column type
    // if the column is not undefined and has a valid e-mail, set type as email
    if (columnData % 1 !== 0 && window.mailpoet_email_regex.test(columnData)) {
      columnId = 'email';
    } else if (header) {
      headerName = header[i];
      headerNameMatch = window.mailpoetColumns
        .map((el) => el.name)
        .indexOf(headerName);
      // set column type using header
      if (headerNameMatch !== -1) {
        columnId = window.mailpoetColumns[headerNameMatch].id;
      } else if (headerName) {
        columnId = getMatchedNameColumnId(headerName);
      }
    }
    // make sure the column id has not been previously selected
    // (e.g., subscriber_first_name shouldn't be autodetected twice),
    // except for "ignore"
    columnId =
      columnId !== 'ignore' && displayedColumnsIds.indexOf(columnId) === -1
        ? columnId
        : 'ignore';
    displayedColumns[i] = { column_id: columnId };
    displayedColumnsIds.push(columnId);
  });
  return displayedColumns;
};
