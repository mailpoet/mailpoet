import { format } from 'date-fns';
import { MailPoetDate } from '../../../../assets/js/src/date';
import { momentToDateFnsFormat } from '../../../../assets/js/src/newsletters/send/date-fns-format';

const date = new Date(2026, 9, 8, 14, 5);

const formatWithWpFormat = (wpFormat: string): string =>
  format(date, momentToDateFnsFormat(MailPoetDate.convertFormat(wpFormat)));

describe('momentToDateFnsFormat', () => {
  it('formats common WordPress date formats', () => {
    expect(formatWithWpFormat('F j, Y')).to.equal('October 8, 2026');
    expect(formatWithWpFormat('Y-m-d')).to.equal('2026-10-08');
    expect(formatWithWpFormat('m/d/Y')).to.equal('10/08/2026');
    expect(formatWithWpFormat('d/m/Y')).to.equal('08/10/2026');
    expect(formatWithWpFormat('g:i A')).to.equal('2:05 PM');
  });

  it('keeps escaped letters as literal text', () => {
    expect(formatWithWpFormat('j \\d\\e F \\d\\e Y')).to.equal(
      '8 de October de 2026',
    );
    expect(formatWithWpFormat('j \\o\\f F Y')).to.equal('8 of October 2026');
  });

  it('keeps unknown letters as literal text', () => {
    expect(formatWithWpFormat('Y\\Wq')).to.equal('2026Wq');
  });

  it('escapes literal single quotes', () => {
    expect(formatWithWpFormat("j 'F' Y")).to.equal("8 'October' 2026");
  });

  it('formats the English ordinal suffix', () => {
    expect(formatWithWpFormat('F jS, Y')).to.equal('October 8th, 2026');
    expect(formatWithWpFormat('F \\t\\h\\e jS')).to.equal('October the 8th');
  });

  it('drops the ordinal suffix when date-fns cannot format it', () => {
    expect(formatWithWpFormat('dS F')).to.equal('08 October');
    expect(formatWithWpFormat('S F')).to.equal(' October');
  });
});
