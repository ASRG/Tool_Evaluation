import { apiFetch } from './client'
import type { EvaluationFramework, Tool, ComputedToolScore } from '@/lib/types'
import { computeToolScore } from '@/lib/scoring'
import { mockFramework, mockTools } from './mock-data'

/**
 * In dev mode, if the WP REST API is unreachable, fall back to mock data
 * so the frontend is usable without a running WordPress backend.
 */
const isDev = import.meta.env.DEV

async function withMockFallback<T>(
  apiFn: () => Promise<T>,
  mockFn: () => T,
): Promise<T> {
  if (!isDev) return apiFn()

  try {
    return await apiFn()
  } catch {
    console.warn('[CSMS] API unreachable, using mock data.')
    return mockFn()
  }
}

export async function fetchFramework(): Promise<EvaluationFramework> {
  return withMockFallback(
    () => apiFetch<EvaluationFramework>('framework'),
    () => mockFramework,
  )
}

export async function fetchTools(): Promise<Tool[]> {
  return withMockFallback(
    () => apiFetch<Tool[]>('tools'),
    () => mockTools,
  )
}

export async function fetchTool(slug: string): Promise<Tool> {
  return withMockFallback(
    () => apiFetch<Tool>(`tools/${slug}`),
    () => {
      const tool = mockTools.find((t) => t.slug === slug)
      if (!tool) throw new Error(`Tool not found: ${slug}`)
      return tool
    },
  )
}

export async function fetchAllScores(): Promise<ComputedToolScore[]> {
  return withMockFallback(
    () => apiFetch<ComputedToolScore[]>('scores'),
    () => mockTools.map((tool) => computeToolScore(mockFramework, tool)),
  )
}

export async function fetchToolScores(slug: string): Promise<ComputedToolScore> {
  return withMockFallback(
    () => apiFetch<ComputedToolScore>(`scores/${slug}`),
    () => {
      const tool = mockTools.find((t) => t.slug === slug)
      if (!tool) throw new Error(`Tool not found: ${slug}`)
      return computeToolScore(mockFramework, tool)
    },
  )
}
