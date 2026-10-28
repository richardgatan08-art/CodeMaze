// progress_tracker.js
// Shared helper that records player activity (games started, quests answered)
// to the Code Maze progress API. Used by the game page and the quest UI.

(function () {
  const API = 'progress_api.php';

  function post(data) {
    try {
      return fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
        credentials: 'same-origin'
      }).catch(() => {});
    } catch (e) {
      return Promise.resolve(null);
    }
  }

  /* Record that a player started / played a round of the game */
  function recordGameStart() {
    return post({ action: 'game_start' });
  }

  /* Record a completed (answered correctly) quest */
  function recordQuestComplete(questionData) {
    return post({
      action: 'quest_complete',
      language: (questionData && questionData.language) || '',
      question: (questionData && questionData.question) || '',
      code: (questionData && questionData.code) || '',
      hint: (questionData && questionData.hint) || ''
    });
  }

  /* Record a wrong answer / missed quest */
  function recordQuestWrong(questionData) {
    return post({
      action: 'quest_wrong',
      language: (questionData && questionData.language) || '',
      question: (questionData && questionData.question) || '',
      code: (questionData && questionData.code) || '',
      hint: (questionData && questionData.hint) || ''
    });
  }

  window.CodeMazeTracker = {
    gameStart: recordGameStart,
    questComplete: recordQuestComplete,
    questWrong: recordQuestWrong
  };
})();