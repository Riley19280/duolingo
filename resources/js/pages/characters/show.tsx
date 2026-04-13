import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { WordGrid } from '@/components/word-grid';
import { Word } from '@/types';
import { dashboard } from '@/routes';

interface Props {
    character: string;
    words: Word[];
    [key: string]: unknown;
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
