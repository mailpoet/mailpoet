export type EngagementScoreType =
  | 'unknown'
  | 'dormant'
  | 'low'
  | 'good'
  | 'excellent';

export type EngagementScoreBadgeType =
  | 'unknown'
  | 'dormant'
  | 'average'
  | 'good'
  | 'excellent';

export function getEngagementScoreBadgeType(
  engagementScore?: number | null,
  engagementScoreType?: EngagementScoreType,
): EngagementScoreBadgeType {
  switch (engagementScoreType) {
    case 'unknown':
    case 'dormant':
    case 'good':
    case 'excellent':
      return engagementScoreType;
    case 'low':
      return 'average';
    default:
      break;
  }

  if (engagementScore == null) {
    return 'unknown';
  }
  if (engagementScore < 20) {
    return 'average';
  }
  if (engagementScore < 50) {
    return 'good';
  }
  return 'excellent';
}
