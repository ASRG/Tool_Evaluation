import { SubFeatureRating, RATING_LABELS } from '@/lib/types'

interface ScoreBadgeProps {
  rating: SubFeatureRating
  compact?: boolean
}

const BADGE_CLASSES: Record<SubFeatureRating, string> = {
  [SubFeatureRating.FULLY_FULFILLS]: 'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-green-500/10 text-green-700 dark:text-green-400',
  [SubFeatureRating.PARTIALLY_FULFILLS]: 'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400',
  [SubFeatureRating.DOES_NOT_FULFILL]: 'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-red-500/10 text-red-700 dark:text-red-400',
}

const COMPACT_LABELS: Record<SubFeatureRating, string> = {
  [SubFeatureRating.FULLY_FULFILLS]: 'FF',
  [SubFeatureRating.PARTIALLY_FULFILLS]: 'PF',
  [SubFeatureRating.DOES_NOT_FULFILL]: 'DNF',
}

export default function ScoreBadge({ rating, compact = false }: ScoreBadgeProps) {
  return (
    <span className={BADGE_CLASSES[rating]} title={RATING_LABELS[rating]}>
      {compact ? COMPACT_LABELS[rating] : RATING_LABELS[rating]}
    </span>
  )
}
