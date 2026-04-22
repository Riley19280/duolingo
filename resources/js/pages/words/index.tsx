import { Head, router, usePage } from '@inertiajs/react';
import { Lock, Search, Unlock } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { destroy as destroyNote, update as updateNote } from '@/routes/notes';
import { index as wordsIndex } from '@/routes/words';
import { store as storeNote } from '@/routes/words/notes';
import type { Word as BaseWord } from '@/types';

type Word = BaseWord & { isAvailable: boolean };

interface Note {
    id: number;
    wordId: number;
    content: string;
}

interface Props {
    words: Word[];
    notes: Note[];
    [key: string]: unknown;
}

export default function WordsIndex() {
    const { words, notes } = usePage<Props>().props;
    const [search, setSearch] = useState('');
    const [unlockedOnly, setUnlockedOnly] = useState(true);
    const [selected, setSelected] = useState<Word | null>(words[0] ?? null);
    const [noteContent, setNoteContent] = useState(
        () => notes.find((n) => n.wordId === words[0]?.id)?.content ?? '',
    );
    const saveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const existingNoteRef = useRef<Note | null>(null);
    const selectedRef = useRef(selected);

    const existingNote = notes.find((n) => n.wordId === selected?.id) ?? null;
    existingNoteRef.current = existingNote;
    selectedRef.current = selected;

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();

        return words.filter((w) => {
            if (unlockedOnly && !w.isAvailable) return false;
            if (!q) return true;

            return (
                w.text.toLowerCase().includes(q) ||
                w.pinyin.toLowerCase().includes(q) ||
                (w.translation?.toLowerCase().includes(q) ?? false)
            );
        });
    }, [words, search, unlockedOnly]);

    useEffect(() => {
        const note = notes.find((n) => n.wordId === selected?.id) ?? null;
        setNoteContent(note?.content ?? '');

        if (saveTimerRef.current) {
            clearTimeout(saveTimerRef.current);
            saveTimerRef.current = null;
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected?.id]);

    useEffect(() => {
        return () => {
            if (saveTimerRef.current) {
                clearTimeout(saveTimerRef.current);
            }
        };
    }, []);

    function selectWord(word: Word) {
        if (saveTimerRef.current) {
            clearTimeout(saveTimerRef.current);
            saveTimerRef.current = null;
        }

        setSelected(word);

        if (word.ttsUrl) {
            new Audio(word.ttsUrl).play();
        }
    }

    function handleNoteChange(value: string) {
        setNoteContent(value);

        if (saveTimerRef.current) {
            clearTimeout(saveTimerRef.current);
        }

        saveTimerRef.current = setTimeout(() => {
            const word = selectedRef.current;
            const note = existingNoteRef.current;

            if (note) {
                if (value.trim()) {
                    router.patch(updateNote.url(note.id), { content: value.trim() }, { preserveScroll: true });
                } else {
                    router.delete(destroyNote.url(note.id), { preserveScroll: true });
                }
            } else if (value.trim() && word) {
                router.post(storeNote.url(word.id), { content: value.trim() }, { preserveScroll: true });
            }
        }, 800);
    }

    return (
        <>
            <Head title="Words" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Selected word display */}
                <div
                    className={`flex flex-col items-center gap-2 py-4 ${selected?.ttsUrl ? 'cursor-pointer' : ''}`}
                    onClick={() => selected?.ttsUrl && new Audio(selected.ttsUrl).play()}
                >
                    <span className="text-8xl font-bold">{selected?.text ?? '—'}</span>
                    <span className="text-lg text-muted-foreground">{selected?.pinyin}</span>
                    <span className="text-base">{selected?.translation}</span>
                </div>

                {/* Note */}
                {selected && (
                    <textarea
                        value={noteContent}
                        onChange={(e) => handleNoteChange(e.target.value)}
                        placeholder="Add a note…"
                        rows={2}
                        className="border-input placeholder:text-muted-foreground focus-visible:ring-ring/50 w-full resize-none rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px]"
                    />
                )}

                {/* Word list */}
                <Card className="flex min-h-0 flex-1 flex-col">
                    <CardHeader className="pb-3">
                        <div className="flex items-center gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder={`Search ${words.length} words…`}
                                    className="pl-9"
                                />
                            </div>
                            <button
                                type="button"
                                onClick={() => setUnlockedOnly((v) => !v)}
                                title={unlockedOnly ? 'Showing unlocked only' : 'Showing all words'}
                                className="shrink-0 cursor-pointer rounded p-1 hover:bg-accent"
                            >
                                {unlockedOnly
                                    ? <Unlock className="size-4 text-green-500" />
                                    : <Lock className="size-4 text-muted-foreground" />}
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent className="min-h-0 flex-1 overflow-y-auto px-0 pb-2">
                        {filtered.length === 0 ? (
                            <p className="px-6 py-4 text-sm text-muted-foreground">No words found.</p>
                        ) : (
                            filtered.map((word) => (
                                <div
                                    key={word.id}
                                    onClick={() => selectWord(word)}
                                    className={`flex cursor-pointer items-center gap-4 px-6 py-2 transition-colors hover:bg-accent ${selected?.id === word.id ? 'bg-primary/10' : ''}`}
                                >
                                    <span className="w-1/4 shrink-0 text-xl font-bold">{word.text}</span>
                                    <span className="w-1/4 shrink-0 text-sm text-muted-foreground">{word.pinyin}</span>
                                    <span className="min-w-0 flex-1 truncate text-right text-sm">{word.translation}</span>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

WordsIndex.layout = {
    breadcrumbs: [{ title: 'Words', href: wordsIndex.url() }],
};
