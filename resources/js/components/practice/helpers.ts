import type { Word } from '@/types';

export function getCorrectAnswer(word: Word, answerForm: string): string {
    if (answerForm === 'character') return word.text;
    if (answerForm === 'pinyin') return word.pinyin;
    return word.translation ?? word.pinyin;
}

export function getQuestionText(word: Word, questionForm: string): string | null {
    if (questionForm === 'character') return word.text;
    if (questionForm === 'pinyin') return word.pinyin;
    if (questionForm === 'english') return word.translation ?? word.pinyin;
    return null; // audio
}

export function stripDiacritics(s: string): string {
    return s
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

export function checkAnswer(given: string, correct: string, answerForm: string): boolean {
    const g = given.trim().toLowerCase();
    const c = correct.trim().toLowerCase();
    if (g === c) return true;
    if (answerForm === 'pinyin') {
        return stripDiacritics(given) === stripDiacritics(correct);
    }
    return false;
}

export function generateMCOptions(word: Word, allWords: Word[], answerForm: string): string[] {
    const correct = getCorrectAnswer(word, answerForm);
    const pool = allWords
        .filter((w) => w.id !== word.id)
        .map((w) => getCorrectAnswer(w, answerForm))
        .filter((ans) => ans !== correct)
        .sort(() => Math.random() - 0.5)
        .slice(0, 3);
    return [...pool, correct].sort(() => Math.random() - 0.5);
}
