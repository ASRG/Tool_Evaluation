interface ScoreBarProps {
  score: number // 0-100
  label?: string
}

function getScoreColor(score: number): string {
  if (score >= 75) return 'bg-score-fully'
  if (score >= 40) return 'bg-score-partially'
  return 'bg-score-does-not'
}

export default function ScoreBar({ score, label }: ScoreBarProps) {
  const width = Math.max(0, Math.min(100, score))
  const color = getScoreColor(score)

  return (
    <div className="flex items-center gap-2 min-w-[120px]">
      <div className="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
        <div
          className={`h-full rounded-full transition-all duration-300 ${color}`}
          style={{ width: `${width}%` }}
        />
      </div>
      <span className="text-xs font-bold text-gray-600 w-10 text-right tabular-nums">
        {label ?? score.toFixed(1)}
      </span>
    </div>
  )
}
