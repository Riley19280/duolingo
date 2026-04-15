import type { Word } from '@/types';
import { useMemo, useRef, useState } from 'react';
import { generateMCOptions, getCorrectAnswer } from './helpers';
import { QuestionDisplay } from './QuestionDisplay';
import type { LocalAttempt, Session } from './types';

export function MultipleChoiceExercise({
    word,
    allWords,
    session,
    onAnswer,
}: {
    word: Word;
    allWords: Word[];
    session: Session;
    onAnswer: (attempt: LocalAttempt) => void;
}) {
    const [selected, setSelected] = useState<string | null>(null);
    const startTime = useRef(Date.now());

    const options = useMemo(
        () => generateMCOptions(word, allWords, session.answerForm),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [word.id],
    );

    const correctAnswer = getCorrectAnswer(word, session.answerForm);

    function handleSelect(option: string) {
        if (selected !== null) return;
        setSelected(option);
        onAnswer({
            word_id: word.id,
            given_answer: option,
            correct_answer: correctAnswer,
            is_correct: option === correctAnswer,
            response_time_ms: Date.now() - startTime.current,
            options,
        });
    }

    return (
        <div className="flex flex-col gap-6">
            <QuestionDisplay word={word} questionForm={session.questionForm} />
            <div className="grid grid-cols-2 gap-3">
                {options.map((opt) => {
                    const isCorrect = opt === correctAnswer;
                    const isSelected = opt === selected;
                    let cls = 'border-border hover:border-primary hover:bg-primary/5 cursor-pointer';
                    if (selected !== null) {
                        if (isCorrect) cls = 'border-green-500 bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200';
                        else if (isSelected) cls = 'border-red-400 bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                        else cls = 'border-border bg-muted/30 text-muted-foreground';
                    }
                    return (
                        <button
                            key={opt}
                            type="button"
                            onClick={() => handleSelect(opt)}
                            disabled={selected !== null}
                            className={`rounded-lg border-2 px-4 py-5 text-center text-lg font-medium transition-colors ${cls}`}
                        >
                            {opt}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
