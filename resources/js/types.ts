export interface Character {
    character: string;
}

export interface Word {
    text: string;
    pinyin: string;
    translation: string | null;
    ttsUrl: string | null;
    isAvailable?: boolean;
}

export interface Section {
    id: number;
    title: string;
    sectionNumber: number;
    unitNumber: number;
    wordsCount?: number;
    isUnlocked?: boolean;
}
