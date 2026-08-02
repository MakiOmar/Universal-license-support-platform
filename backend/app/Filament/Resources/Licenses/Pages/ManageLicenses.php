<?php

namespace App\Filament\Resources\Licenses\Pages;

use App\Filament\Resources\Licenses\LicenseResource;
use App\Models\Customer;
use App\Models\License;
use App\Models\PricingTier;
use App\Models\Product;
use App\Services\LicenseService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLicenses extends ManageRecords
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, LicenseService $licenseService): License {
                    $product = Product::query()->findOrFail($data['product_id']);
                    $customer = Customer::query()->findOrFail($data['customer_id']);
                    $tier = ! empty($data['pricing_tier_id'])
                        ? PricingTier::query()->find($data['pricing_tier_id'])
                        : null;

                    $options = [
                        'max_activations' => $data['max_activations'] ?? null,
                        'status' => $data['status'] ?? License::STATUS_ACTIVE,
                    ];

                    if (! empty($data['purchased_at'])) {
                        $options['purchased_at'] = $data['purchased_at'];
                    }

                    if (! empty($data['expires_at'])) {
                        $options['expires_at'] = $data['expires_at'];
                    }

                    if (! empty($data['support_expires_at'])) {
                        $options['support_expires_at'] = $data['support_expires_at'];
                    }

                    return $licenseService->issue($customer, $product, $tier, $options);
                })
                ->successNotificationTitle(fn (License $record): string => 'License created: '.$record->license_key),
        ];
    }
}
