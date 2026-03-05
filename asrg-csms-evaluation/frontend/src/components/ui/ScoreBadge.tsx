import { SubFeatureRating, RATING_LABELS } from '@/lib/types'

interface ScoreBadgeProps {
  rating: SubFeatureRating
  compact?: boolean
}

const BADGE_CLASSES: Record<SubFeatureRating, string> = {
  [SubFeatureRating.FULLY_FULFILLS]: 'score-badge-fully',
  [SubFeatureRating.PARTIALLY_FULFILLS]: 'score-badge-partially',
  [SubFeatureRating.DOES_NOT_FULFILL]: 'score-badge-does-not',
}

const COMPACT_LABELS: Record<SubFeatureRating, string> = {
  [SubFeatureRating.FULLY_FULFILLS]: 'FF',
  [SubFeatureRating.PARTIALLY_FULFILLS]: 'PF',
  [SubFeatureRating.DOES_NOT_FULFILL]: 'DNF',
}

export default function ScoreBadge({ rating, compact = false }: ScoreBadgeProps) {
  return (
    <span
      className={BADGE_CLASSES[rating]}
      title={RATING_LABELS[rating]}
    >
      {compact ? COMPACT_LABELS[rating] : RATING_LABELS[rating]}
    </span>
  )
}
