import type { Word } from '@/types';
import { useEffect, useMemo, useRef, useState } from 'react';
import { getCorrectAnswer, getQuestionText } from './helpers';
import type { LocalAttempt, Session } from './types';

export function MatchingExercise({
    words,
    session,
    onBatchComplete,
}: {
    words: Word[];
    session: Session;
    onBatchComplete: (attempts: LocalAttempt[]) => void;
}) {
    const [leftSelected, setLeftSelected] = useState<number | null>(null);
    const [matched, setMatched] = useState<Set<number>>(new Set());
    const [wrongCounts, setWrongCounts] = useState<Record<number, number>>({});
    const [flash, setFlash] = useState<{ leftId: number; rightId: number; correct: boolean } | null>(null);
    const batchStartTime = useRef(Date.now());
    const onBatchCompleteRef = useRef(onBatchComplete);
    onBatchCompleteRef.current = onBatchComplete;

    const rightItems = useMemo(
        () => [...words].sort(() => Math.random() - 0.5),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    function handleLeftClick(wordId: number) {
        if (matched.has(wordId) || flash) return;
        setLeftSelected((prev) => (prev === wordId ? null : wordId));
    }

    function handleRightClick(wordId: number) {
        if (!leftSelected || matched.has(wordId) || flash) return;

        const correct = leftSelected === wordId;
        setFlash({ leftId: leftSelected, rightId: wordId, correct });

        if (correct) {
            setTimeout(() => {
                setMatched((prev) => new Set(prev).add(wordId));
                setFlash(null);
                setLeftSelected(null);
            }, 350);
        } else {
            setWrongCounts((prev) => ({
                ...prev,
                [leftSelected]: (prev[leftSelected] ?? 0) + 1,
            }));
            setTimeout(() => {
                setFlash(null);
                setLeftSelected(null);
            }, 600);
        }
    }

    useEffect(() => {
        if (matched.size > 0 && matched.size === words.length) {
            const elapsed = Date.now() - batchStartTime.current;
            const perWord = Math.round(elapsed / words.length);
            const attempts: LocalAttempt[] = words.map((w) => ({
                word_id: w.id,
                given_answer: getCorrectAnswer(w, session.answerForm),
                correct_answer: getCorrectAnswer(w, session.answerForm),
                is_correct: (wrongCounts[w.id] ?? 0) === 0,
                response_time_ms: perWord,
                options: rightItems.map((r) => getCorrectAnswer(r, session.answerForm)),
            }));
            onBatchCompleteRef.current(attempts);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [matched.size]);

    const labelClass = 'mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground';

    function leftItemClass(word: Word) {
        const isMatched = matched.has(word.id);
        const isSelected = leftSelected === word.id;
        const isFlashLeft = flash?.leftId === word.id;
        if (isMatched) return 'border-green-200 bg-green-50 text-green-400 line-through dark:border-green-800 dark:bg-green-950/30 dark:text-green-700';
        if (isFlashLeft && flash?.correct) return 'border-green-500 bg-green-50 dark:bg-green-950';
        if (isFlashLeft && !flash?.correct) return 'border-red-400 bg-red-50 dark:bg-red-950';
        if (isSelected) return 'border-primary bg-primary/10';
        return 'border-border hover:border-muted-foreground';
    }

    function rightItemClass(word: Word) {
        const isMatched = matched.has(word.id);
        const isFlashRight = flash?.rightId === word.id;
        if (isMatched) return 'border-green-200 bg-green-50 text-green-400 line-through dark:border-green-800 dark:bg-green-950/30 dark:text-green-700';
        if (isFlashRight && flash?.correct) return 'border-green-500 bg-green-50 dark:bg-green-950';
        if (isFlashRight && !flash?.correct) return 'border-red-400 bg-red-50 dark:bg-red-950';
        if (leftSelected) return 'border-border hover:border-primary hover:bg-primary/5 cursor-pointer';
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
                        {getQuestionText(word, session.questionForm) ?? '🔊'}
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
                        disabled={matched.has(word.id) || !leftSelected}
                        className={`rounded-lg border-2 px-4 py-3 text-left text-lg font-medium transition-all ${rightItemClass(word)}`}
                    >
                        {getCorrectAnswer(word, session.answerForm)}
                    </button>
                ))}
            </div>
        </div>
    );
}
