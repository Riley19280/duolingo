import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

interface Word {
    text: string;
    pinyin: string;
    translation: string | null;
    isAvailable: boolean;
    ttsUrl: string | null;
}

interface Props {
    character: string;
    words: Word[];
    [key: string]: unknown;
}

function WordGrid({ words }: { words: Word[] }) {
    return (
        <div className="grid grid-cols-[1fr_1fr_1fr] gap-x-4 gap-y-2">
            {words.map((word) => (
                <React.Fragment key={word.text}>
                    <span
                        className={word.ttsUrl ? 'cursor-pointer select-none text-lg font-bold hover:opacity-70' : 'text-lg font-bold'}
                        onClick={() => word.ttsUrl && new Audio(word.ttsUrl!).play()}
                    >
                        {word.text}
                    </span>
                    <span className="self-center text-sm text-muted-foreground">{word.pinyin}</span>
                    <span className="self-center truncate text-sm text-right">{word.translation}</span>
                </React.Fragment>
            ))}
        </div>
    );
}

export default function CharacterShow() {
    const { character, words } = usePage<Props>().props;

    const learned = words.filter((w) => w.isAvailable);
    const unlearned = words.filter((w) => !w.isAvailable);

    return (
        <>
            <Head title={character} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex justify-center">
                    <span className="text-8xl font-bold">{character}</span>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Words
                            <span className="text-sm font-normal text-muted-foreground">{words.length}</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {learned.length > 0 && (
                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Learned ({learned.length})</p>
                                <WordGrid words={learned} />
                            </div>
                        )}
                        {learned.length > 0 && unlearned.length > 0 && <hr />}
                        {unlearned.length > 0 && (
                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Not yet learned ({unlearned.length})</p>
                                <WordGrid words={unlearned} />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CharacterShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Characters', href: '#' },
    ],
};
