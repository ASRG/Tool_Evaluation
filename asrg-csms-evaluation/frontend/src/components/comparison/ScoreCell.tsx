import ScoreBadge from '@/components/ui/ScoreBadge'
import type { ComputedSubFeatureScore } from '@/lib/types'

interface ScoreCellProps {
  score: ComputedSubFeatureScore | undefined
  onClick?: () => void
}

export default function ScoreCell({ score, onClick }: ScoreCellProps) {
  if (!score) {
    return (
      <td className="px-3 py-2 text-center text-gray-300 text-sm">
        —
      </td>
    )
  }

  return (
    <td className="px-3 py-2 text-center">
      <button
        onClick={onClick}
        className="inline-flex items-center gap-1 group cursor-pointer hover:opacity-80 transition-opacity"
        title="Click to view rationale and feedback"
      >
        <ScoreBadge rating={score.rating} compact />
        {score.rationale && (
          <svg
            className="w-3.5 h-3.5 text-gray-400 group-hover:text-asrg-purple"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2}
          >
            <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        )}
      </button>
    </td>
  )
}
