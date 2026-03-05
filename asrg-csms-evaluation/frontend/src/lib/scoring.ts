/**
 * Client-side scoring engine — mirrors the PHP implementation.
 */

import {
  type EvaluationFramework,
  type Tool,
  type ComputedToolScore,
  type ComputedCategoryScore,
  SubFeatureRating,
  RATING_NUMERIC,
} from './types'

const COMMUNITY_MAX_ADJUSTMENT = 0.15
const MIN_VOTES_THRESHOLD = 5

interface FeedbackSummary {
  [subFeatureId: string]: { agree: number; disagree: number }
}

/**
 * Compute scores for a single tool using the evaluation framework.
 */
export function computeToolScore(
  framework: EvaluationFramework,
  tool: Tool,
  feedback?: FeedbackSummary,
): ComputedToolScore {
  const categoryScores: ComputedCategoryScore[] = framework.categories.map((category) => {
    const subFeatureScores = category.subFeatures.map((sf) => {
      const score = tool.scores.find((s) => s.subFeatureId === sf.id)
      const rating = score?.rating ?? SubFeatureRating.DOES_NOT_FULFILL
      const numericRating = RATING_NUMERIC[rating] ?? 0

      const fb = feedback?.[sf.id]
      const communityConfidence = fb
        ? computeCommunityConfidence(fb.agree, fb.disagree)
        : 0

      const adjustedRating = Math.max(
        0,
        Math.min(1, numericRating + communityConfidence * COMMUNITY_MAX_ADJUSTMENT),
      )

      return {
        subFeatureId: sf.id,
        rating,
        numericRating,
        communityConfidence: Math.round(communityConfidence * 10000) / 10000,
        adjustedRating: Math.round(adjustedRating * 10000) / 10000,
        weight: sf.weight,
        rationale: score?.rationale ?? '',
        evidenceUrl: score?.evidenceUrl ?? '',
      }
    })

    const editorialScore =
      subFeatureScores.reduce((sum, sf) => sum + sf.numericRating * sf.weight, 0) * 100

    const communityAdjustedScore =
      subFeatureScores.reduce((sum, sf) => sum + sf.adjustedRating * sf.weight, 0) * 100

    return {
      categoryId: category.id,
      editorialScore: Math.round(editorialScore * 10) / 10,
      communityAdjustedScore: Math.round(communityAdjustedScore * 10) / 10,
      subFeatureScores,
    }
  })

  const overallScore = categoryScores.reduce((sum, cs, i) => {
    return sum + cs.communityAdjustedScore * framework.categories[i].weight
  }, 0)

  return {
    toolId: tool.id,
    overallScore: Math.round(overallScore * 10) / 10,
    categoryScores,
  }
}

/**
 * Wilson score lower bound for community confidence.
 * Returns -1 (strong disagreement) to +1 (strong agreement).
 */
function computeCommunityConfidence(agree: number, disagree: number): number {
  const total = agree + disagree

  if (total < MIN_VOTES_THRESHOLD) return 0

  const p = agree / total
  const z = 1.96 // 95% confidence

  const denominator = 1 + (z * z) / total
  const center = p + (z * z) / (2 * total)
  const spread = z * Math.sqrt((p * (1 - p) + (z * z) / (4 * total)) / total)

  const wilsonLower = (center - spread) / denominator

  return (wilsonLower - 0.5) * 2
}
