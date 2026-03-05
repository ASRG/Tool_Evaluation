import { useState, useMemo } from 'react'
import CategoryRow from './CategoryRow'
import FeedbackPanel from '@/components/feedback/FeedbackPanel'
import type { EvaluationFramework, Tool, ComputedToolScore } from '@/lib/types'

interface ComparisonTableProps {
  framework: EvaluationFramework
  tools: Tool[]
  scores: ComputedToolScore[]
}

type SortField = 'name' | 'overall' | string // string for category IDs

export default function ComparisonTable({ framework, tools, scores }: ComparisonTableProps) {
  const [sortField, setSortField] = useState<SortField>('overall')
  const [sortDesc, setSortDesc] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [selectedCell, setSelectedCell] = useState<{
    toolId: number
    subFeatureId: string
  } | null>(null)

  // Filter tools by search.
  const filteredTools = useMemo(() => {
    if (!searchQuery.trim()) return tools
    const q = searchQuery.toLowerCase()
    return tools.filter(
      (t) =>
        t.name.toLowerCase().includes(q) || t.vendor.toLowerCase().includes(q),
    )
  }, [tools, searchQuery])

  // Sort tools by selected field.
  const sortedToolIds = useMemo(() => {
    const toolIds = filteredTools.map((t) => t.id)

    return toolIds.sort((a, b) => {
      const scoreA = scores.find((s) => s.toolId === a)
      const scoreB = scores.find((s) => s.toolId === b)

      let valA = 0
      let valB = 0

      if (sortField === 'name') {
        const nameA = filteredTools.find((t) => t.id === a)?.name ?? ''
        const nameB = filteredTools.find((t) => t.id === b)?.name ?? ''
        return sortDesc ? nameB.localeCompare(nameA) : nameA.localeCompare(nameB)
      }

      if (sortField === 'overall') {
        valA = scoreA?.overallScore ?? 0
        valB = scoreB?.overallScore ?? 0
      } else {
        // Sort by category score.
        valA =
          scoreA?.categoryScores.find((cs) => cs.categoryId === sortField)
            ?.communityAdjustedScore ?? 0
        valB =
          scoreB?.categoryScores.find((cs) => cs.categoryId === sortField)
            ?.communityAdjustedScore ?? 0
      }

      return sortDesc ? valB - valA : valA - valB
    })
  }, [filteredTools, scores, sortField, sortDesc])

  const sortedScores = sortedToolIds
    .map((id) => scores.find((s) => s.toolId === id))
    .filter((s): s is ComputedToolScore => !!s)

  const sortedToolNames = sortedToolIds
    .map((id) => filteredTools.find((t) => t.id === id)?.name ?? '')

  const handleSort = (field: SortField) => {
    if (field === sortField) {
      setSortDesc(!sortDesc)
    } else {
      setSortField(field)
      setSortDesc(true)
    }
  }

  const handleCellClick = (toolId: number, subFeatureId: string) => {
    setSelectedCell({ toolId, subFeatureId })
  }

  const selectedTool = selectedCell
    ? filteredTools.find((t) => t.id === selectedCell.toolId)
    : null

  const selectedScore = selectedCell
    ? sortedScores
        .find((s) => s.toolId === selectedCell.toolId)
        ?.categoryScores.flatMap((cs) => cs.subFeatureScores)
        .find((sf) => sf.subFeatureId === selectedCell.subFeatureId)
    : null

  return (
    <div className="relative">
      {/* Controls */}
      <div className="flex flex-wrap items-center gap-3 mb-6">
        <input
          type="text"
          placeholder="Search tools..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="border border-gray-200 rounded px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-asrg-purple/50"
        />

        <select
          value={sortField}
          onChange={(e) => handleSort(e.target.value)}
          className="border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-asrg-purple/50"
        >
          <option value="overall">Sort: Overall Score</option>
          <option value="name">Sort: Name</option>
          {framework.categories
            .filter((c) => c.weight > 0)
            .map((c) => (
              <option key={c.id} value={c.id}>
                Sort: {c.name}
              </option>
            ))}
        </select>

        <button
          onClick={() => setSortDesc(!sortDesc)}
          className="border border-gray-200 rounded px-3 py-2 text-sm hover:bg-gray-50"
          title={sortDesc ? 'Descending' : 'Ascending'}
        >
          {sortDesc ? '↓' : '↑'}
        </button>
      </div>

      {/* Table */}
      <div className="overflow-x-auto rounded-lg border border-gray-200">
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-asrg-dark text-white">
              <th className="px-4 py-3 text-left text-sm font-bold w-72">
                Category / Sub-Feature
              </th>
              {sortedToolIds.map((id) => {
                const tool = filteredTools.find((t) => t.id === id)
                const score = scores.find((s) => s.toolId === id)
                return (
                  <th key={id} className="px-3 py-3 text-center min-w-[160px]">
                    <div className="flex flex-col items-center gap-1">
                      {tool?.logo_url && (
                        <img
                          src={tool.logo_url}
                          alt={tool.name}
                          className="h-6 w-auto object-contain"
                        />
                      )}
                      <span className="text-sm font-bold">{tool?.name}</span>
                      {tool?.isSponsor && (
                        <span className="text-[10px] bg-asrg-purple/20 text-asrg-purple px-1.5 rounded">
                          ASRG Sponsor
                        </span>
                      )}
                      <span className="text-xs font-normal opacity-75">
                        Overall: {score?.overallScore.toFixed(1) ?? '—'}
                      </span>
                    </div>
                  </th>
                )
              })}
            </tr>
          </thead>
          <tbody>
            {framework.categories.map((category) => (
              <CategoryRow
                key={category.id}
                category={category}
                toolScores={sortedScores}
                toolNames={sortedToolNames}
                onCellClick={handleCellClick}
              />
            ))}
          </tbody>
        </table>
      </div>

      {/* Empty state */}
      {filteredTools.length === 0 && (
        <div className="text-center py-12 text-gray-400">
          No tools match your search.
        </div>
      )}

      {/* Feedback slide-out panel */}
      {selectedCell && selectedTool && selectedScore && (
        <FeedbackPanel
          toolId={selectedCell.toolId}
          toolName={selectedTool.name}
          subFeatureId={selectedCell.subFeatureId}
          subFeatureName={
            framework.categories
              .flatMap((c) => c.subFeatures)
              .find((sf) => sf.id === selectedCell.subFeatureId)?.name ?? ''
          }
          score={selectedScore}
          onClose={() => setSelectedCell(null)}
        />
      )}
    </div>
  )
}
