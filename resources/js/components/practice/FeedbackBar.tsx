import { useEffect, useRef } from 'react';
import { CheckCircle2, ChevronRight, XCircle } from 'lucide-react';
import type { LocalAttempt } from './types';

export function FeedbackBar({
    attempt,
    ttsUrl,
    onContinue,
}: {
    attempt: LocalAttempt;
    ttsUrl?: string | null;
    onContinue: () => void;
}) {
    const continueRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        continueRef.current?.focus();
    }, []);

    return (
        <div
            className={`mt-4 flex items-center justify-between rounded-lg px-5 py-4 ${attempt.is_correct ? 'bg-green-50 dark:bg-green-950/50' : 'bg-red-50 dark:bg-red-950/50'}`}
        >
            <div className="flex items-center gap-2">
                {attempt.is_correct ? (
                    <CheckCircle2 className="size-5 text-green-600 dark:text-green-400" />
                ) : (
                    <XCircle className="size-5 text-red-500 dark:text-red-400" />
                )}
                <span className={`font-medium ${attempt.is_correct ? 'text-green-700 dark:text-green-300' : 'text-red-600 dark:text-red-300'}`}>
                    {attempt.is_correct ? 'Correct!' : (
                        <>
                            Answer:{' '}
                            <span
                                className={ttsUrl ? 'cursor-pointer underline-offset-2 hover:underline' : ''}
                                onClick={() => ttsUrl && new Audio(ttsUrl).play()}
                            >
                                {attempt.correct_answer}
                            </span>
                        </>
                    )}
                </span>
            </div>
            <button
                ref={continueRef}
                type="button"
                onClick={onContinue}
                className={`flex cursor-pointer items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors ${attempt.is_correct ? 'bg-green-600 hover:bg-green-700' : 'bg-red-500 hover:bg-red-600'}`}
            >
                Continue <ChevronRight className="size-4" />
            </button>
        </div>
    );
}
