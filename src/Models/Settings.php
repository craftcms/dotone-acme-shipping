<?php

declare(strict_types=1);

namespace Acme\Shipping\Models;

use CraftCms\Cms\Plugin\PluginSettings;

class Settings extends PluginSettings
{
    public ?string $apiKey = null;

    public bool $testMode = true;

    /** A string so it can hold an env var reference such as `$ACME_SIGNATURE`. */
    public bool|string $requireSignature = false;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'apiKey' => ['nullable', 'string'],
            'testMode' => ['boolean'],
            'requireSignature' => ['nullable'],
        ];
    }
}
