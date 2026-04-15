export interface Character {
    character: string;
}

export interface Word {
    id: number;
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
    words?: Word[];
}

export interface PracticeSet {
    id: number;
    name: string;
    wordIds: number[];
}
