import { index as practiceIndex } from '@/routes/practice';
import type { SessionResult } from './types';

export function ResultsScreen({ results }: { results: SessionResult[] }) {
    const correct = results.filter((r) => r.isCorrect).length;
    const total = results.length;
    const pct = total > 0 ? Math.round((correct / total) * 100) : 0;
    const wrong = results.filter((r) => !r.isCorrect);

    const scoreColor =
        pct >= 80
            ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
            : pct >= 50
              ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300'
              : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';

    return (
        <div className="mx-auto flex w-full max-w-xl flex-col items-center gap-6">
            <div className="flex flex-col items-center gap-2">
                <div className={`flex h-32 w-32 items-center justify-center rounded-full text-4xl font-bold ${scoreColor}`}>
                    {pct}%
                </div>
                <p className="text-lg font-medium">
                    {correct} / {total} correct
                </p>
            </div>

            {wrong.length > 0 && (
                <div className="w-full">
                    <h3 className="mb-2 text-sm font-medium text-muted-foreground">Needs review</h3>
                    <div className="divide-y rounded-lg border">
                        {wrong.map((r) => (
                            <div key={r.wordId} className="flex items-center gap-4 px-4 py-3">
                                <span className="w-14 shrink-0 text-xl font-bold">{r.word.text}</span>
                                <span className="w-24 shrink-0 text-sm text-muted-foreground">{r.word.pinyin}</span>
                                <span className="min-w-0 flex-1 truncate text-sm">{r.word.translation}</span>
                                <div className="text-right text-sm">
                                    {r.givenAnswer && (
                                        <p className="text-red-500 line-through">{r.givenAnswer}</p>
                                    )}
                                    <p className="font-medium">{r.correctAnswer}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <a
                href={practiceIndex.url()}
                className="rounded-lg bg-primary px-6 py-2.5 font-medium text-primary-foreground hover:bg-primary/90"
            >
                Back to Practice
            </a>
        </div>
    );
}
