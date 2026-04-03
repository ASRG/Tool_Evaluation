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
        className="border-b border-border bg-muted/50 cursor-pointer hover:bg-muted transition-colors"
        onClick={() => setExpanded(!expanded)}
      >
        <td className="sticky left-0 z-20 bg-muted dark:bg-[#1a1f2e] px-4 py-3 font-bold text-sm text-foreground border-r border-border min-w-[220px] max-w-[220px]">
          <div className="flex items-center gap-2">
            <svg
              className={`w-4 h-4 text-muted-foreground transition-transform duration-200 ${
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
              <span className="text-xs text-muted-foreground font-normal">(not scored)</span>
            )}
          </div>
        </td>
        {toolScores.map((ts, i) => {
          const cs = getCategoryScore(ts)
          return (
            <td key={ts.toolId} className="px-2 py-3 text-center">
              {category.weight > 0 && cs ? (
                <ScoreBar score={cs.communityAdjustedScore} />
              ) : (
                <span className="text-xs text-muted-foreground">{toolNames[i]}</span>
              )}
            </td>
          )
        })}
      </tr>

      {/* Expanded sub-feature rows */}
      {expanded &&
        category.subFeatures.map((sf) => (
          <tr key={sf.id} className="border-b border-border hover:bg-accent/30">
            <td className="sticky left-0 z-20 bg-card dark:bg-[#151923] pl-10 pr-4 py-2 text-sm text-foreground border-r border-border min-w-[220px] max-w-[220px]">
              <div>
                <span>{sf.name}</span>
                {sf.isoReference && (
                  <span className="ml-2 text-xs text-[var(--asrg-blue)]">{sf.isoReference}</span>
                )}
              </div>
              <p className="text-xs text-muted-foreground mt-0.5 max-w-md">{sf.description}</p>
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
