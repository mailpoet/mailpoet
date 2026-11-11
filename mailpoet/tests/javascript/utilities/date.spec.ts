import moment, { Moment } from 'moment';
import { MailPoetDate } from '../../../assets/js/src/date';

describe('MailPoetDate', () => {
  describe('isInFuture', () => {
    let now: Moment | undefined;
    beforeEach(() => {
      now = moment(Date.now());
    });

    it('Should work correctly for present', () => {
      expect(MailPoetDate.isInFuture(now.toISOString(), now)).to.equal(false);
    });

    it('Should work correctly for future dates', () => {
      const tomorrow = now.clone().add(1, 'days').toISOString();

      expect(
        MailPoetDate.isInFuture(
          now.clone().add(1, 'seconds').toISOString(),
          now,
        ),
        '1 second in future',
      ).to.equal(true);
      expect(MailPoetDate.isInFuture(tomorrow, now.valueOf())).to.equal(true);
    });

    it('Should work correctly for past dates ', () => {
      const yesterday = now.clone().add(-1, 'days');

      expect(
        MailPoetDate.isInFuture(
          now.clone().add(-1, 'seconds').toISOString(),
          now,
        ),
        '1 second in the past',
      ).to.equal(false);
      expect(
        MailPoetDate.isInFuture(yesterday.toISOString(), now),
        'yesterday',
      ).to.equal(false);
    });
  });
});
