<?php

namespace App\Http\Requests\Api\V1\License\Concerns;

use Illuminate\Support\Facades\Log;

trait NormalizesDeviceMeta
{
    /**
     * Map common client aliases into canonical snake_case fields and log what arrived.
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->all();

        $aliases = [
            'device_name' => ['device_name', 'deviceName', 'device_model', 'deviceModel', 'model'],
            'platform' => ['platform', 'os', 'operating_system', 'operatingSystem'],
            'app_version' => ['app_version', 'appVersion', 'version'],
        ];

        $normalized = [];

        foreach ($aliases as $canonical => $keys) {
            foreach ($keys as $key) {
                $value = data_get($raw, $key);

                if (is_string($value) && trim($value) !== '') {
                    $normalized[$canonical] = trim($value);
                    break;
                }
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }

        // Temporary diagnosis: what the mobile app actually posts for device meta.
        Log::info('ulsp.license.device_meta', [
            'path' => '/'.$this->path(),
            'method' => $this->method(),
            'content_type' => $this->header('Content-Type'),
            'raw_keys' => array_keys($raw),
            'raw' => [
                'license_key' => $raw['license_key'] ?? null,
                'activation_type' => $raw['activation_type'] ?? ($raw['activationType'] ?? null),
                'activation_value' => $raw['activation_value'] ?? ($raw['activationValue'] ?? null),
                'device_name' => $raw['device_name'] ?? null,
                'deviceName' => $raw['deviceName'] ?? null,
                'device_model' => $raw['device_model'] ?? null,
                'platform' => $raw['platform'] ?? null,
                'os' => $raw['os'] ?? null,
                'app_version' => $raw['app_version'] ?? null,
                'appVersion' => $raw['appVersion'] ?? null,
                'version' => $raw['version'] ?? null,
            ],
            'normalized' => [
                'device_name' => $this->input('device_name'),
                'platform' => $this->input('platform'),
                'app_version' => $this->input('app_version'),
            ],
            'meta_present' => $normalized !== [],
        ]);
    }
}
