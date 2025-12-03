<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RealSentimentService;
use Illuminate\Support\Facades\Log;

class AnalyzeSentiment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentiment:analyze {coin=SERPO}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze sentiment from social media sources';

    /**
     * Execute the console command.
     */
    public function handle(RealSentimentService $sentiment)
    {
        $coin = $this->argument('coin');

        $this->info("Analyzing sentiment for {$coin}...");

        try {
            $data = $sentiment->analyzeSentiment($coin);

            $this->info("✅ Sentiment analysis completed!");
            $this->line("Overall Sentiment: {$data['overall_sentiment']}");
            $this->line("Score: {$data['overall_score']}/100");
            $this->line("Positive: {$data['positive_count']}");
            $this->line("Negative: {$data['negative_count']}");
            $this->line("Neutral: {$data['neutral_count']}");

            if (!empty($data['trending_keywords'])) {
                $this->line("Trending: " . implode(', ', array_slice($data['trending_keywords'], 0, 5)));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error analyzing sentiment: " . $e->getMessage());
            Log::error('Sentiment analysis failed', [
                'coin' => $coin,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
