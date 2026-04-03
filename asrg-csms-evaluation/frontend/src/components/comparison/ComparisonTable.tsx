import { useState, useMemo } from 'react'
import CategoryRow from './CategoryRow'
import FeedbackPanel from '@/components/feedback/FeedbackPanel'
import type { EvaluationFramework, Tool, ComputedToolScore } from '@/lib/types'
import { cn } from '@/lib/utils'

interface ComparisonTableProps {
  framework: EvaluationFramework
  tools: Tool[]
  scores: ComputedToolScore[]
}

type SortField = 'name' | 'overall' | string

export default function ComparisonTable({ framework, tools, scores }: ComparisonTableProps) {
  const [sortField, setSortField] = useState<SortField>('overall')
  const [sortDesc, setSortDesc] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [selectedCell, setSelectedCell] = useState<{ toolId: number; subFeatureId: string } | null>(null)
  const [hiddenTools, setHiddenTools] = useState<Set<number>>(new Set())
  const [showToolSelector, setShowToolSelector] = useState(false)

  const filteredTools = useMemo(() => {
    let result = tools
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase()
      result = result.filter((t) => t.name.toLowerCase().includes(q) || t.vendor.toLowerCase().includes(q))
    }
    return result.filter((t) => !hiddenTools.has(t.id))
  }, [tools, searchQuery, hiddenTools])

  const sortedToolIds = useMemo(() => {
    const toolIds = filteredTools.map((t) => t.id)
    return toolIds.sort((a, b) => {
      const scoreA = scores.find((s) => s.toolId === a)
      const scoreB = scores.find((s) => s.toolId === b)
      if (sortField === 'name') {
        const nameA = filteredTools.find((t) => t.id === a)?.name ?? ''
        const nameB = filteredTools.find((t) => t.id === b)?.name ?? ''
        return sortDesc ? nameB.localeCompare(nameA) : nameA.localeCompare(nameB)
      }
      let valA = 0, valB = 0
      if (sortField === 'overall') {
        valA = scoreA?.overallScore ?? 0; valB = scoreB?.overallScore ?? 0
      } else {
        valA = scoreA?.categoryScores.find((cs) => cs.categoryId === sortField)?.communityAdjustedScore ?? 0
        valB = scoreB?.categoryScores.find((cs) => cs.categoryId === sortField)?.communityAdjustedScore ?? 0
      }
      return sortDesc ? valB - valA : valA - valB
    })
  }, [filteredTools, scores, sortField, sortDesc])

  const sortedScores = sortedToolIds.map((id) => scores.find((s) => s.toolId === id)).filter((s): s is ComputedToolScore => !!s)
  const sortedToolNames = sortedToolIds.map((id) => filteredTools.find((t) => t.id === id)?.name ?? '')

  const handleSort = (field: SortField) => {
    if (field === sortField) setSortDesc(!sortDesc)
    else { setSortField(field); setSortDesc(true) }
  }

  const toggleTool = (toolId: number) => {
    setHiddenTools((prev) => {
      const next = new Set(prev)
      if (next.has(toolId)) next.delete(toolId); else next.add(toolId)
      return next
    })
  }

  const selectedTool = selectedCell ? filteredTools.find((t) => t.id === selectedCell.toolId) : null
  const selectedScore = selectedCell
    ? sortedScores.find((s) => s.toolId === selectedCell.toolId)?.categoryScores.flatMap((cs) => cs.subFeatureScores).find((sf) => sf.subFeatureId === selectedCell.subFeatureId)
    : null

  return (
    <div className="relative">
      {/* Controls */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
        <input
          type="text"
          placeholder="Search tools..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="border border-border rounded-lg bg-background px-3 py-2 text-sm w-56 focus:outline-none focus:ring-1 focus:ring-ring"
        />
        <div className="flex items-center gap-3">
          <select
            value={sortField}
            onChange={(e) => handleSort(e.target.value)}
            className="border border-border rounded-lg bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
          >
            <option value="overall">Sort: Overall Score</option>
            <option value="name">Sort: Name</option>
            {framework.categories.filter((c) => c.weight > 0).map((c) => (
              <option key={c.id} value={c.id}>Sort: {c.name}</option>
            ))}
          </select>
          <button
            onClick={() => setSortDesc(!sortDesc)}
            className="border border-border rounded-lg bg-background px-3 py-2 text-sm hover:bg-accent"
          >
            {sortDesc ? '↓' : '↑'}
          </button>
          {/* Tool selector — right aligned, wider */}
          <div className="relative">
            <button
              onClick={() => setShowToolSelector(!showToolSelector)}
              className="border border-border rounded-lg bg-background px-3 py-2 text-sm hover:bg-accent transition-colors"
            >
              {filteredTools.length}/{tools.length} tools
            </button>
            {showToolSelector && (
              <div className="absolute right-0 top-full z-20 mt-1 w-80 rounded-lg border border-border bg-card p-2 shadow-lg">
                <div className="mb-2 flex items-center justify-between px-2">
                  <span className="text-xs font-semibold text-foreground">Select Tools to Compare</span>
                  <button onClick={() => setHiddenTools(new Set())} className="text-[10px] text-[var(--asrg-blue)] hover:underline">Show all</button>
                </div>
                <div className="max-h-64 overflow-y-auto">
                  {tools.map((tool) => (
                    <label key={tool.id} className="flex items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-muted/50 cursor-pointer">
                      <input type="checkbox" checked={!hiddenTools.has(tool.id)} onChange={() => toggleTool(tool.id)} className="rounded border-input" />
                      <span className="flex-1 text-foreground">{tool.name}</span>
                      <span className="text-xs text-muted-foreground">{tool.vendor}</span>
                    </label>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Table with horizontal scroll and sticky first column */}
      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full border-collapse" style={{ minWidth: `${280 + filteredTools.length * 120}px` }}>
          <thead>
            <tr className="bg-[var(--asrg-black)] text-white">
              <th className="sticky left-0 z-20 bg-[var(--asrg-black)] px-4 py-3 text-left text-sm font-bold min-w-[220px] max-w-[220px] border-r border-white/10 align-bottom">
                Category / Sub-Feature
              </th>
              {sortedToolIds.map((id) => {
                const tool = filteredTools.find((t) => t.id === id)
                const score = scores.find((s) => s.toolId === id)
                return (
                  <th key={id} className="px-2 py-3 text-center min-w-[120px] align-bottom">
                    <div className="grid grid-rows-[32px_auto_auto_20px_24px] items-center justify-items-center gap-0.5">
                      {/* Row 1: Logo — fixed 32px height */}
                      <a href="/portal/directory" className="transition-opacity hover:opacity-80 self-end">
                        {tool?.logo_url ? (
                          <img src={tool.logo_url} alt={tool.vendor} className="h-8 w-auto object-contain" />
                        ) : (
                          <div className="flex h-8 w-8 items-center justify-center rounded-md bg-white/10 text-[10px] font-bold text-white/80 hover:bg-white/20">
                            {tool?.logoPlaceholder || '?'}
                          </div>
                        )}
                      </a>
                      {/* Row 2: Tool name + vendor */}
                      <div className="text-center">
                        <div className="text-xs font-bold text-white leading-tight">{tool?.name}</div>
                        <div className="text-[9px] text-white/40">{tool?.vendor}</div>
                      </div>
                      {/* Row 3: Version */}
                      <div className="text-[9px] text-white/30">{tool?.version ? `v${tool.version}` : '\u00A0'}</div>
                      {/* Row 4: Sponsor badge — fixed 20px height, always present */}
                      <div className="h-5 flex items-center">
                        {tool?.isSponsor ? (
                          <span className="text-[8px] font-semibold bg-orange-500/20 text-orange-400 px-1.5 py-0.5 rounded-full">★ Sponsor</span>
                        ) : null}
                      </div>
                      {/* Row 5: Score — fixed 24px height */}
                      <span className="text-sm font-bold text-white">{score?.overallScore.toFixed(1) ?? '—'}</span>
                    </div>
                  </th>
                )
              })}
            </tr>
          </thead>
          <tbody>
            {framework.categories
              .filter((category) => category.weight > 0)
              .map((category) => (
              <CategoryRow
                key={category.id}
                category={category}
                toolScores={sortedScores}
                toolNames={sortedToolNames}
                onCellClick={(toolId, sfId) => setSelectedCell({ toolId, subFeatureId: sfId })}
              />
            ))}
          </tbody>
        </table>
      </div>

      {filteredTools.length === 0 && (
        <div className="text-center py-12 text-muted-foreground">No tools match your filters.</div>
      )}

      {selectedCell && selectedTool && selectedScore && (
        <FeedbackPanel
          toolId={selectedCell.toolId}
          toolName={selectedTool.name}
          subFeatureId={selectedCell.subFeatureId}
          subFeatureName={framework.categories.flatMap((c) => c.subFeatures).find((sf) => sf.id === selectedCell.subFeatureId)?.name ?? ''}
          score={selectedScore}
          onClose={() => setSelectedCell(null)}
        />
      )}
    </div>
  )
}
