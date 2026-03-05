import { useState, useEffect } from 'react'
import ComparisonTable from '@/components/comparison/ComparisonTable'
import { fetchFramework, fetchTools, fetchAllScores } from '@/api/tools'
import { isLoggedIn, getUser, getLoginUrl } from '@/api/client'
import type { EvaluationFramework, Tool, ComputedToolScore } from '@/lib/types'

export default function App() {
  const [framework, setFramework] = useState<EvaluationFramework | null>(null)
  const [tools, setTools] = useState<Tool[]>([])
  const [scores, setScores] = useState<ComputedToolScore[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    Promise.all([fetchFramework(), fetchTools(), fetchAllScores()])
      .then(([fw, t, s]) => {
        setFramework(fw)
        setTools(t)
        setScores(s)
      })
      .catch((err) => {
        console.error('Failed to load data:', err)
        setError('Failed to load evaluation data. Please try again later.')
      })
      .finally(() => setLoading(false))
  }, [])

  const user = getUser()

  return (
    <div className="max-w-wide mx-auto px-4 py-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-bold text-asrg-black">
            CSMS Tool Evaluation
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Community-driven comparison of Cybersecurity Management System tools
            <span className="mx-1">&middot;</span>
            <span className="text-asrg-purple">ISO/SAE 21434</span>
          </p>
        </div>
        <div className="flex items-center gap-3">
          {isLoggedIn() && user ? (
            <div className="flex items-center gap-2">
              <img
                src={user.avatar}
                alt=""
                className="w-8 h-8 rounded-full"
              />
              <div className="text-right">
                <p className="text-sm font-medium text-gray-800">{user.name}</p>
                <p className="text-[10px] text-gray-400 uppercase">{user.role}</p>
              </div>
            </div>
          ) : (
            <a href={getLoginUrl()} className="btn-secondary text-sm">
              Sign In
            </a>
          )}
        </div>
      </div>

      {/* Methodology link */}
      <div className="flex gap-4 mb-6 text-sm">
        <span className="text-gray-400">
          {tools.length} tools evaluated across {framework?.categories.length ?? 0} categories
        </span>
        <a href="#methodology" className="text-asrg-purple hover:underline">
          Scoring Methodology
        </a>
      </div>

      {/* Loading state */}
      {loading && (
        <div className="text-center py-20">
          <div className="inline-block w-8 h-8 border-4 border-gray-200 border-t-asrg-red rounded-full animate-spin" />
          <p className="text-sm text-gray-400 mt-3">Loading evaluation data...</p>
        </div>
      )}

      {/* Error state */}
      {error && (
        <div className="text-center py-20">
          <p className="text-sm text-red-500">{error}</p>
        </div>
      )}

      {/* Comparison table */}
      {!loading && !error && framework && (
        <ComparisonTable
          framework={framework}
          tools={tools}
          scores={scores}
        />
      )}

      {/* Footer */}
      <div className="mt-12 pt-6 border-t border-gray-100 text-center text-xs text-gray-400">
        <p>
          ASRG CSMS Tool Evaluation &middot; Framework v{framework?.version ?? '—'}
          &middot; Last updated {framework?.lastUpdated ?? '—'}
        </p>
        <p className="mt-1">
          Scores are community-influenced. See the{' '}
          <a href="#methodology" className="text-asrg-purple hover:underline">
            methodology
          </a>{' '}
          for details.
        </p>
      </div>
    </div>
  )
}
