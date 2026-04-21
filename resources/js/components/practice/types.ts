export interface Session {
    id: number;
    exerciseType: string;
    questionForm: string;
    answerForm: string;
    completedAt: string | null;
}

export interface SessionResult {
    wordId: number;
    word: { text: string; pinyin: string; translation: string | null; ttsUrl: string | null };
    isCorrect: boolean;
    givenAnswer: string | null;
    correctAnswer: string;
    responseTimeMs: number | null;
}

export interface LocalAttempt {
    word_id: number;
    given_answer: string | null;
    correct_answer: string;
    is_correct: boolean;
    response_time_ms: number;
    options: string[] | null;
}
