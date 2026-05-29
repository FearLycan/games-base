<?php

namespace common\components;

use common\models\CompanyProfile;
use common\models\Developer;
use common\models\Publisher;

/**
 * Upserts a {{%company_profile}} row from a plain import record and attaches it
 * to the matching developer or publisher. Because Developer/Publisher fire
 * {@see \common\models\CompanyProfileSync} on save, attaching a profile to a
 * developer also propagates it to a same-named publisher (and vice versa).
 *
 * The import row is treated as the source of truth: every field present in the
 * row is written (an explicit null clears the column); fields omitted from the
 * row are left untouched.
 */
class CompanyProfileImporter
{
    /** Row keys copied verbatim onto the profile (after string trimming). */
    private const FIELDS = [
        'description', 'history', 'logo_url',
        'country', 'city', 'founded_year', 'closed_year',
        'website', 'discord',
    ];

    /**
     * @param array<string,mixed>            $row
     * @param class-string<Developer|Publisher> $modelClass
     * @return array{action:'created'|'updated'|'skipped', name:string, error?:string}
     */
    public static function import(array $row, string $modelClass = Developer::class): array
    {
        $kind = $modelClass === Publisher::class ? 'publisher' : 'developer';

        // Match by primary key — the id is the concrete id from our own system.
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        if ($id <= 0) {
            return ['action' => 'skipped', 'name' => (string)($row['name'] ?? '(no id)'), 'error' => 'missing id'];
        }

        $model = $modelClass::findOne($id);
        if (!$model) {
            return ['action' => 'skipped', 'name' => (string)($row['name'] ?? $id), 'error' => "no {$kind} with id {$id}"];
        }
        $name = $model->name;

        $profile = $model->profile_id ? CompanyProfile::findOne($model->profile_id) : null;
        $isNew = $profile === null;
        $profile ??= new CompanyProfile();

        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $row)) {
                $value = $row[$field];
                $profile->$field = is_string($value) ? (trim($value) ?: null) : $value;
            }
        }
        if (array_key_exists('twitter', $row)) {
            $profile->twitter = self::twitterHandle($row['twitter']);
        }

        if (!$profile->save()) {
            $errors = implode('; ', array_map(
                static fn($attr, $msgs) => $attr . ': ' . implode(', ', $msgs),
                array_keys($profile->getErrors()),
                $profile->getErrors(),
            ));
            return ['action' => 'skipped', 'name' => $name, 'error' => 'validation — ' . $errors];
        }

        if ($isNew) {
            // save() (not updateAttributes) so CompanyProfileSync fires and links
            // the same-named counterpart in the other table.
            $model->profile_id = $profile->id;
            $model->save(false);
            return ['action' => 'created', 'name' => $name];
        }

        return ['action' => 'updated', 'name' => $name];
    }

    /**
     * Normalizes a Twitter/X value to a bare handle. The profile view builds the
     * URL itself (https://twitter.com/<handle>), so a full URL stored here would
     * produce a broken link. Accepts "@handle", "handle", or any twitter.com/x.com URL.
     */
    public static function twitterHandle(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if (preg_match('~(?:twitter\.com|x\.com)/([^/?#]+)~i', $value, $m)) {
            $value = $m[1];
        }
        $value = ltrim($value, '@');

        return $value !== '' ? $value : null;
    }
}
