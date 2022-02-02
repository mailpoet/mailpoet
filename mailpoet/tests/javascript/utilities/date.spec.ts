import { expect } from 'chai';
import moment from 'moment';
import { MailPoetDate } from '../../../assets/js/src/date';

describe('MailPoetDate', () => {
  describe('isInFuture', () => {
    let now;
    beforeEach(() => {
      now = moment(Date.now());
    });

    it('Should work correctly for present', () => {
      expect(MailPoetDate.isInFuture(now.toISOString())).to.be.false;
    });

    it('Should work correctly for future dates', () => {
      const tomorrow = moment().add(1, 'days').toISOString();

      expect(MailPoetDate.isInFuture(now.add(1, 'seconds').toISOString())).to.be.true;
      expect(MailPoetDate.isInFuture(tomorrow)).to.be.true;
    });

    it('Should work correctly for past dates ', () => {
      const yesterday = now.add(-1, 'days').toISOString();

      expect(MailPoetDate.isInFuture(now.add(-1, 'seconds').toISOString())).to.be.false;
      expect(MailPoetDate.isInFuture(yesterday)).to.be.false;
    });
  });
});
