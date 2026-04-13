<?php

namespace App\Console\Commands;

use App\Library\DuolingoApi;
use App\Library\DuolingoImporter;
use App\Models\Character;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('duolingo:fetch-characters')]
#[Description('Recursively fetch and process character data from the Duolingo API until all characters have stroke data')]
class FetchAllDuolingoCharactersCommand extends Command
{
    public function handle(DuolingoApi $api, DuolingoImporter $importer): int
    {
        if (! $api->isConfigured()) {
            $this->error('DUOLINGO_JWT and DUOLINGO_ALPHABETS_KEY must be set in your .env file.');

            return self::FAILURE;
        }

        $user = User::first();

        if (! $user) {
            $this->error('No users found — run the user seeder first.');

            return self::FAILURE;
        }

        $round = 0;

        $ignore = $this->ignoreList();

        do {
            $characters = Character::all()->filter(
                fn (Character $c) => ! File::exists(storage_path("app/raw/{$c->character}.json"))
            );

            $round++;
            $fetched = 0;

            $this->info("Round {$round}: {$characters->count()} character(s) without stroke data.");

            if ($characters->isEmpty()) {
                break;
            }

            foreach ($characters as $character) {
                if (in_array($character->character, $ignore)) {
                    continue;
                }

                try {
                    $isNew = $api->fetchCharacterIfNeeded($character->character);
                } catch (\RuntimeException $e) {
                    $this->warn("  {$e->getMessage()}");

                    $ignore[] = $character->character;

                    continue;
                }

                if (! $isNew) {
                    continue;
                }

                $this->line("  Fetched <fg=cyan>{$character->character}</>");

                $data = json_decode(File::get(storage_path("app/raw/{$character->character}.json")), associative: true);
                $importer->processResponseBody($data, $user);
                $fetched++;
            }

            $this->line("  Processed {$fetched} new file(s).");
            $this->newLine();
            sleep(5);
        } while ($fetched > 0);

        $this->info('Done — all known characters have files on disk.');

        $this->call(FetchDuolingoTTS::class);

        return self::SUCCESS;
    }

    public function ignoreList(): array
    {
        return [
            '喱',
            '花',
            '玉',
            '夜',
            '闹',
            '泰',
            '庆',
            '际',
            '餐',
            '笔',
            '剧',
            '算',
            '吓',
            '雄',
            '化',
            '夹',
            '章',
            '究',
            '卫',
            '维',
            '素',
            '陌',
            '程',
            '设',
            '计',
            '派',
            '象',
            '绝',
            '反',
            '惊',
            '财',
            '临',
            '澳',
            '部',
            '队',
            '器',
            '帽',
            '筷',
            '袜',
            '靴',
            '箱',
            '梳',
            '柜',
            '狮',
            '脖',
            '鸭',
            '邮',
            '笼',
            '蚊',
            '许',
            '装',
            '秘',
            '博',
            '青',
            '谱',
            '酱',
            '芒',
            '效',
            '坚',
            '睛',
            '液',
            '达',
            '卷',
            '港',
            '蕉',
            '肠',
            '槟',
            '售',
            'atm',
            '壳',
            '由',
            '虾',
            '馒',
            '盔',
            '鹰',
            '摄',
            '晕',
            '酸',
            '柿',
            '载',
            '扰',
            '般',
            '轻',
            '龄',
            '童',
            '底',
            '侣',
            '况',
            '坡',
            '呐',
            '改',
            '赋',
            '摩',
            '轮',
            '忘',
            '套',
            '户',
            '郊',
            '另',
            '具',
            '搬',
            '庭',
            '艺',
            '诗',
            '细',
            '诞',
            '恶',
            '性',
            '传',
            '简',
            '农',
            '副',
            '宝',
            '扇',
            '筒',
            '咱',
            '棒',
            '除',
            '啊',
            '幸',
            '处',
            '坞',
            '奋',
            '趣',
            '福',
            '贺',
            '奥',
            '托',
            '讨',
            '软',
            '灵',
            '宠',
            '植',
            '购',
            '创',
            '案',
            '仪',
            '验',
            '诚',
            '确',
            '滑',
            '标',
            '积',
            '纽',
            '频',
            '团',
            '伦',
            '赵',
            '组',
            '偷',
            '麦',
            '丑',
            '突',
            '显',
            '授',
            '顶',
            '航',
            '阁',
            '管',
            '免',
            '阳',
            '擅',
            '连',
            '顺',
            '滩',
            '贴',
            '胃',
            '递',
            '赶',
            '众',
            '精',
            '率',
            '营',
            '养',
            '优',
            '击',
            '键',
            '仔',
            '扑',
            '墨',
            '背',
            '裹',
            '鬼',
            '祸',
            '库',
            '寿',
            '卧',
            '置',
            '线',
            '曲',
            '之',
            '芝',
            '敏',
            '冒',
            '随',
            '启',
            '战',
            '账',
            '链',
            '烤',
            '类',
            '莓',
            '印',
            '碎',
            '锅',
            '按',
            '排',
            '推',
            '答',
            '皇',
            '谅',
            '罗',
            '藏',
            '钻',
            '朗',
            '睁',
            '灰',
            '棕',
            '蜜',
            '榜',
            '播',
            '齐',
            '储',
            '蓄',
            '诊',
            '输',
            '附',
            '令',
            '孤',
            '立',
            '翻',
            '译',
            '辑',
            '冥',
            '念',
            '签',
            '宿',
            '序',
            '悲',
            '塘',
            '领',
            '志',
            '济',
            '舱',
            '曾',
            '毕',
            '产',
            '妆',
            '适',
            '弄',
            '释',
            '迹',
            '乒',
            '乓',
            '宫',
            '险',
            '统',
            '训',
            '杂',
            '薯',
            '恐',
            '垫',
            '建',
            '沟',
            '抱',
            '延',
            '较',
            '尔',
            '搞',
            '微',
            '份',
            '座',
            '态',
            '扬',
            '雕',
            '圾',
            '集',
            '华',
            '糖',
            '庙',
            '噩',
            '幕',
            '将',
            '肤',
            '步',
            '驾',
            '虑',
            '翅',
            '邀',
            '羞',
            '杭',
            '糟',
            '拖',
            '冻',
            '萝',
            '贷',
            '肃',
            '胶',
        ];
    }
}
