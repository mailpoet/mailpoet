import {
  getQueueActionLabels,
  getScheduledFor,
} from '../../../../assets/js/src/newsletters/listings/queue-labels';

describe('queue action labels', () => {
  it('treats a paused overdue queue as held with a Send now button', () => {
    expect(
      getQueueActionLabels({ status: 'paused', pause_reason: 'overdue' }),
    ).to.deep.equal({
      isHeld: true,
      showPause: false,
      resumeLabel: 'Send now',
    });
  });

  it('keeps Resume for a paused queue without a reason', () => {
    expect(
      getQueueActionLabels({ status: 'paused', pause_reason: null }),
    ).to.deep.equal({ isHeld: false, showPause: true, resumeLabel: 'Resume' });
  });

  it('does not treat a sender domain pause as held', () => {
    expect(
      getQueueActionLabels({ status: 'paused', pause_reason: 'sender_domain' }),
    ).to.include({ isHeld: false, resumeLabel: 'Resume' });
  });

  it('shows Pause for a running queue', () => {
    expect(
      getQueueActionLabels({ status: null, pause_reason: null }).showPause,
    ).to.equal(true);
  });
});

describe('scheduled date for the status column', () => {
  it('uses scheduled_at only for scheduled queues', () => {
    const queue = { status: 'scheduled', scheduled_at: '2030-01-01 10:00:00' };
    expect(getScheduledFor({ sent_at: null, queue })).to.equal(
      '2030-01-01 10:00:00',
    );
    expect(
      getScheduledFor({ sent_at: null, queue: { ...queue, status: null } }),
    ).to.equal(null);
    expect(
      getScheduledFor({ sent_at: null, queue: { ...queue, status: 'paused' } }),
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
