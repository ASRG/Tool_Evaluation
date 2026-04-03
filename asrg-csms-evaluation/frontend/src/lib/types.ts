// === Evaluation Framework Types ===

export enum SubFeatureRating {
  FULLY_FULFILLS = 'fully_fulfills',
  PARTIALLY_FULFILLS = 'partially_fulfills',
  DOES_NOT_FULFILL = 'does_not_fulfill',
}

export const RATING_NUMERIC: Record<SubFeatureRating, number> = {
  [SubFeatureRating.FULLY_FULFILLS]: 1.0,
  [SubFeatureRating.PARTIALLY_FULFILLS]: 0.5,
  [SubFeatureRating.DOES_NOT_FULFILL]: 0.0,
}

export const RATING_LABELS: Record<SubFeatureRating, string> = {
  [SubFeatureRating.FULLY_FULFILLS]: 'Fully Fulfills',
  [SubFeatureRating.PARTIALLY_FULFILLS]: 'Partially Fulfills',
  [SubFeatureRating.DOES_NOT_FULFILL]: 'Does Not Fulfill',
}

export interface SubFeatureDefinition {
  id: string
  name: string
  description: string
  weight: number
  isoReference?: string
}

export interface CategoryDefinition {
  id: string
  slug: string
  name: string
  description: string
  weight: number
  subFeatures: SubFeatureDefinition[]
}

export interface EvaluationFramework {
  version: string
  lastUpdated: string
  categories: CategoryDefinition[]
}

// === Tool Types ===

export interface ToolScore {
  subFeatureId: string
  rating: SubFeatureRating
  rationale: string
  evidenceUrl?: string
  lastReviewed?: string
}

export interface Tool {
  id: number
  slug: string
  name: string
  vendor: string
  version: string
  website: string
  logo_url: string
  logoPlaceholder: string
  description: string
  isSponsor: boolean
  sponsor_tier: string
  status: string
  scores: ToolScore[]
  created_at: string
  updated_at: string
}

// === Computed Score Types ===

export interface ComputedSubFeatureScore {
  subFeatureId: string
  rating: SubFeatureRating
  numericRating: number
  communityConfidence: number
  adjustedRating: number
  weight: number
  rationale: string
  evidenceUrl: string
}

export interface ComputedCategoryScore {
  categoryId: string
  editorialScore: number
  communityAdjustedScore: number
  subFeatureScores: ComputedSubFeatureScore[]
}

export interface ComputedToolScore {
  toolId: number
  slug?: string
  name?: string
  overallScore: number
  categoryScores: ComputedCategoryScore[]
}

// === Feedback Types ===

export interface FeedbackVotes {
  agree: number
  disagree: number
  userVote: 'agree' | 'disagree' | null
}

export interface FeedbackComment {
  id: number
  userId: number
  userName: string
  avatar: string
  body: string
  parentId: number | null
  isDeleted: boolean
  createdAt: string
  updatedAt: string | null
}

export interface FeedbackData {
  votes: FeedbackVotes
  comments: FeedbackComment[]
}

// === Auth Types ===

export interface CsmsUser {
  id: number
  name: string
  email: string
  avatar: string
  role: 'anonymous' | 'community' | 'vendor' | 'editor'
}

export interface CsmsConfig {
  apiBase: string
  nonce: string
  isLoggedIn: boolean
  loginUrl: string
  user: CsmsUser | null
  pluginUrl: string
  version: string
}

declare global {
  interface Window {
    csmsConfig: CsmsConfig
  }
}
