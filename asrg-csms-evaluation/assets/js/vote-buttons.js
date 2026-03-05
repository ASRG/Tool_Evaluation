/**
 * CSMS Vote Buttons — vanilla JS for AJAX voting.
 *
 * Each vote button group is rendered by the [csms_vote] shortcode and
 * contains data attributes for tool_id and sub_feature_id.
 */
(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.csms-vote-btn');
    if (!btn) return;

    e.preventDefault();

    var container = btn.closest('.csms-vote-buttons');
    if (!container) return;

    var toolId = container.dataset.toolId;
    var subFeatureId = container.dataset.subFeatureId;
    var voteType = btn.dataset.voteType;
    var nonce = container.dataset.nonce;
    var apiBase = container.dataset.apiBase;

    if (!toolId || !subFeatureId || !voteType) return;

    // Disable buttons during request.
    var buttons = container.querySelectorAll('.csms-vote-btn');
    buttons.forEach(function (b) { b.disabled = true; });

    fetch(apiBase + '/feedback/vote', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify({
        toolId: parseInt(toolId, 10),
        subFeatureId: subFeatureId,
        voteType: voteType,
      }),
    })
      .then(function (res) { return res.json(); })
      .then(function () {
        // Refresh vote counts.
        return fetch(apiBase + '/feedback/' + toolId + '/' + subFeatureId, {
          headers: { 'X-WP-Nonce': nonce },
        });
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        var votes = data.votes || {};

        // Update counts.
        var agreeCount = container.querySelector('.csms-vote-count-agree');
        var disagreeCount = container.querySelector('.csms-vote-count-disagree');
        if (agreeCount) agreeCount.textContent = votes.agree || 0;
        if (disagreeCount) disagreeCount.textContent = votes.disagree || 0;

        // Update active state.
        buttons.forEach(function (b) {
          b.classList.remove('csms-vote-active');
          if (b.dataset.voteType === votes.userVote) {
            b.classList.add('csms-vote-active');
          }
          b.disabled = false;
        });
      })
      .catch(function () {
        buttons.forEach(function (b) { b.disabled = false; });
      });
  });
})();
