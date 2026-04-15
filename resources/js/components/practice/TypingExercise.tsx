import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import type { Word } from '@/types';
import { checkAnswer, getCorrectAnswer } from './helpers';
import { QuestionDisplay } from './QuestionDisplay';
import type { LocalAttempt, Session } from './types';

export function TypingExercise({
    word,
    session,
    onAnswer,
}: {
    word: Word;
    session: Session;
    onAnswer: (attempt: LocalAttempt) => void;
}) {
    const [input, setInput] = useState('');
    const [submitted, setSubmitted] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const startTime = useRef(Date.now());

    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    const correctAnswer = getCorrectAnswer(word, session.answerForm);

    function check() {
        if (submitted || !input.trim()) {
            return;
        }

        setSubmitted(true);
        const isCorrect = checkAnswer(input, correctAnswer, session.answerForm);
        const attempt = {
            word_id: word.id,
            given_answer: input,
            correct_answer: correctAnswer,
            is_correct: isCorrect,
            response_time_ms: Date.now() - startTime.current,
            options: null,
        };
        onAnswer(attempt);
    }

    const placeholder =
        session.answerForm === 'character'
            ? 'Type the character…'
            : session.answerForm === 'pinyin'
              ? 'Type the pinyin…'
              : 'Type the English…';

    return (
        <div className="flex flex-col gap-6">
            <QuestionDisplay word={word} questionForm={session.questionForm} />
            <div className="flex flex-col gap-3">
                <Input
                    ref={inputRef}
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    onKeyUp={(e) => e.key === 'Enter' && check()}
                    disabled={submitted}
                    placeholder={placeholder}
                    className="text-center text-lg"
                />
                <button
                    type="button"
                    onClick={check}
                    disabled={submitted || !input.trim()}
                    className="cursor-pointer rounded-lg bg-primary px-4 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Check
                </button>
            </div>
        </div>
    );
}
