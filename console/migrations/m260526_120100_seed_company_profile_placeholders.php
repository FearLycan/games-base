<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Local-dev seed: creates company_profile rows for every developer and publisher
 * that doesn't yet have one. Deterministic per-name so reruns are stable.
 *
 * Skipped on non-dev environments to avoid polluting real catalogs with
 * placeholder copy.
 */
class m260526_120100_seed_company_profile_placeholders extends Migration
{
    private const COUNTRIES = [
        ['Poland', 'Warsaw'], ['Poland', 'Kraków'], ['Poland', 'Wrocław'],
        ['United States', 'San Francisco'], ['United States', 'Austin'], ['United States', 'Seattle'],
        ['Japan', 'Tokyo'], ['Japan', 'Kyoto'], ['Japan', 'Osaka'],
        ['United Kingdom', 'London'], ['United Kingdom', 'Brighton'],
        ['Germany', 'Berlin'], ['Germany', 'Hamburg'],
        ['Sweden', 'Stockholm'], ['Sweden', 'Malmö'],
        ['France', 'Paris'], ['France', 'Lyon'],
        ['Canada', 'Montreal'], ['Canada', 'Vancouver'],
        ['Finland', 'Helsinki'],
        ['Netherlands', 'Amsterdam'],
        ['South Korea', 'Seoul'],
        ['Australia', 'Melbourne'],
        ['Czech Republic', 'Prague'],
        ['Ukraine', 'Kyiv'],
    ];

    private const DESCRIPTIONS = [
        'Small but ambitious studio with a sharp focus on player-driven systems and emergent storytelling.',
        'Veteran team of industry alumni building hand-crafted single-player experiences.',
        'Indie collective experimenting with art direction, mechanics and what games can feel like.',
        'Mid-size studio known for tight gameplay loops and uncompromising production values.',
        'Tools-first studio that builds its own tech so artists never have to compromise on a vision.',
        'A small remote team scattered across three continents, united by a love for tactics games.',
        'One of the most consistent quality bars in the industry, with a release every two to three years.',
        'Lean studio that doubles down on community feedback and ships in long, transparent early access.',
        'Boutique outfit curating distinctive single-player titles for a global audience.',
        'Mid-size house with a catalog leaning heavily into strategy and simulation.',
    ];

    private const HISTORY_INTROS = [
        'Founded in %d by a small group of friends out of a shared apartment, the studio shipped its first prototype within a year and signed a publishing deal shortly after.',
        'The company was registered in %d but the team had been working together informally for years on game jams and modding projects before going professional.',
        'Originally spun out of a larger studio in %d, the founders wanted to focus on smaller, more personal projects without the weight of a publisher schedule.',
        'Started in %d as a side project, the team only went full-time once their first title broke even - which took longer than anyone expected.',
    ];

    private const HISTORY_MIDDLES = [
        ' Their breakthrough came with a tight, well-reviewed release that built a loyal audience and gave the team enough runway to scale up carefully.',
        ' The early years were rough, with two cancelled projects and a near-collapse before a single release turned the company around.',
        ' Through patient iteration and a willingness to delay releases, they built a reputation for shipping polished games even when the schedule slipped.',
        ' A pivot from contract work to original IP took three years and a complete restructuring of the team.',
    ];

    private const HISTORY_OUTROS = [
        ' Today they continue to work on a mix of new IP and supporting older titles, with a focus on long-term player relationships rather than quick wins.',
        ' The studio remains independent and self-funded, with no plans to expand beyond a tight-knit core team.',
        ' Recent years have seen the team experiment with new genres while staying true to the design philosophy that made them recognisable in the first place.',
        ' Their current slate is the most ambitious yet, with multiple projects in parallel for the first time in the studio history.',
    ];

    public function safeUp()
    {
        if (YII_ENV !== 'dev') {
            echo "    > skipped: YII_ENV is '" . YII_ENV . "', placeholder seed only runs on dev\n";
            return;
        }

        $this->seedTable('{{%developer}}');
        $this->seedTable('{{%publisher}}');
    }

    public function safeDown()
    {
        if (YII_ENV !== 'dev') {
            return;
        }

        $devProfileIds = (new Query())
            ->select('profile_id')
            ->from('{{%developer}}')
            ->where(['not', ['profile_id' => null]])
            ->column($this->db);

        $pubProfileIds = (new Query())
            ->select('profile_id')
            ->from('{{%publisher}}')
            ->where(['not', ['profile_id' => null]])
            ->column($this->db);

        $this->update('{{%developer}}', ['profile_id' => null]);
        $this->update('{{%publisher}}', ['profile_id' => null]);

        $ids = array_unique(array_merge($devProfileIds, $pubProfileIds));
        if ($ids) {
            $this->delete('{{%company_profile}}', ['id' => $ids]);
        }
    }

    private function seedTable(string $table): void
    {
        $rows = (new Query())
            ->select(['id', 'name'])
            ->from($table)
            ->where(['profile_id' => null])
            ->all($this->db);

        $now = date('Y-m-d H:i:s');
        $descCount = count(self::DESCRIPTIONS);
        $countryCount = count(self::COUNTRIES);
        $introCount = count(self::HISTORY_INTROS);
        $midCount = count(self::HISTORY_MIDDLES);
        $outroCount = count(self::HISTORY_OUTROS);

        foreach ($rows as $row) {
            $name = (string)$row['name'];
            $seed = crc32($name);
            $country = self::COUNTRIES[$seed % $countryCount];
            $founded = 1995 + ($seed % 28);
            $closed = ($seed % 17 === 0) ? $founded + 5 + (($seed >> 4) % 12) : null;

            $handle = substr(preg_replace('/[^a-z0-9]/i', '', strtolower($name)), 0, 24);
            if ($handle === '') {
                $handle = 'studio' . $row['id'];
            }

            $history = sprintf(self::HISTORY_INTROS[$seed % $introCount], $founded)
                . self::HISTORY_MIDDLES[($seed >> 2) % $midCount]
                . self::HISTORY_OUTROS[($seed >> 4) % $outroCount];

            $this->insert('{{%company_profile}}', [
                'description'  => self::DESCRIPTIONS[$seed % $descCount],
                'history'      => $history,
                'logo_url'     => sprintf(
                    'https://placehold.co/240x240/0a0a0a/10b981?text=%s&font=lexend',
                    urlencode(mb_substr($name, 0, 2))
                ),
                'country'      => $country[0],
                'city'         => $country[1],
                'founded_year' => $founded,
                'closed_year'  => $closed,
                'website'      => 'https://example.com/' . $handle,
                'twitter'      => '@' . $handle,
                'discord'      => 'https://discord.gg/' . $handle,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            $profileId = $this->db->getLastInsertID();
            $this->update($table, ['profile_id' => $profileId], ['id' => $row['id']]);
        }
    }
}
