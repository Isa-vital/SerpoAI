<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AIPrediction;
use App\Services\MarketDataService;
use Illuminate\Support\Facades\Log;

class ValidatePredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate AI predictions and calculate accuracy';

    /**
     * Execute the console command.
     */
    public function handle(MarketDataService $marketData)
    {
        $this->info("Validating AI predictions...");

        try {
            // Get predictions that need validation (prediction_for date has passed)
            $predictions = AIPrediction::where('validated', false)
                ->where('prediction_for', '<=', now())
                ->get();

            if ($predictions->isEmpty()) {
                $this->info("No predictions to validate.");
                return 0;
            }

            $validated = 0;

            foreach ($predictions as $prediction) {
                $this->line("Validating prediction #{$prediction->id} for {$prediction->coin_symbol}...");

                // Get actual price - use appropriate source based on symbol
                $actualData = $marketData->getTokenPriceFromDex();
                $actualPrice = $actualData['price'];

                $prediction->validatePrediction($actualPrice);
                $validated++;

                $accuracy = $prediction->accuracy_percentage;
                $emoji = $accuracy >= 90 ? '🎯' : ($accuracy >= 70 ? '✅' : '⚠️');
                $this->line("{$emoji} Accuracy: {$accuracy}%");
            }

            // Get overall accuracy stats
            $stats = AIPrediction::getAccuracyStats($predictions->first()->coin_symbol ?? 'BTC');

            $this->info("\n✅ Validation completed!");
            $this->line("Predictions validated: {$validated}");
            $this->line("Overall accuracy: {$stats['avg_accuracy']}%");
            $this->line("Total predictions: {$stats['total']}");

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error validating predictions: " . $e->getMessage());
            Log::error('Prediction validation failed', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
