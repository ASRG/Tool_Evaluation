import { apiFetch } from './client'
import type { FeedbackData, FeedbackComment } from '@/lib/types'
import { mockFeedback } from './mock-data'

const isDev = import.meta.env.DEV

export async function fetchFeedback(
  toolId: number,
  subFeatureId: string,
): Promise<FeedbackData> {
  if (!isDev) return apiFetch<FeedbackData>(`feedback/${toolId}/${subFeatureId}`)

  try {
    return await apiFetch<FeedbackData>(`feedback/${toolId}/${subFeatureId}`)
  } catch {
    return mockFeedback
  }
}

export async function submitVote(
  toolId: number,
  subFeatureId: string,
  voteType: 'agree' | 'disagree',
): Promise<{ success: boolean }> {
  return apiFetch('feedback/vote', {
    method: 'POST',
    body: JSON.stringify({ toolId, subFeatureId, voteType }),
  })
}

export async function deleteVote(voteId: number): Promise<void> {
  return apiFetch(`feedback/vote/${voteId}`, { method: 'DELETE' })
}

export async function submitComment(
  toolId: number,
  subFeatureId: string,
  body: string,
  parentId?: number,
): Promise<FeedbackComment> {
  return apiFetch('feedback/comment', {
    method: 'POST',
    body: JSON.stringify({ toolId, subFeatureId, body, parentId }),
  })
}

export async function deleteComment(commentId: number): Promise<void> {
  return apiFetch(`feedback/comment/${commentId}`, { method: 'DELETE' })
}
