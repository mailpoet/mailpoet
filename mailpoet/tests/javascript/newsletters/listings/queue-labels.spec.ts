import {
  getQueueActionLabels,
  getScheduledFor,
  isHeldQueue,
} from '../../../../assets/js/src/newsletters/listings/queue-labels';

describe('queue action labels', () => {
  it('treats a paused overdue queue as held with a Send now button', () => {
    expect(
      getQueueActionLabels({ status: 'paused', pause_reason: 'overdue' }),
    ).to.deep.equal({
      isHeld: true,
      resumeLabel: 'Send now',
    });
  });

  it('keeps Resume for a paused queue without a reason', () => {
    expect(
      getQueueActionLabels({ status: 'paused', pause_reason: null }),
    ).to.deep.equal({ isHeld: false, resumeLabel: 'Resume' });
  });

  it('does not treat a sender domain pause as held', () => {
    expect(
      getQueueActionLabels({ status: 'paused', pause_reason: 'sender_domain' }),
    ).to.include({ isHeld: false, resumeLabel: 'Resume' });
  });
});

describe('held state', () => {
  it('is not held again after the user resumed the row', () => {
    expect(
      getQueueActionLabels({
        status: 'paused',
        pause_reason: 'overdue',
        resumed: true,
      }),
    ).to.deep.equal({ isHeld: false, resumeLabel: 'Resume' });
  });

  it('shows the Held tag for an overdue paused queue even if sending is paused', () => {
    expect(isHeldQueue({ status: 'paused', pause_reason: 'overdue' })).to.equal(
      true,
    );
    expect(isHeldQueue({ status: 'paused', pause_reason: null })).to.equal(
      false,
    );
    expect(
      isHeldQueue({ status: 'scheduled', pause_reason: 'overdue' }),
    ).to.equal(false);
  });
});

describe('scheduled date for the status column', () => {
  const queue = { status: 'scheduled', scheduled_at: '2030-01-01 10:00:00' };

  it('keeps the planned date for scheduled and paused queues', () => {
    expect(getScheduledFor({ sent_at: null, queue })).to.equal(
      '2030-01-01 10:00:00',
    );
    expect(
      getScheduledFor({ sent_at: null, queue: { ...queue, status: 'paused' } }),
    ).to.equal('2030-01-01 10:00:00');
  });

  it('drops the date stamped by an immediate send on a running queue', () => {
    expect(
      getScheduledFor({ sent_at: null, queue: { ...queue, status: null } }),
    ).to.equal(null);
    expect(
      getScheduledFor({
        sent_at: null,
        queue: { ...queue, status: 'running' },
      }),
    ).to.equal(null);
  });

  it('prefers sent_at', () => {
    expect(
      getScheduledFor({
        sent_at: '2030-01-01 09:00:00',
        queue: { status: 'scheduled', scheduled_at: '2030-01-01 10:00:00' },
      }),
    ).to.equal('2030-01-01 09:00:00');
  });
});
