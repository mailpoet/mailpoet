const FIRST_NAME_HEADERS = [
  'first',
  'first name',
  'firstname',
  'given name',
  'fname',
];
const LAST_NAME_HEADERS = [
  'last',
  'last name',
  'lastname',
  'surname',
  'family name',
  'lname',
];

// Collapse common word separators (underscore, hyphen, dot) to a single space
// so that first_name, first-name, first.name and "first name" all normalize to
// the same value. Whole-string matching is preserved, so unrelated headers such
// as first_updated or last_login still fall through to "ignore".
const normalizeHeaderName = (headerName) =>
  String(headerName)
    .trim()
    .toLowerCase()
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replaceAll('.', ' ')
    .split(' ')
    .filter(Boolean)
    .join(' ');

const getMatchedNameColumnId = (headerName) => {
  const normalizedHeaderName = normalizeHeaderName(headerName);
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
