import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Lock, Unlock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Section } from '@/types';
import { dashboard } from '@/routes';
import { index as sectionsIndex, show as sectionShow, update as sectionUpdate } from '@/routes/sections';

interface Props {
    sections: Section[];
    [key: string]: unknown;
}

export default function SectionsIndex() {
    const { sections } = usePage<Props>().props;

    function toggle(section: Section) {
        router.put(sectionUpdate.url(section.id), {
            is_unlocked: !section.isUnlocked,
        });
    }

    return (
        <>
            <Head title="Sections" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Sections
                            <span className="text-sm font-normal text-muted-foreground">
                                {sections.filter((s) => s.isUnlocked).length} / {sections.length} unlocked
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-1">
                            {sections.map((section) => (
                                <div
                                    key={section.id}
                                    className="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-accent"
                                >
                                    <div className="flex items-center gap-3">
                                        {section.isUnlocked
                                            ? <CheckCircle2 className="size-4 shrink-0 text-green-500" />
                                            : <div className="size-4 shrink-0" />
                                        }
                                        <Link
                                            href={sectionShow.url(section.id)}
                                            className={section.isUnlocked ? 'text-foreground hover:underline' : 'text-muted-foreground hover:underline'}
                                        >
                                            {section.title}
                                        </Link>
                                        <span className="text-xs text-muted-foreground">
                                            {section.wordsCount} words
                                        </span>
                                    </div>
                                    <button
                                        onClick={() => toggle(section)}
                                        className="cursor-pointer rounded p-1 hover:bg-background"
                                        title={section.isUnlocked ? 'Lock section' : 'Unlock section'}
                                    >
                                        {section.isUnlocked
                                            ? <Unlock className="size-4 text-green-500" />
                                            : <Lock className="size-4 text-muted-foreground" />
                                        }
                                    </button>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SectionsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Sections', href: sectionsIndex.url() },
    ],
};
