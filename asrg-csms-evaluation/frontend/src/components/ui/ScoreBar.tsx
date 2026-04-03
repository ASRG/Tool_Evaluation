interface ScoreBarProps {
  score: number // 0-100
  label?: string
}

function getScoreStyle(score: number): { bg: string; text: string } {
  if (score >= 75) return { bg: 'bg-green-500/15 dark:bg-green-500/20', text: 'text-green-700 dark:text-green-400' }
  if (score >= 40) return { bg: 'bg-amber-500/15 dark:bg-amber-500/20', text: 'text-amber-700 dark:text-amber-400' }
  return { bg: 'bg-red-500/15 dark:bg-red-500/20', text: 'text-red-700 dark:text-red-400' }
}

export default function ScoreBar({ score, label }: ScoreBarProps) {
  const style = getScoreStyle(score)

  return (
    <div className={`inline-flex items-center justify-center rounded-md px-3 py-1.5 min-w-[56px] ${style.bg}`}>
      <span className={`text-xs font-bold tabular-nums ${style.text}`}>
        {label ?? score.toFixed(1)}
      </span>
    </div>
  )
}
