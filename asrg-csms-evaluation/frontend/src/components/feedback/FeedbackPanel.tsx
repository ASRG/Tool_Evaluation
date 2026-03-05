import { useState, useEffect } from 'react'
import ScoreBadge from '@/components/ui/ScoreBadge'
import { fetchFeedback, submitVote, submitComment } from '@/api/feedback'
import { isLoggedIn, getLoginUrl, getUser } from '@/api/client'
import type { ComputedSubFeatureScore, FeedbackData } from '@/lib/types'

interface FeedbackPanelProps {
  toolId: number
  toolName: string
  subFeatureId: string
  subFeatureName: string
  score: ComputedSubFeatureScore
  onClose: () => void
}

export default function FeedbackPanel({
  toolId,
  toolName,
  subFeatureId,
  subFeatureName,
  score,
  onClose,
}: FeedbackPanelProps) {
  const [feedback, setFeedback] = useState<FeedbackData | null>(null)
  const [loading, setLoading] = useState(true)
  const [commentText, setCommentText] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    setLoading(true)
    fetchFeedback(toolId, subFeatureId)
      .then(setFeedback)
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [toolId, subFeatureId])

  const handleVote = async (voteType: 'agree' | 'disagree') => {
    if (!isLoggedIn()) {
      window.location.href = getLoginUrl()
      return
    }
    try {
      await submitVote(toolId, subFeatureId, voteType)
      const updated = await fetchFeedback(toolId, subFeatureId)
      setFeedback(updated)
    } catch (err) {
      console.error('Vote failed:', err)
    }
  }

  const handleComment = async () => {
    if (!commentText.trim() || submitting) return
    if (!isLoggedIn()) {
      window.location.href = getLoginUrl()
      return
    }
    setSubmitting(true)
    try {
      const newComment = await submitComment(toolId, subFeatureId, commentText.trim())
      setFeedback((prev) =>
        prev
          ? { ...prev, comments: [...prev.comments, newComment] }
          : prev,
      )
      setCommentText('')
    } catch (err) {
      console.error('Comment failed:', err)
    } finally {
      setSubmitting(false)
    }
  }

  const user = getUser()

  return (
    <div className="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl border-l border-gray-200 z-50 flex flex-col">
      {/* Header */}
      <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-gray-50">
        <div>
          <h3 className="font-bold text-sm text-asrg-black">{subFeatureName}</h3>
          <p className="text-xs text-gray-500">{toolName}</p>
        </div>
        <button
          onClick={onClose}
          className="p-1 hover:bg-gray-200 rounded transition-colors"
        >
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto px-5 py-4 space-y-5">
        {/* Rating */}
        <div>
          <div className="flex items-center gap-2 mb-2">
            <span className="text-xs font-bold text-gray-500 uppercase">Rating</span>
            <ScoreBadge rating={score.rating} />
          </div>
          {score.rationale && (
            <p className="text-sm text-gray-700 leading-relaxed">{score.rationale}</p>
          )}
          {score.evidenceUrl && (
            <a
              href={score.evidenceUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="text-xs text-asrg-purple hover:underline mt-1 inline-block"
            >
              View evidence &rarr;
            </a>
          )}
        </div>

        {/* Voting */}
        <div className="border-t border-gray-100 pt-4">
          <span className="text-xs font-bold text-gray-500 uppercase mb-2 block">
            Community Vote
          </span>
          {loading ? (
            <p className="text-xs text-gray-400">Loading...</p>
          ) : (
            <div className="flex items-center gap-3">
              <button
                onClick={() => handleVote('agree')}
                className={`flex items-center gap-1 px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  feedback?.votes.userVote === 'agree'
                    ? 'bg-green-100 text-score-fully border border-green-300'
                    : 'bg-gray-100 text-gray-600 hover:bg-green-50'
                }`}
              >
                <span>&#128077;</span>
                <span>{feedback?.votes.agree ?? 0}</span>
              </button>
              <button
                onClick={() => handleVote('disagree')}
                className={`flex items-center gap-1 px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  feedback?.votes.userVote === 'disagree'
                    ? 'bg-red-100 text-score-does-not border border-red-300'
                    : 'bg-gray-100 text-gray-600 hover:bg-red-50'
                }`}
              >
                <span>&#128078;</span>
                <span>{feedback?.votes.disagree ?? 0}</span>
              </button>
              {!isLoggedIn() && (
                <a href={getLoginUrl()} className="text-xs text-asrg-purple hover:underline">
                  Sign in to vote
                </a>
              )}
            </div>
          )}
        </div>

        {/* Comments */}
        <div className="border-t border-gray-100 pt-4">
          <span className="text-xs font-bold text-gray-500 uppercase mb-3 block">
            Comments ({feedback?.comments.filter((c) => !c.isDeleted).length ?? 0})
          </span>

          {loading ? (
            <p className="text-xs text-gray-400">Loading comments...</p>
          ) : (
            <div className="space-y-3">
              {feedback?.comments
                .filter((c) => !c.parentId)
                .map((comment) => (
                  <div key={comment.id} className="flex gap-2">
                    <img
                      src={comment.avatar}
                      alt=""
                      className="w-7 h-7 rounded-full flex-shrink-0 mt-0.5"
                    />
                    <div className="flex-1 min-w-0">
                      <div className="flex items-baseline gap-2">
                        <span className="text-xs font-bold text-gray-800">
                          {comment.userName}
                        </span>
                        <span className="text-[10px] text-gray-400">
                          {new Date(comment.createdAt).toLocaleDateString()}
                        </span>
                      </div>
                      <p className="text-sm text-gray-600 mt-0.5 break-words">
                        {comment.body}
                      </p>

                      {/* Replies */}
                      {feedback.comments
                        .filter((r) => r.parentId === comment.id)
                        .map((reply) => (
                          <div key={reply.id} className="flex gap-2 mt-2 ml-4">
                            <img
                              src={reply.avatar}
                              alt=""
                              className="w-5 h-5 rounded-full flex-shrink-0 mt-0.5"
                            />
                            <div>
                              <span className="text-xs font-bold text-gray-700">
                                {reply.userName}
                              </span>
                              <p className="text-xs text-gray-500">{reply.body}</p>
                            </div>
                          </div>
                        ))}
                    </div>
                  </div>
                ))}

              {feedback?.comments.length === 0 && (
                <p className="text-xs text-gray-400">No comments yet.</p>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Comment input */}
      <div className="border-t border-gray-200 px-5 py-3 bg-gray-50">
        {isLoggedIn() && user ? (
          <div className="flex gap-2">
            <input
              type="text"
              value={commentText}
              onChange={(e) => setCommentText(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleComment()}
              placeholder="Add a comment..."
              maxLength={2000}
              className="flex-1 border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-asrg-purple/50"
            />
            <button
              onClick={handleComment}
              disabled={!commentText.trim() || submitting}
              className="btn-primary text-sm disabled:opacity-50"
            >
              Post
            </button>
          </div>
        ) : (
          <a href={getLoginUrl()} className="text-sm text-asrg-purple hover:underline">
            Sign in to comment
          </a>
        )}
      </div>
    </div>
  )
}
