import type { Word } from '@/types';
import { useCallback, useMemo, useRef, useState } from 'react';
import { getCorrectAnswer, getQuestionText } from './helpers';
import type { LocalAttempt, Session } from './types';

const MATCH_BATCH_SIZE = 6

function MatchingBatch({
    words,
    session,
    onAnswer,
}: {
    words: Word[];
    session: Session;
    onAnswer: (attempt: LocalAttempt) => void;
}) {
    const [leftWordId, setLeftWordId] = useState<number | null>(null);
    const [matched, setMatched] = useState<Set<number>>(new Set());
    const [flash, setFlash] = useState<{ leftId: number; rightId: number; correct: boolean } | null>(null);
    const lastMatchTime = useRef(Date.now());
    const onAnswerRef = useRef(onAnswer);
    onAnswerRef.current = onAnswer;

    const rightItems = useMemo(
        () => [...words].sort(() => Math.random() - 0.5),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    function handleLeftClick(wordId: number) {
        if (matched.has(wordId) || flash) {
            return;
        }

        setLeftWordId((prev) => (prev === wordId ? null : wordId));

        const word = words.find((w) => w.id === wordId);

        if (word && session.questionForm === 'audio' && word.ttsUrl) {
            new Audio(word.ttsUrl).play();
        }
    }

    function handleRightClick(rightWordId: number) {
        if (!leftWordId || matched.has(rightWordId) || flash) {
            return;
        }

        const correct = leftWordId === rightWordId;
        const now = Date.now();
        const responseTime = now - lastMatchTime.current;
        lastMatchTime.current = now;

        setFlash({ leftId: leftWordId, rightId: rightWordId, correct });
        setTimeout(() => {
                setFlash(null);
                setLeftWordId(null);
            }, correct ? 350 : 600,
        );

        const leftWord = words.find((w) => w.id === leftWordId)!;
        const rightWord = words.find((w) => w.id === rightWordId)!;

        if (correct) {
            setMatched((prev) => new Set(prev).add(rightWordId));
        }

        const attempt: LocalAttempt = {
            word_id: leftWord.id,
            given_answer: getCorrectAnswer(leftWord, session.answerForm),
            correct_answer: getCorrectAnswer(rightWord, session.answerForm),
            is_correct: correct,
            response_time_ms: responseTime,
            options: rightItems.map((r) =>
                getCorrectAnswer(r, session.answerForm),
            ),
        };
        onAnswerRef.current(attempt);
    }

    const labelClass = 'mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground';

    function leftItemClass(word: Word) {
        const isMatched = matched.has(word.id);
        const isSelected = leftWordId === word.id;
        const isFlashLeft = flash?.leftId === word.id;

        if (isMatched) {
            return 'border-green-200 bg-green-50 text-green-400 line-through dark:border-green-800 dark:bg-green-950/30 dark:text-green-700';
        }

        if (isFlashLeft && flash?.correct) {
            return 'border-green-500 bg-green-50 dark:bg-green-950';
        }

        if (isFlashLeft && !flash?.correct) {
            return 'border-red-400 bg-red-50 dark:bg-red-950';
        }

        if (isSelected) {
            return 'border-primary bg-primary/10';
        }

        return 'border-border hover:border-muted-foreground';
    }

    function rightItemClass(word: Word) {
        const isMatched = matched.has(word.id);
        const isFlashRight = flash?.rightId === word.id;

        if (isMatched) {
            return 'border-green-200 bg-green-50 text-green-400 line-through dark:border-green-800 dark:bg-green-950/30 dark:text-green-700';
        }

        if (isFlashRight && flash?.correct) {
            return 'border-green-500 bg-green-50 dark:bg-green-950';
        }

        if (isFlashRight && !flash?.correct) {
            return 'border-red-400 bg-red-50 dark:bg-red-950';
        }

        if (leftWordId) {
            return 'border-border hover:border-primary hover:bg-primary/5 cursor-pointer';
        }

        return 'border-border opacity-50';
    }

    return (
        <div className="grid grid-cols-2 gap-4">
            <div className="flex flex-col gap-2">
                <p className={labelClass}>{session.questionForm}</p>
                {words.map((word) => (
                    <button
                        key={word.id}
                        type="button"
                        onClick={() => handleLeftClick(word.id)}
                        disabled={matched.has(word.id)}
                        className={`cursor-pointer rounded-lg border-2 px-4 py-3 text-left text-lg font-medium transition-all ${leftItemClass(word)}`}
                    >
                        {getQuestionText(word, session.questionForm)}
                    </button>
                ))}
            </div>
            <div className="flex flex-col gap-2">
                <p className={labelClass}>{session.answerForm}</p>
                {rightItems.map((word) => (
                    <button
                        key={word.id}
                        type="button"
                        onClick={() => handleRightClick(word.id)}
                        disabled={matched.has(word.id) || !leftWordId}
                        className={`rounded-lg border-2 px-4 py-3 text-left text-lg font-medium transition-all ${rightItemClass(word)}`}
                    >
                        {getCorrectAnswer(word, session.answerForm)}
                    </button>
                ))}
            </div>
        </div>
    );
}

export function MatchingExercise({
    words,
    session,
    onAnswer,
}: {
    words: Word[];
    session: Session;
    onAnswer: (attempt: LocalAttempt) => void;
}) {
    const [batchStart, setBatchStart] = useState(0);
    const onAnswerRef = useRef(onAnswer);
    // eslint-disable-next-line react-hooks/refs
    onAnswerRef.current = onAnswer;
    const answeredInBatchRef = useRef(0);

    const currentBatch = words.slice(batchStart, batchStart + MATCH_BATCH_SIZE);

    const handleBatchAnswer = useCallback(
        (attempt: LocalAttempt) => {
            onAnswerRef.current(attempt);
            answeredInBatchRef.current += 1;

            if (answeredInBatchRef.current >= currentBatch.length) {
                answeredInBatchRef.current = 0;

                const next = batchStart + MATCH_BATCH_SIZE;

                if (next < words.length) {
                    setBatchStart(next);
                }
            }
        },
        [batchStart, currentBatch.length, words.length],
    );

    return (
        <MatchingBatch
            key={batchStart}
            words={currentBatch}
            session={session}
            onAnswer={handleBatchAnswer}
        />
    );
}
