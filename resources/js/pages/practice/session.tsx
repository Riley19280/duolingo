import { Head, router, usePage } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { FeedbackBar } from '@/components/practice/FeedbackBar';
import { MatchingExercise } from '@/components/practice/MatchingExercise';
import { MultipleChoiceExercise } from '@/components/practice/MultipleChoiceExercise';
import { ResultsScreen } from '@/components/practice/ResultsScreen';
import { TypingExercise } from '@/components/practice/TypingExercise';
import type { LocalAttempt, Session, SessionResult } from '@/components/practice/types';
import { index as practiceIndex } from '@/routes/practice';
import { complete as completeRoute } from '@/routes/practice/sessions/index';
import type { Word } from '@/types';

interface Props {
    session: Session;
    words: Word[];
    results: SessionResult[] | null;
    [key: string]: unknown;
}

export default function PracticeSession() {
    const { session, words, results } = usePage<Props>().props;

    const [wordIndex, setWordIndex] = useState(0);
    const [pendingAttempts, setPendingAttempts] = useState<LocalAttempt[]>([]);
    const [feedbackAttempt, setFeedbackAttempt] = useState<LocalAttempt | null>(null);
    const [processing, setProcessing] = useState(false);

    const isMatching = session.exerciseType === 'matching';
    const totalWords = words.length;
    const completedCount =  isMatching ? pendingAttempts.filter(attempt => attempt.is_correct).length : wordIndex;

    const finishSession = useCallback((attempts: LocalAttempt[]) => {
            setProcessing(true);
            router.post(completeRoute.url(session.id), { attempts: attempts } as any, {
                onFinish: () => {
                    setProcessing(false);
                    setPendingAttempts([])
                },
            });
        },
        [session.id],
    );

    // MC and Typing: user answered — show feedback bar
    const handleAnswer = useCallback((attempt: LocalAttempt) => {
        const newAttempts = [...pendingAttempts, attempt]

        setFeedbackAttempt(attempt);
        setPendingAttempts(newAttempts)

        if (isMatching) {
            if (newAttempts.filter((attempt) => attempt.is_correct).length >= totalWords) {
                finishSession(newAttempts);
            } else {
                setWordIndex(pendingAttempts.filter((attempt) => attempt.is_correct).length,);
            }
        }
    }, [finishSession, isMatching, pendingAttempts, totalWords]);

    // MC and Typing: Continue pressed in feedback bar — advance
    const handleContinue = useCallback(() => {
        const nextIndex = wordIndex + 1;
        setFeedbackAttempt(null);

        if (nextIndex >= totalWords) {
            finishSession(pendingAttempts);
        } else {
            setWordIndex(nextIndex);
        }
    }, [wordIndex, totalWords, finishSession, pendingAttempts]);

    if (session.completedAt && results) {
        return (
            <>
                <Head title="Results" />
                <div className="flex h-full flex-1 flex-col items-center gap-6 p-4 pt-10">
                    <h1 className="text-2xl font-bold">Session Complete</h1>
                    <ResultsScreen results={results} />
                </div>
            </>
        );
    }

    if (processing) {
        return (
            <>
                <Head title="Practice" />
                <div className="flex h-full flex-1 items-center justify-center">
                    <p className="text-muted-foreground">Saving results…</p>
                </div>
            </>
        );
    }

    if (totalWords === 0) {
        return (
            <>
                <Head title="Practice" />
                <div className="flex h-full flex-1 items-center justify-center">
                    <p className="text-muted-foreground">No words in this practice set.</p>
                </div>
            </>
        );
    }

    const progress = completedCount / totalWords;

    return (
        <>
            <Head title="Practice" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Progress */}
                <div className="mb-6">
                    <div className="mb-1 flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            {completedCount} / {totalWords}
                        </span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-all duration-300"
                            style={{ width: `${progress * 100}%` }}
                        />
                    </div>
                </div>

                {/* Exercise card */}
                <div className="flex flex-1 flex-col items-center">
                    <div
                        className={`w-full ${isMatching ? 'max-w-2xl' : 'max-w-lg'}`}
                    >
                        <Card>
                            <CardContent className="p-6">
                                {session.exerciseType === 'multiple_choice' && (
                                    <MultipleChoiceExercise
                                        key={wordIndex}
                                        word={words[wordIndex]}
                                        allWords={words}
                                        session={session}
                                        onAnswer={handleAnswer}
                                    />
                                )}
                                {session.exerciseType === 'typing' && (
                                    <TypingExercise
                                        key={wordIndex}
                                        word={words[wordIndex]}
                                        session={session}
                                        onAnswer={handleAnswer}
                                    />
                                )}
                                {session.exerciseType === 'matching' && (
                                    <MatchingExercise
                                        words={words}
                                        session={session}
                                        onAnswer={handleAnswer}
                                    />
                                )}
                            </CardContent>
                        </Card>

                        {/* Feedback bar (MC and Typing) */}
                        {feedbackAttempt && !isMatching && (
                            <FeedbackBar attempt={feedbackAttempt} onContinue={handleContinue} />
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

PracticeSession.layout = {
    breadcrumbs: [
        { title: 'Practice', href: practiceIndex.url() },
        { title: 'Session', href: '#' },
    ],
};
