// trim spaces, commas, periods,
// single/double quotes and convert to lowercase
import _ from 'underscore';

const isRoleEmail = (email) =>
  window.roleBasedEmails.findIndex((element) =>
    email.startsWith(`${element}@`),
  ) >= 0;

const detectAndCleanupEmail = (emailString) => {
  let test;

  let email = _.unescape(emailString);
  email = email
    .toLowerCase()
    // left/right trim spaces, punctuation (e.g., " 'email@email.com'; ")
    // right trim non-printable characters (e.g., "email@email.com�")
    .replace(/^["';.,\s]+|[^\x20-\x7E]+$|["';.,_\s]+$/g, '')
    // remove spaces (e.g., "email @ email . com")
    // remove urlencoded characters
    .replace(/\s+|%\d+|,+/g, '');
  // detect e-mails that will be otherwise rejected by email regex
  test = /<(.*?)>/.exec(email);
  if (test) {
    // is the email inside angle brackets (e.g., 'some@email.com <some@email.com>')?
    email = test[1].trim();
  }
  test = /mailto:(?:\s+)?(.*)/.exec(email);
  if (test) {
    // is the email in 'mailto:email' format?
    email = test[1].trim();
  }

  // validate email
  if (!window.mailpoet_email_regex.test(email)) {
    return false;
  }
  if (isRoleEmail(email)) {
    return false;
  }
  return email;
};

function sanitizeCSVData(csvData) {
  let processedSubscribers = [];
  const parsedEmails = [];
  const duplicateEmails = [];
  const invalidEmails = [];
  const roleEmails = [];
  let emailColumnPosition = null;
  let columnCount = null;
  let isHeaderFound = false;
  let email;
  let emailAddress;
  let rowData;
  let rowColumnCount;
  Object.keys(csvData).forEach((rowCount) => {
    rowData = csvData[rowCount].map((el) => el.trim());
    rowColumnCount = rowData.length;
    // set the number of row elements based on the first non-empty row
    if (columnCount === null) {
      columnCount = rowColumnCount;
    }
    // Process the row with the following assumptions:
    // 1. Each row should contain the same number of elements
    // 2. There should be at least 1 valid (as per HTML5 e-mail regex)
    // e-mail address on each row EXCEPT when the header option is set to true
    // 3. Duplicate addresses are skipped
    if (rowColumnCount === columnCount) {
      // determine position of email address inside an array; this is
      // done once and then email regex is run just on that element for each row
      if (emailColumnPosition === null) {
        Object.keys(rowData).forEach((column) => {
          emailAddress = detectAndCleanupEmail(rowData[column]);
          if (
            emailColumnPosition === null &&
            window.mailpoet_email_regex.test(emailAddress)
          ) {
            emailColumnPosition = column;
            // add current e-mail to an object index
            parsedEmails[emailAddress] = true;
            rowData[column] = emailAddress;
            processedSubscribers[emailAddress] = rowData;
          }
        });
        if (emailColumnPosition === null && parseInt(rowCount, 10) === 0) {
          isHeaderFound = true;
          processedSubscribers[0] = rowData;
        }
      } else if (rowData[emailColumnPosition] !== '') {
        email = detectAndCleanupEmail(rowData[emailColumnPosition]);
        if (_.has(parsedEmails, email)) {
          duplicateEmails.push(email);
        } else if (isRoleEmail(rowData[emailColumnPosition])) {
          roleEmails.push(rowData[emailColumnPosition]);
        } else if (!window.mailpoet_email_regex.test(email)) {
          invalidEmails.push(rowData[emailColumnPosition]);
        } else {
          // if we haven't yet processed this e-mail and it passed
          // the regex test, then process the row
          parsedEmails[email] = true;
          rowData[emailColumnPosition] = email;
          processedSubscribers[email] = rowData;
        }
      }
    }
  });
  // reindex array to avoid non-numeric indices
  processedSubscribers = _.values(processedSubscribers);
  // if the header options is set, there should be at least
  // 2 data rows, otherwise at least 1 data row
  if (
    processedSubscribers &&
    ((isHeaderFound && processedSubscribers.length >= 2) ||
      (!isHeaderFound && processedSubscribers.length >= 1))
  ) {
    // since we assume that the header line is always present, we need
    // to detect the header by checking if it contains a valid e-mail address
    return {
      header: !window.mailpoet_email_regex.test(
        processedSubscribers[0][emailColumnPosition],
      )
        ? processedSubscribers.shift()
        : null,
      subscribers: processedSubscribers,
      subscribersCount: processedSubscribers.length,
      duplicate: _.uniq(duplicateEmails),
      invalid: _.uniq(invalidEmails),
      role: _.uniq(roleEmails),
    };
  }
  return null;
}

export default sanitizeCSVData;
