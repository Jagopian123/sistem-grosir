<?php

declare(strict_types=1);

namespace App\Actions\Delivery;

use App\Enums\DeliveryStatus;
use App\Models\Penjualan;
use RuntimeException;

class MarkDeliveredAction
{
    public function execute(Penjualan $penjualan): void
    {
        if ($penjualan->status_kirim !== DeliveryStatus::Dikirim) {
            throw new RuntimeException('Hanya penjualan berstatus "Dikirim" yang dapat ditandai terkirim.');
        }

        $penjualan->update([
            'status_kirim' => DeliveryStatus::Terkirim,
        ]);
    }
}
