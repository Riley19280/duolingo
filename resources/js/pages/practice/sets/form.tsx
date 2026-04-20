import { Head, router, usePage } from '@inertiajs/react';
import { Lock, LockOpen, Plus, Search, X } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { index as practiceIndex } from '@/routes/practice';
import { store, update } from '@/routes/practice/sets';
import type { PracticeSet, Section, Word } from '@/types';

interface FormSection extends Section {
    words: Word[];
    isUnlocked: boolean;
}

interface Props {
    sections: FormSection[];
    practiceSet?: PracticeSet;
    [key: string]: unknown;
}

export default function PracticeSetForm() {
    const { sections, practiceSet } = usePage<Props>().props;
    const isEditing = !!practiceSet;

    const [name, setName] = useState(practiceSet?.name ?? '');
    const [selectedIds, setSelectedIds] = useState<Set<number>>(() => new Set(practiceSet?.wordIds ?? []));
    const [showLocked, setShowLocked] = useState(false);
    const [search, setSearch] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const allWordsById = useMemo(() => {
        const map = new Map<number, Word>();
        for (const section of sections) {
            for (const word of section.words) {
                map.set(word.id, word);
            }
        }
        return map;
    }, [sections]);

    const visibleSections = useMemo(
        () => (showLocked ? sections : sections.filter((s) => s.isUnlocked)),
        [sections, showLocked],
    );

    const sectionWordIds = useMemo(
        () => new Map(sections.map((s) => [s.id, s.words.map((w) => w.id)])),
        [sections],
    );

    const searchResults = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) {
            return [];
        }
        const results: Word[] = [];
        for (const [id, word] of allWordsById) {
            if (selectedIds.has(id)) {
                continue;
            }
            if (
                word.text.toLowerCase().includes(q) ||
                word.pinyin.toLowerCase().includes(q) ||
                word.translation?.toLowerCase().includes(q)
            ) {
                results.push(word);
                if (results.length >= 15) {
                    break;
                }
            }
        }
        return results;
    }, [search, allWordsById, selectedIds]);

    const selectedWords = useMemo(
        () => [...selectedIds].map((id) => allWordsById.get(id)).filter(Boolean) as Word[],
        [selectedIds, allWordsById],
    );

    const getSectionState = useCallback(
        (sectionId: number): 'none' | 'partial' | 'all' => {
            const ids = sectionWordIds.get(sectionId) ?? [];
            if (ids.length === 0) {
                return 'none';
            }
            const n = ids.filter((id) => selectedIds.has(id)).length;
            if (n === 0) {
                return 'none';
            }
            return n === ids.length ? 'all' : 'partial';
        },
        [sectionWordIds, selectedIds],
    );

    const toggleSection = useCallback(
        (sectionId: number) => {
            const ids = sectionWordIds.get(sectionId) ?? [];
            setSelectedIds((prev) => {
                const allSelected = ids.every((id) => prev.has(id));
                const next = new Set(prev);
                if (allSelected) {
                    ids.forEach((id) => next.delete(id));
                } else {
                    ids.forEach((id) => next.add(id));
                }
                return next;
            });
        },
        [sectionWordIds],
    );

    const addWord = useCallback((wordId: number) => {
        setSelectedIds((prev) => new Set(prev).add(wordId));
        setSearch('');
    }, []);

    const removeWord = useCallback((wordId: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.delete(wordId);
            return next;
        });
    }, []);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        const payload = { name, word_ids: [...selectedIds] };
        const options = {
            onError: (errs: Record<string, string>) => setErrors(errs),
            onFinish: () => setProcessing(false),
        };

        if (isEditing) {
            router.put(update.url(practiceSet.id), payload, options);
        } else {
            router.post(store.url(), payload, options);
        }
    }

    return (
        <>
            <Head title={isEditing ? 'Edit Practice Set' : 'New Practice Set'} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <form onSubmit={submit} className="flex flex-1 flex-col gap-4">
                    {/* Top bar */}
                    <div className="flex flex-col gap-2 md:flex-row md:items-start md:gap-3">
                        <div className="flex flex-1 flex-col gap-1">
                            <Input
                                id="name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="Practice set name"
                            />
                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            {errors.word_ids && <p className="text-sm text-destructive">{errors.word_ids}</p>}
                        </div>
                        <div className="flex justify-end gap-2">
                            <a
                                href={practiceIndex.url()}
                                className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                            >
                                Cancel
                            </a>
                            <button
                                type="submit"
                                disabled={processing}
                                className="cursor-pointer rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                {isEditing ? 'Save changes' : 'Create set'}
                            </button>
                        </div>
                    </div>

                    {/* Two-column */}
                    <div className="grid grid-cols-1 gap-4 md:min-h-0 md:flex-1 md:grid-cols-2">
                        {/* Left: sections */}
                        <Card className="flex min-h-0 flex-col max-h-[45vh] md:max-h-none">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center justify-between text-base">
                                    Sections
                                    <div className="flex cursor-pointer items-center gap-2 text-sm font-normal text-muted-foreground" onClick={() => setShowLocked((v) => !v)}>
                                        Show locked
                                        <span
                                            role="switch"
                                            aria-checked={showLocked}
                                            className={`relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors ${showLocked ? 'bg-primary' : 'bg-input'}`}
                                        >
                                            <span
                                                className={`inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform ${showLocked ? 'translate-x-4.75' : 'translate-x-0.75'}`}
                                            />
                                        </span>
                                    </div>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex-1 overflow-y-auto px-0 pb-2">
                                {visibleSections.length === 0 ? (
                                    <p className="px-6 text-sm text-muted-foreground">No unlocked sections yet.</p>
                                ) : (
                                    visibleSections.map((section) => {
                                        const state = getSectionState(section.id);
                                        const ids = sectionWordIds.get(section.id) ?? [];
                                        const selectedCount = ids.filter((id) => selectedIds.has(id)).length;

                                        return (
                                            <button
                                                key={section.id}
                                                type="button"
                                                onClick={() => toggleSection(section.id)}
                                                className={`flex w-full cursor-pointer items-center gap-3 px-6 py-2.5 text-left transition-colors hover:bg-accent ${state === 'all' ? 'bg-primary/10' : state === 'partial' ? 'bg-accent/60' : ''}`}
                                            >
                                                {section.isUnlocked ? (
                                                    <LockOpen className="size-4 shrink-0 text-green-600" />
                                                ) : (
                                                    <Lock className="size-4 shrink-0 text-muted-foreground" />
                                                )}
                                                <span className="flex-1 text-sm font-medium">{section.title}</span>
                                                {state !== 'none' ? (
                                                    <Badge variant={state === 'all' ? 'default' : 'secondary'}>
                                                        {selectedCount} / {ids.length}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">{ids.length}</span>
                                                )}
                                            </button>
                                        );
                                    })
                                )}
                            </CardContent>
                        </Card>

                        {/* Right: search + selected words */}
                        <Card className="flex min-h-0 flex-col max-h-[45vh] md:max-h-none">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">
                                    {selectedIds.size} {selectedIds.size === 1 ? 'word' : 'words'} selected
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex min-h-0 flex-1 flex-col gap-3 pb-3">
                                {/* Search */}
                                <div className="relative shrink-0">
                                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Add by character, pinyin, or English…"
                                        className="pl-9"
                                    />
                                </div>

                                {/* Search results */}
                                {searchResults.length > 0 && (
                                    <div className="shrink-0 overflow-hidden rounded-md border">
                                        {searchResults.map((word) => (
                                            <button
                                                key={word.id}
                                                type="button"
                                                onClick={() => addWord(word.id)}
                                                className="flex w-full cursor-pointer items-center justify-between px-4 py-2 text-left hover:bg-accent"
                                            >
                                                <Plus className="size-3.5 shrink-0 text-muted-foreground" />
                                                <span className="w-10 shrink-0 text-base font-bold">{word.text}</span>
                                                <span className="text-sm text-muted-foreground">{word.pinyin}</span>
                                                <span className="text-sm">{word.translation}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}

                                {search.trim() !== '' && searchResults.length === 0 && (
                                    <p className="shrink-0 text-sm text-muted-foreground">No results for "{search}"</p>
                                )}

                                {/* Selected words list */}
                                <div className="min-h-0 flex-1 overflow-y-auto rounded-md border">
                                    {selectedWords.length === 0 ? (
                                        <p className="p-4 text-sm text-muted-foreground">
                                            Click a section to add its words, or search above to add individually.
                                        </p>
                                    ) : (
                                        selectedWords.map((word) => (
                                            <div
                                                key={word.id}
                                                className="flex items-center justify-between px-4 py-2 hover:bg-accent"
                                            >
                                                <span className="w-10 shrink-0 text-base font-bold">{word.text}</span>
                                                <span className="text-sm text-muted-foreground">{word.pinyin}</span>
                                                <span className="text-sm">{word.translation}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => removeWord(word.id)}
                                                    className="cursor-pointer rounded p-1 hover:bg-background"
                                                >
                                                    <X className="size-4 text-muted-foreground" />
                                                </button>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </form>
            </div>
        </>
    );
}

PracticeSetForm.layout = {
    breadcrumbs: [
        { title: 'Practice', href: practiceIndex.url() },
        { title: 'Set', href: '#' },
    ],
};
