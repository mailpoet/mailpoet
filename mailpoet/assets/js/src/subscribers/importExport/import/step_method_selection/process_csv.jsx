import Papa from 'papaparse';
import MailPoet from 'mailpoet';
import sanitizeCSVData from '../sanitize_csv_data.jsx';

function papaParserConfig(done) {
  return {
    skipEmptyLines: true,
    error() {
      MailPoet.Notice.hide();
      MailPoet.Notice.error(MailPoet.I18n.t('dataProcessingError'));
    },
    complete(CSV) {
      const sanitizedData = sanitizeCSVData(CSV.data);
      if (sanitizedData) {
        done(sanitizedData);
      } else {
        let errorNotice = MailPoet.I18n.t('noValidRecords');
        errorNotice = errorNotice.replace(
          '[link]',
          '<a href="https://kb.mailpoet.com/article/126-importing-subscribers-with-csv-files" data-beacon-article="57ce079f903360649f6e56fc">',
        );
        errorNotice = errorNotice.replace('[/link]', '</a>');
        MailPoet.Notice.error(errorNotice);
      }
      MailPoet.Modal.loading(false);
    },
  };
}

const process = (csvData, done) => {
  const pasteSize = encodeURI(csvData).split(/%..|./).length - 1;
  MailPoet.Notice.hide();
  // get an approximate size of textarea paste in bytes
  if (pasteSize > window.maxPostSizeBytes) {
    MailPoet.Notice.error(MailPoet.I18n.t('maxPostSizeNotice'));
    return;
  }
  MailPoet.Modal.loading(true);
  Papa.parse(csvData, papaParserConfig(done));
};

export default process;
