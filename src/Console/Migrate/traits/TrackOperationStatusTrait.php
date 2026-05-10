<?php

namespace Teksite\Module\Console\Migrate\traits;

trait TrackOperationStatusTrait
{
    public bool $multistage= true;
    /**
     * Advanced statistics collector
     */
    public array $operationStats = [
        'total_operations' => 0,
        'successful_operations' => 0,
        'failed_operations' => 0,
        'operations_details' => [],
    ];

    /**
     * Reset advanced statistics
     */
    public function resetAdvancedStats(): void
    {
        $this->operationStats = [
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'operations_details' => [],
        ];
    }

    /**
     * Track an operation result
     */

    public function addTotalFailure(array $operationStats): void
    {

    }


    public function hasMultistage(): bool{
        return $this->multistage;
    }
}
