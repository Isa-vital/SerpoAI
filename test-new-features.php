<?php

/**
 * Test script for new SerpoAI features
 * Run: php test-new-features.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\RealSentimentService;
use App\Services\BlockchainMonitorService;
use App\Services\AnalyticsReportService;
use App\Services\MultiLanguageService;
use App\Services\OpenAIService;
use App\Models\SentimentData;
use App\Models\AIPrediction;
use App\Models\TransactionAlert;
use App\Models\AnalyticsReport;
use App\Models\HolderCelebration;

echo "🧪 Testing SerpoAI New Features\n";
echo "================================\n\n";

// Test 1: Database Tables
echo "1️⃣ Testing Database Tables...\n";
try {
    $tables = [
        'sentiment_data' => SentimentData::count(),
        'ai_predictions' => AIPrediction::count(),
        'transaction_alerts' => TransactionAlert::count(),
        'analytics_reports' => AnalyticsReport::count(),
        'holder_celebrations' => HolderCelebration::count(),
    ];

    foreach ($tables as $table => $count) {
        echo "   ✅ {$table}: {$count} records\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n\n";
}

// Test 2: Multi-Language Service
echo "2️⃣ Testing Multi-Language Service...\n";
try {
    $language = app(MultiLanguageService::class);

    $translations = [
        'price' => $language->translate('price', 'es'),
        'volume' => $language->translate('volume', 'ru'),
        'holders' => $language->translate('holders', 'zh'),
    ];

    foreach ($translations as $key => $value) {
        echo "   ✅ {$key} translated: {$value}\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Language error: " . $e->getMessage() . "\n\n";
}

// Test 3: OpenAI Service Extensions
echo "3️⃣ Testing OpenAI Service...\n";
try {
    $openai = app(OpenAIService::class);

    // Check if methods exist
    $methods = [
        'analyzeSentimentBatch',
        'generateMarketPrediction',
        'generatePersonalizedRecommendation',
        'processNaturalQuery'
    ];

    foreach ($methods as $method) {
        if (method_exists($openai, $method)) {
            echo "   ✅ Method exists: {$method}\n";
        } else {
            echo "   ❌ Method missing: {$method}\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ OpenAI error: " . $e->getMessage() . "\n\n";
}

// Test 4: Service Instantiation
echo "4️⃣ Testing Service Instantiation...\n";
try {
    $services = [
        'RealSentimentService' => RealSentimentService::class,
        'BlockchainMonitorService' => BlockchainMonitorService::class,
        'AnalyticsReportService' => AnalyticsReportService::class,
    ];

    foreach ($services as $name => $class) {
        try {
            $service = app($class);
            echo "   ✅ {$name} instantiated\n";
        } catch (\Exception $e) {
            echo "   ❌ {$name} failed: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Service error: " . $e->getMessage() . "\n\n";
}

// Test 5: Model Methods
echo "5️⃣ Testing Model Methods...\n";
try {
    $models = [
        'SentimentData' => ['getLatestSentiment', 'getAggregatedSentiment'],
        'AIPrediction' => ['getLatestPrediction', 'getAccuracyStats'],
        'TransactionAlert' => ['getWhaleTransactions', 'getNewHolderCount'],
        'AnalyticsReport' => ['getLatestReport', 'getReportsForPeriod'],
        'HolderCelebration' => ['createMilestone', 'getPendingCelebrations'],
    ];

    foreach ($models as $model => $methods) {
        $class = "App\\Models\\{$model}";
        foreach ($methods as $method) {
            if (method_exists($class, $method)) {
                echo "   ✅ {$model}::{$method}()\n";
            } else {
                echo "   ❌ {$model}::{$method}() missing\n";
            }
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Model error: " . $e->getMessage() . "\n\n";
}

// Test 6: Console Commands
echo "6️⃣ Testing Console Commands...\n";
try {
    $commands = [
        'reports:daily',
        'reports:weekly',
        'blockchain:monitor',
        'sentiment:analyze',
        'predictions:validate',
    ];

    $artisan = app(\Illuminate\Contracts\Console\Kernel::class);
    $allCommands = array_keys($artisan->all());

    foreach ($commands as $command) {
        if (in_array($command, $allCommands)) {
            echo "   ✅ Command registered: {$command}\n";
        } else {
            echo "   ⚠️  Command not found: {$command}\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Command error: " . $e->getMessage() . "\n\n";
}

// Test 7: Environment Variables
echo "7️⃣ Testing Environment Variables...\n";
$envVars = [
    'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
    'TELEGRAM_BOT_TOKEN' => env('TELEGRAM_BOT_TOKEN'),
    'TON_API_KEY' => env('TON_API_KEY'),
    'TWITTER_BEARER_TOKEN' => env('TWITTER_BEARER_TOKEN'),
    'REDDIT_CLIENT_ID' => env('REDDIT_CLIENT_ID'),
];

foreach ($envVars as $key => $value) {
    if ($value) {
        echo "   ✅ {$key} is set\n";
    } else {
        if (in_array($key, ['TWITTER_BEARER_TOKEN', 'REDDIT_CLIENT_ID', 'TON_API_KEY'])) {
            echo "   ⚠️  {$key} not set (optional)\n";
        } else {
            echo "   ❌ {$key} not set (required)\n";
        }
    }
}
echo "\n";

// Summary
echo "================================\n";
echo "✅ Test Complete!\n\n";
echo "Next Steps:\n";
echo "1. Test bot commands in Telegram\n";
echo "2. Run: php artisan reports:daily SERPO\n";
echo "3. Run: php artisan sentiment:analyze SERPO\n";
echo "4. Set up cron jobs for automation\n";
echo "5. Monitor logs: tail -f storage/logs/laravel.log\n";
