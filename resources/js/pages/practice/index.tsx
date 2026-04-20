import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Play, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as practiceIndex } from '@/routes/practice';
import { store as storeSession } from '@/routes/practice/sessions/index';
import { create, destroy as destroySet, edit } from '@/routes/practice/sets';

interface PracticeSet {
    id: number;
    name: string;
    wordsCount: number;
}

interface PracticeSessionRow {
    id: number;
    setName: string | null;
    exerciseType: string;
    questionForm: string;
    answerForm: string;
    attemptsCount: number;
    correctCount: number;
    completedAt: string;
}

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    sets: PracticeSet[];
    sessions: PracticeSessionRow[];
    exerciseTypes: EnumOption[];
    questionForms: EnumOption[];
    answerForms: EnumOption[];
    [key: string]: unknown;
}

function RadioGroup({
    label,
    options,
    value,
    onChange,
}: {
    label: string;
    options: EnumOption[];
    value: string;
    onChange: (v: string) => void;
}) {
    return (
        <div className="flex flex-col gap-2">
            <span className="text-sm font-medium">{label}</span>
            <div className="flex flex-wrap gap-2">
                {options.map((opt) => (
                    <label
                        key={opt.value}
                        className={`flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors ${
                            value === opt.value
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-accent'
                        }`}
                    >
                        <input
                            type="radio"
                            name={label}
                            value={opt.value}
                            checked={value === opt.value}
                            onChange={() => onChange(opt.value)}
                            className="sr-only"
                        />
                        {opt.label}
                    </label>
                ))}
            </div>
        </div>
    );
}

export default function PracticeIndex() {
    const { sets, sessions, exerciseTypes, questionForms, answerForms } = usePage<Props>().props;

    const [selectedSetId, setSelectedSetId] = useState<number | null>(sets[0]?.id ?? null);
    const [exerciseType, setExerciseType] = useState(exerciseTypes[0]?.value ?? '');
    const [questionForm, setQuestionForm] = useState(questionForms[0]?.value ?? '');
    const [answerForm, setAnswerForm] = useState(answerForms[0]?.value ?? '');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function deleteSet(id: number) {
        if (!confirm('Delete this practice set?')) {
            return;
        }
        router.delete(destroySet.url(id));
    }

    function startPractice() {
        if (!selectedSetId) {
            return;
        }
        setErrors({});
        setProcessing(true);
        router.post(
            storeSession.url(),
            {
                practice_set_id: selectedSetId,
                exercise_type: exerciseType,
                question_form: questionForm,
                answer_form: answerForm,
            },
            {
                onError: (errs) => setErrors(errs),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <>
            <Head title="Practice" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {/* Sets list */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                Practice Sets
                                <Link
                                    href={create.url()}
                                    className="flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                >
                                    New Set
                                </Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-0 pb-2">
                            {sets.length === 0 ? (
                                <p className="px-6 text-sm text-muted-foreground">
                                    No practice sets yet. Create one to get started.
                                </p>
                            ) : (
                                sets.map((set) => (
                                    <div
                                        key={set.id}
                                        onClick={() => setSelectedSetId(set.id)}
                                        className={`flex cursor-pointer items-center gap-3 px-6 py-2.5 transition-colors hover:bg-accent ${selectedSetId === set.id ? 'bg-primary/10' : ''}`}
                                    >
                                        <div className="flex flex-1 flex-col min-w-0">
                                            <span className="font-medium truncate">{set.name}</span>
                                            <span className="text-xs text-muted-foreground">{set.wordsCount} words</span>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-1">
                                            <Link
                                                href={edit.url(set.id)}
                                                onClick={(e) => e.stopPropagation()}
                                                className="rounded p-1.5 hover:bg-background"
                                                title="Edit set"
                                            >
                                                <Pencil className="size-4 text-muted-foreground" />
                                            </Link>
                                            <button
                                                onClick={(e) => { e.stopPropagation(); deleteSet(set.id); }}
                                                className="cursor-pointer rounded p-1.5 hover:bg-background"
                                                title="Delete set"
                                            >
                                                <Trash2 className="size-4 text-muted-foreground" />
                                            </button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    {/* Start practice config */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Configure Practice</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-5">
                            {sets.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Create a practice set first.</p>
                            ) : (
                                <>
                                    <RadioGroup
                                        label="Exercise Type"
                                        options={exerciseTypes}
                                        value={exerciseType}
                                        onChange={setExerciseType}
                                    />
                                    <RadioGroup
                                        label="Question"
                                        options={questionForms}
                                        value={questionForm}
                                        onChange={setQuestionForm}
                                    />
                                    <RadioGroup
                                        label="Answer"
                                        options={answerForms}
                                        value={answerForm}
                                        onChange={setAnswerForm}
                                    />
                                    {Object.keys(errors).length > 0 && (
                                        <p className="text-sm text-destructive">
                                            {Object.values(errors)[0]}
                                        </p>
                                    )}
                                    <div className="flex flex-wrap items-center justify-between gap-2 pt-1">
                                        <span className="text-sm text-muted-foreground">
                                            {selectedSetId
                                                ? `${sets.find((s) => s.id === selectedSetId)?.name}`
                                                : 'Select a set on the left'}
                                        </span>
                                        <button
                                            onClick={startPractice}
                                            disabled={processing || !selectedSetId}
                                            className="flex cursor-pointer items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                        >
                                            <Play className="size-4" />
                                            Start Practice
                                        </button>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Practice history */}
                {sessions.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>History</CardTitle>
                        </CardHeader>
                        <CardContent className="px-0 pb-0">
                            <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        <th className="px-6 py-2">Date</th>
                                        <th className="px-6 py-2">Set</th>
                                        <th className="px-6 py-2">Type</th>
                                        <th className="px-6 py-2">Question → Answer</th>
                                        <th className="px-6 py-2 text-right">Score</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {sessions.map((s) => {
                                        const pct = s.attemptsCount > 0 ? Math.round((s.correctCount / s.attemptsCount) * 100) : 0;
                                        const scoreColor = pct >= 80 ? 'text-green-600' : pct >= 50 ? 'text-yellow-600' : 'text-red-500';
                                        return (
                                            <tr key={s.id} className="hover:bg-accent/50">
                                                <td className="px-6 py-2.5 text-muted-foreground">
                                                    {new Date(s.completedAt).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-2.5 font-medium">{s.setName ?? '—'}</td>
                                                <td className="px-6 py-2.5">{s.exerciseType}</td>
                                                <td className="px-6 py-2.5 text-muted-foreground">
                                                    {s.questionForm} → {s.answerForm}
                                                </td>
                                                <td className={`px-6 py-2.5 text-right font-semibold tabular-nums ${scoreColor}`}>
                                                    {s.correctCount}/{s.attemptsCount}
                                                    <span className="ml-1.5 font-normal text-muted-foreground">({pct}%)</span>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

PracticeIndex.layout = {
    breadcrumbs: [{ title: 'Practice', href: practiceIndex.url() }],
};
