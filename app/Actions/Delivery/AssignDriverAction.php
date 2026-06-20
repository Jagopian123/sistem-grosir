<?php

declare(strict_types=1);

namespace App\Actions\Delivery;

use App\Enums\DeliveryStatus;
use App\Models\Penjualan;
use App\Models\Sopir;
use RuntimeException;

class AssignDriverAction
{
    public function execute(Penjualan $penjualan, Sopir $sopir): void
    {
        if ($penjualan->status_kirim !== DeliveryStatus::SiapKirim) {
            throw new RuntimeException('Hanya penjualan berstatus "Siap Kirim" yang dapat di-assign sopir.');
        }

        $penjualan->update([
            'sopir_id' => $sopir->id,
            'status_kirim' => DeliveryStatus::Dikirim,
        ]);
    }
}
