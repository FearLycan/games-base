<?php

namespace common\schema\factory;

use Yii;

final class OrganizationSchemaFactory
{
    public static function fromParams(): array
    {
        $name = trim((string)(Yii::$app->params['schema.organization.name'] ?? Yii::$app->name));
        $url = trim((string)(Yii::$app->params['schema.organization.url'] ?? ''));
        $logo = trim((string)(Yii::$app->params['schema.organization.logo'] ?? ''));

        $schema = [
            '@type' => 'Organization',
            '@id'   => '#organization',
            'name'  => $name !== '' ? $name : 'Gamentator',
        ];

        if ($url !== '') {
            $schema['url'] = $url;
        }

        if ($logo !== '') {
            $schema['logo'] = $logo;
        }

        return $schema;
    }
}
