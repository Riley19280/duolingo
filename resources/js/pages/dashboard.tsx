import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { WordGrid } from '@/components/word-grid'
import { dashboard } from '@/routes'
import { show as characterShow } from '@/routes/characters'
import { Character, Word } from '@/types'
import {
    Head,
    Link,
    usePage,
} from '@inertiajs/react'

interface Props {
    uniqueWords: number;
    uniqueCharacters: number;
    availableWords: number;
    lockedWords: number;
    sectionsCovered: number;
    totalSections: number;
    userCharacters: Character[];
    availableWordList: Word[];

    [key: string]: unknown;
}

export default function Dashboard() {
    const {
        uniqueWords,
        uniqueCharacters,
        availableWords,
        lockedWords,
        sectionsCovered,
        totalSections,
        userCharacters,
        availableWordList,
    } = usePage<Props>().props

    const total = availableWords + lockedWords
    const availablePct = total > 0 ? Math.round((availableWords / total) * 100) : 0

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                {/* Summary stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Sections covered
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {sectionsCovered.toLocaleString()}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                of {totalSections} sections
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Sections to go
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {(
                                    totalSections - sectionsCovered
                                ).toLocaleString()}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                of {totalSections} sections
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {/* User's characters */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                My Characters
                                <span className="text-sm font-normal text-muted-foreground">
                                    {userCharacters.length} / {uniqueCharacters}{' '}
                                    (
                                    {Math.round(
                                        (userCharacters.length /
                                            uniqueCharacters) *
                                            100,
                                    )}
                                    %)
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex max-h-96 flex-wrap gap-2 overflow-y-auto">
                                {userCharacters.map(({ character }) => (
                                    <Link
                                        key={character}
                                        href={characterShow.url(character)}
                                        className="flex items-center justify-center rounded-lg border px-3 py-2 text-2xl font-bold hover:bg-accent"
                                    >
                                        {character}
                                    </Link>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Available words */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                My Words
                                <span className="text-sm font-normal text-muted-foreground">
                                    {availableWordList.length} / {uniqueWords} (
                                    {availablePct}%)
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="max-h-96 overflow-y-auto">
                                <WordGrid words={availableWordList} />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
}
