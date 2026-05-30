<?php

namespace backend\models;

/**
 * Backend-facing Game model. Thin subclass of the shared catalogue model so the
 * admin module owns its own model namespace (the frontend will diverge with its
 * own models) while reusing the common table mapping, rules and relations.
 */
class Game extends \common\models\Game
{
}
