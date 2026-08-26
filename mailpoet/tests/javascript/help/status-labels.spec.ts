import {
  getCronStatusLabelKey,
  getQueueStatusLabelKey,
} from '../../../assets/js/src/help/status-labels';

describe('Help status labels', () => {
  describe('getQueueStatusLabelKey', () => {
    it('reports a paused queue as paused', () => {
      expect(getQueueStatusLabelKey('paused')).to.equal('paused');
    });

    it('reports a queue with no status as running', () => {
      expect(getQueueStatusLabelKey(undefined)).to.equal('running');
      expect(getQueueStatusLabelKey(null)).to.equal('running');
    });
  });

  describe('getCronStatusLabelKey', () => {
    it('reports an active daemon as running', () => {
      expect(getCronStatusLabelKey('active')).to.equal('running');
    });

    it('reports an inactive daemon as waiting for the next run', () => {
      expect(getCronStatusLabelKey('inactive')).to.equal('cronWaiting');
    });

    it('reports a missing daemon record as never started', () => {
      expect(getCronStatusLabelKey(undefined)).to.equal('cronNeverStarted');
      expect(getCronStatusLabelKey(null)).to.equal('cronNeverStarted');
    });
  });
});
