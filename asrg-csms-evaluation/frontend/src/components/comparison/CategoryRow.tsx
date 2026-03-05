import { useState } from 'react'
import ScoreBar from '@/components/ui/ScoreBar'
import ScoreCell from './ScoreCell'
import type { CategoryDefinition, ComputedToolScore, ComputedSubFeatureScore } from '@/lib/types'

interface CategoryRowProps {
  category: CategoryDefinition
  toolScores: ComputedToolScore[]
  toolNames: string[]
  onCellClick?: (toolId: number, subFeatureId: string) => void
}

export default function CategoryRow({
  category,
  toolScores,
  toolNames,
  onCellClick,
}: CategoryRowProps) {
  const [expanded, setExpanded] = useState(false)

  const getCategoryScore = (toolScore: ComputedToolScore) => {
    return toolScore.categoryScores.find((cs) => cs.categoryId === category.id)
  }

  const getSubFeatureScore = (
    toolScore: ComputedToolScore,
    subFeatureId: string,
  ): ComputedSubFeatureScore | undefined => {
    const cs = getCategoryScore(toolScore)
    return cs?.subFeatureScores.find((sf) => sf.subFeatureId === subFeatureId)
  }

  return (
    <>
      {/* Category header row */}
      <tr
        className="border-b border-gray-200 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors"
        onClick={() => setExpanded(!expanded)}
      >
        <td className="px-4 py-3 font-bold text-sm text-asrg-black">
          <div className="flex items-center gap-2">
            <svg
              className={`w-4 h-4 text-gray-500 transition-transform duration-200 ${
                expanded ? 'rotate-90' : ''
              }`}
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2}
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span>{category.name}</span>
            {category.weight === 0 && (
              <span className="text-xs text-gray-400 font-normal">(not scored)</span>
            )}
          </div>
        </td>
        {toolScores.map((ts, i) => {
          const cs = getCategoryScore(ts)
          return (
            <td key={ts.toolId} className="px-3 py-3">
              {category.weight > 0 && cs ? (
                <ScoreBar score={cs.communityAdjustedScore} />
              ) : (
                <span className="text-xs text-gray-400">{toolNames[i]}</span>
              )}
            </td>
          )
        })}
      </tr>

      {/* Expanded sub-feature rows */}
      {expanded &&
        category.subFeatures.map((sf) => (
          <tr key={sf.id} className="border-b border-gray-100 hover:bg-blue-50/30">
            <td className="pl-10 pr-4 py-2 text-sm text-gray-700">
              <div>
                <span>{sf.name}</span>
                {sf.isoReference && (
                  <span className="ml-2 text-xs text-asrg-purple">{sf.isoReference}</span>
                )}
              </div>
              <p className="text-xs text-gray-400 mt-0.5 max-w-md">{sf.description}</p>
            </td>
            {toolScores.map((ts) => (
              <ScoreCell
                key={ts.toolId}
                score={getSubFeatureScore(ts, sf.id)}
                onClick={() => onCellClick?.(ts.toolId, sf.id)}
              />
            ))}
          </tr>
        ))}
    </>
  )
}
