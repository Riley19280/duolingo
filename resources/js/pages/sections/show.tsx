import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { WordGrid } from '@/components/word-grid';
import { Section, Word } from '@/types';
import { index as sectionsIndex } from '@/routes/sections';

interface Props {
    section: Section;
    words: Word[];
    [key: string]: unknown;
}

export default function SectionShow() {
    const { section, words } = usePage<Props>().props;

    const available = words.filter((w) => w.isAvailable);
    const locked = words.filter((w) => !w.isAvailable);

    return (
        <>
            <Head title={section.title} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            {section.title}
                            <span className="text-sm font-normal text-muted-foreground">
                                {available.length} / {words.length} available
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {available.length > 0 && (
                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Available ({available.length})
                                </p>
                                <WordGrid words={available} />
                            </div>
                        )}
                        {available.length > 0 && locked.length > 0 && <hr />}
                        {locked.length > 0 && (
                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Locked ({locked.length})
                                </p>
                                <WordGrid words={locked} />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}


SectionShow.layout = {
    breadcrumbs: [
        { title: 'Sections', href: sectionsIndex.url() },
        { title: 'Section', href: '#' },
    ],
};
