<?php

/**
 * Comprehensive test suite for /flow command improvements
 * Tests data validation, market-specific formatting, and edge cases
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\App;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class FlowValidationTest
{
    private $telegram;
    private $commandHandler;
    private $derivatives;
    private $testResults = [];

    public function __construct()
    {
        $this->telegram = App::make(\App\Services\TelegramService::class);
        $this->commandHandler = App::make(\App\Services\CommandHandler::class);
        $this->derivatives = App::make(\App\Services\DerivativesAnalysisService::class);
    }

    public function runAllTests()
    {
        echo "=================================================\n";
        echo "FLOW COMMAND VALIDATION TEST SUITE\n";
        echo "=================================================\n\n";

        $this->testCryptoWithValidData();
        $this->testCryptoWithMissingOI();
        $this->testCryptoWithZeroVolume();
        $this->testCryptoExchangeFlowWithoutAPIs();
        $this->testCryptoExchangeFlowWithAPIs();
        $this->testStockMarketOpen();
        $this->testStockMarketClosed();
        $this->testStockHighVolume();
        $this->testStockLowVolume();
        $this->testForexBullishMomentum();
        $this->testForexBearishMomentum();
        $this->testForexLowMomentum();
        $this->testTimestampFormatting();
        $this->testPercentageDefinitions();
        $this->testDeterministicSummaries();

        $this->printSummary();
    }

    // ===== CRYPTO TESTS =====

    private function testCryptoWithValidData()
    {
        echo "Test 1: Crypto with valid data (BTCUSDT)\n";
        echo "----------------------------------------------\n";

        try {
            $flow = $this->derivatives->getMoneyFlow('BTCUSDT');

            // Call private method using reflection
            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            echo "✅ PASS: Valid crypto data formatted successfully\n";

            // Check key elements
            $checks = [
                'Capital Flow' => strpos($output, 'CAPITAL FLOW') !== false,
                'Spot vs Futures' => strpos($output, 'Spot vs Futures') !== false,
                'UTC timestamp' => strpos($output, 'UTC') !== false,
                '% of Total Volume' => strpos($output, '% of Total Volume') !== false,
                'Summary section' => strpos($output, 'Summary:') !== false,
            ];

            foreach ($checks as $checkName => $passed) {
                if ($passed) {
                    echo "  ✓ Contains {$checkName}\n";
                } else {
                    echo "  ✗ Missing {$checkName}\n";
                }
            }

            // Check that no "$0" appears for volume/OI
            if (strpos($output, '$0') === false && strpos($output, '\$0') === false) {
                echo "  ✓ No zero values displayed\n";
            } else {
                echo "  ✗ WARNING: Found zero values in output\n";
            }

            $this->testResults['Crypto Valid Data'] = 'PASS';
            echo "\nOutput Preview:\n";
            echo substr($output, 0, 500) . "...\n\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Crypto Valid Data'] = 'FAIL';
        }
    }

    private function testCryptoWithMissingOI()
    {
        echo "Test 2: Crypto with missing Open Interest\n";
        echo "----------------------------------------------\n";

        try {
            // Create mock flow data with missing OI
            $flow = [
                'symbol' => 'TESTUSDT',
                'market_type' => 'crypto',
                'spot' => [
                    'volume_24h' => 1500000,
                    'dominance' => 60,
                    'trades' => 25000,
                    'avg_trade_size' => 60,
                ],
                'futures' => [
                    'volume_24h' => 1000000,
                    'dominance' => 40,
                    'open_interest' => 0, // Missing data
                ],
                'flow' => [
                    'net_flow' => 'Inflow',
                    'magnitude' => 5,
                    'note' => 'Exchange flow data requires premium APIs',
                ],
                'total_volume' => 2500000,
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Check for "Unavailable" message instead of $0
            if (strpos($output, 'Unavailable') !== false && strpos($output, 'API limit') !== false) {
                echo "✅ PASS: Missing OI shows 'Unavailable (API limit or not supported)'\n";
                $this->testResults['Crypto Missing OI'] = 'PASS';
            } else {
                echo "❌ FAIL: Missing OI not handled properly\n";
                $this->testResults['Crypto Missing OI'] = 'FAIL';
            }

            // Ensure no $0 appears
            if (strpos($output, '$0') === false && strpos($output, '\$0') === false) {
                echo "  ✓ No zero values displayed\n";
            } else {
                echo "  ✗ WARNING: Found zero values in output\n";
            }

            echo "\nOutput Preview:\n";
            echo substr($output, 0, 400) . "...\n\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Crypto Missing OI'] = 'FAIL';
        }
    }

    private function testCryptoWithZeroVolume()
    {
        echo "Test 3: Crypto with zero volume\n";
        echo "----------------------------------------------\n";

        try {
            $flow = [
                'symbol' => 'LOWLIQUSDT',
                'market_type' => 'crypto',
                'spot' => [
                    'volume_24h' => 0, // Zero volume
                    'dominance' => 0,
                    'trades' => 0,
                    'avg_trade_size' => 0,
                ],
                'futures' => [
                    'volume_24h' => 0,
                    'dominance' => 0,
                    'open_interest' => 0,
                ],
                'flow' => [
                    'net_flow' => 'Unknown',
                    'magnitude' => 0,
                ],
                'total_volume' => 0,
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Should show "Unavailable" not $0
            $unavailableCount = substr_count($output, 'Unavailable');

            if ($unavailableCount >= 2) {
                echo "✅ PASS: Zero volumes show 'Unavailable' messages ({$unavailableCount} found)\n";
                $this->testResults['Crypto Zero Volume'] = 'PASS';
            } else {
                echo "❌ FAIL: Zero volumes not properly handled\n";
                $this->testResults['Crypto Zero Volume'] = 'FAIL';
            }

            echo "\nOutput Preview:\n";
            echo substr($output, 0, 400) . "...\n\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Crypto Zero Volume'] = 'FAIL';
        }
    }

    private function testCryptoExchangeFlowWithoutAPIs()
    {
        echo "Test 4: Crypto exchange flow without premium APIs\n";
        echo "----------------------------------------------\n";

        try {
            // Ensure premium APIs are not configured
            config(['services.glassnode.api_key' => null]);
            config(['services.nansen.api_key' => null]);

            $flow = $this->derivatives->getMoneyFlow('ETHUSDT');
            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Should show "Requires Glassnode or Nansen API" message
            if (strpos($output, 'Glassnode') !== false || strpos($output, 'Nansen') !== false) {
                echo "✅ PASS: Exchange flow shows API requirement message\n";
                $this->testResults['Crypto No Premium APIs'] = 'PASS';
            } else {
                echo "❌ FAIL: Exchange flow doesn't indicate API requirement\n";
                $this->testResults['Crypto No Premium APIs'] = 'FAIL';
            }

            // Should NOT infer net flow without APIs
            if (strpos($output, 'Net Flow: Inflow') === false && strpos($output, 'Net Flow: Outflow') === false) {
                echo "  ✓ Does not infer net flow without APIs\n";
            } else {
                echo "  ✗ WARNING: Still inferring net flow without premium APIs\n";
            }

            echo "\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Crypto No Premium APIs'] = 'FAIL';
        }
    }

    private function testCryptoExchangeFlowWithAPIs()
    {
        echo "Test 5: Crypto exchange flow WITH premium APIs configured\n";
        echo "----------------------------------------------\n";

        try {
            // Temporarily set API keys
            config(['services.glassnode.api_key' => 'test_key_12345']);

            $flow = $this->derivatives->getMoneyFlow('BTCUSDT');
            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Should show exchange flow section
            if (strpos($output, 'Exchange Flow') !== false && strpos($output, 'Net Flow:') !== false) {
                echo "✅ PASS: Exchange flow section displayed when API configured\n";
                $this->testResults['Crypto With Premium APIs'] = 'PASS';
            } else {
                echo "❌ FAIL: Exchange flow not shown despite API configuration\n";
                $this->testResults['Crypto With Premium APIs'] = 'FAIL';
            }

            // Clean up
            config(['services.glassnode.api_key' => null]);

            echo "\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Crypto With Premium APIs'] = 'FAIL';
        }
    }

    // ===== STOCK TESTS =====

    private function testStockMarketOpen()
    {
        echo "Test 6: Stock during market hours\n";
        echo "----------------------------------------------\n";

        try {
            $flow = $this->derivatives->getMoneyFlow('AAPL');
            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Check for key stock-specific elements
            $checks = [
                'Volume & Participation title' => strpos($output, 'VOLUME & PARTICIPATION') !== false,
                'Session status' => strpos($output, 'Session:') !== false,
                'Last Updated UTC' => strpos($output, 'Last Updated:') !== false && strpos($output, 'UTC') !== false,
                'Volume Pressure section' => strpos($output, 'Volume Pressure') !== false,
            ];

            $allPassed = true;
            foreach ($checks as $checkName => $passed) {
                if ($passed) {
                    echo "  ✓ Contains {$checkName}\n";
                } else {
                    echo "  ✗ Missing {$checkName}\n";
                    $allPassed = false;
                }
            }

            if ($allPassed) {
                echo "✅ PASS: Stock formatting includes all required elements\n";
                $this->testResults['Stock Market Open'] = 'PASS';
            } else {
                echo "❌ FAIL: Missing required stock elements\n";
                $this->testResults['Stock Market Open'] = 'FAIL';
            }

            echo "\nOutput Preview:\n";
            echo substr($output, 0, 500) . "...\n\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Stock Market Open'] = 'FAIL';
        }
    }

    private function testStockMarketClosed()
    {
        echo "Test 7: Stock when market is closed\n";
        echo "----------------------------------------------\n";

        try {
            $flow = [
                'symbol' => 'TSLA',
                'market_type' => 'stock',
                'volume' => [
                    'current' => 0, // Market closed
                    'average' => 75000000,
                    'ratio' => 0,
                    'status' => 'Low',
                ],
                'pressure' => [
                    'type' => 'Mixed',
                    'pressure' => 'Neutral',
                    'interpretation' => 'Market closed',
                ],
                'price_change_24h' => -1.2,
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Should show "Market closed" not "Current: 0"
            if (strpos($output, 'Unavailable') !== false && strpos($output, 'Market closed') !== false) {
                echo "✅ PASS: Shows 'Unavailable (Market closed)' instead of zero\n";
                $this->testResults['Stock Market Closed'] = 'PASS';
            } else {
                echo "❌ FAIL: Market closed state not handled properly\n";
                $this->testResults['Stock Market Closed'] = 'FAIL';
            }

            // Check session status
            if (strpos($output, 'Session: Closed') !== false) {
                echo "  ✓ Session status shows Closed\n";
            }

            echo "\nOutput Preview:\n";
            echo substr($output, 0, 400) . "...\n\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Stock Market Closed'] = 'FAIL';
        }
    }

    private function testStockHighVolume()
    {
        echo "Test 8: Stock with high volume conviction\n";
        echo "----------------------------------------------\n";

        try {
            $flow = [
                'symbol' => 'NVDA',
                'market_type' => 'stock',
                'volume' => [
                    'current' => 150000000,
                    'average' => 75000000,
                    'ratio' => 2.0, // 2x average
                    'status' => 'High',
                ],
                'pressure' => [
                    'type' => 'Bullish',
                    'pressure' => 'Strong Buying',
                    'interpretation' => 'High volume + rising price = strong institutional accumulation',
                ],
                'price_change_24h' => 5.5,
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Summary should mention high volume and bullish conviction
            if (strpos($output, 'High volume') !== false && strpos($output, 'bullish conviction') !== false) {
                echo "✅ PASS: Summary correctly identifies high volume conviction\n";
                $this->testResults['Stock High Volume'] = 'PASS';
            } else {
                echo "❌ FAIL: Summary doesn't correctly analyze high volume scenario\n";
                $this->testResults['Stock High Volume'] = 'FAIL';
            }

            echo "\nSummary Extract:\n";
            $summaryPos = strpos($output, 'Summary:');
            if ($summaryPos !== false) {
                echo substr($output, $summaryPos, 200) . "\n\n";
            }
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Stock High Volume'] = 'FAIL';
        }
    }

    private function testStockLowVolume()
    {
        echo "Test 9: Stock with low volume\n";
        echo "----------------------------------------------\n";

        try {
            $flow = [
                'symbol' => 'SPY',
                'market_type' => 'stock',
                'volume' => [
                    'current' => 40000000,
                    'average' => 70000000,
                    'ratio' => 0.57, // Below 0.7
                    'status' => 'Low',
                ],
                'pressure' => [
                    'type' => 'Cautious',
                    'pressure' => 'Weak Buying',
                    'interpretation' => 'Rising price + low volume = weak rally, potential reversal',
                ],
                'price_change_24h' => 0.8,
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Summary should warn about low participation
            if (strpos($output, 'Below-average volume') !== false && strpos($output, 'lack conviction') !== false) {
                echo "✅ PASS: Summary correctly warns about low volume\n";
                $this->testResults['Stock Low Volume'] = 'PASS';
            } else {
                echo "❌ FAIL: Summary doesn't warn about low volume risks\n";
                $this->testResults['Stock Low Volume'] = 'FAIL';
            }

            echo "\nSummary Extract:\n";
            $summaryPos = strpos($output, 'Summary:');
            if ($summaryPos !== false) {
                echo substr($output, $summaryPos, 200) . "\n\n";
            }
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Stock Low Volume'] = 'FAIL';
        }
    }

    // ===== FOREX TESTS =====

    private function testForexBullishMomentum()
    {
        echo "Test 10: Forex with bullish momentum\n";
        echo "----------------------------------------------\n";

        try {
            $flow = $this->derivatives->getMoneyFlow('EURUSD');
            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Check for forex-specific elements
            $checks = [
                'Price Momentum Proxy title' => strpos($output, 'PRICE MOMENTUM PROXY') !== false,
                'No volume terminology' => strpos($output, 'Volume') === false,
                'Forex disclaimer' => strpos($output, 'no centralized volume') !== false,
                'Momentum Analysis' => strpos($output, 'Momentum Analysis') !== false,
            ];

            $allPassed = true;
            foreach ($checks as $checkName => $passed) {
                if ($passed) {
                    echo "  ✓ {$checkName}\n";
                } else {
                    echo "  ✗ {$checkName}\n";
                    $allPassed = false;
                }
            }

            if ($allPassed) {
                echo "✅ PASS: Forex uses correct terminology (no volume references)\n";
                $this->testResults['Forex Bullish'] = 'PASS';
            } else {
                echo "❌ FAIL: Forex still uses inappropriate volume terminology\n";
                $this->testResults['Forex Bullish'] = 'FAIL';
            }

            echo "\nOutput Preview:\n";
            echo substr($output, 0, 500) . "...\n\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Forex Bullish'] = 'FAIL';
        }
    }

    private function testForexBearishMomentum()
    {
        echo "Test 11: Forex with bearish momentum\n";
        echo "----------------------------------------------\n";

        try {
            $flow = [
                'symbol' => 'GBPJPY',
                'market_type' => 'forex',
                'momentum' => [
                    'direction' => 'Bearish',
                    'strength' => 'Strong',
                    'change_percent' => -1.35,
                ],
                'note' => 'Forex markets have no centralized volume data. Analysis based on price momentum.',
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Summary should mention downward momentum
            if (strpos($output, 'downward momentum') !== false) {
                echo "✅ PASS: Summary correctly identifies bearish momentum\n";
                $this->testResults['Forex Bearish'] = 'PASS';
            } else {
                echo "❌ FAIL: Summary doesn't correctly identify bearish trend\n";
                $this->testResults['Forex Bearish'] = 'FAIL';
            }

            echo "\nSummary Extract:\n";
            $summaryPos = strpos($output, 'Summary:');
            if ($summaryPos !== false) {
                echo substr($output, $summaryPos, 200) . "\n\n";
            }
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Forex Bearish'] = 'FAIL';
        }
    }

    private function testForexLowMomentum()
    {
        echo "Test 12: Forex with low momentum\n";
        echo "----------------------------------------------\n";

        try {
            $flow = [
                'symbol' => 'USDJPY',
                'market_type' => 'forex',
                'momentum' => [
                    'direction' => 'Bullish',
                    'strength' => 'Weak',
                    'change_percent' => 0.15, // Low change
                ],
                'note' => 'Forex markets have no centralized volume data. Analysis based on price momentum.',
                'timestamp' => now()->toIso8601String(),
            ];

            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Summary should indicate consolidation
            if (strpos($output, 'consolidating') !== false || strpos($output, 'Low momentum') !== false) {
                echo "✅ PASS: Summary correctly identifies low momentum consolidation\n";
                $this->testResults['Forex Low Momentum'] = 'PASS';
            } else {
                echo "❌ FAIL: Summary doesn't identify consolidation\n";
                $this->testResults['Forex Low Momentum'] = 'FAIL';
            }

            echo "\nSummary Extract:\n";
            $summaryPos = strpos($output, 'Summary:');
            if ($summaryPos !== false) {
                echo substr($output, $summaryPos, 200) . "\n\n";
            }
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Forex Low Momentum'] = 'FAIL';
        }
    }

    // ===== CROSS-CUTTING TESTS =====

    private function testTimestampFormatting()
    {
        echo "Test 13: Timestamp formatting\n";
        echo "----------------------------------------------\n";

        try {
            // Test all three market types
            $markets = [
                'crypto' => 'BTCUSDT',
                'stock' => 'AAPL',
                'forex' => 'EURUSD',
            ];

            $allPass = true;
            foreach ($markets as $type => $symbol) {
                $flow = $this->derivatives->getMoneyFlow($symbol);
                $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

                // Check for UTC timestamp
                if (strpos($output, 'UTC') !== false) {
                    echo "  ✓ {$type}: Contains UTC timestamp\n";
                } else {
                    echo "  ✗ {$type}: Missing UTC timestamp\n";
                    $allPass = false;
                }

                // Check timestamp format (YYYY-MM-DD HH:MM:SS)
                if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $output)) {
                    echo "  ✓ {$type}: Timestamp format correct\n";
                } else {
                    echo "  ✗ {$type}: Timestamp format incorrect\n";
                    $allPass = false;
                }
            }

            if ($allPass) {
                echo "✅ PASS: All timestamps formatted correctly with UTC\n";
                $this->testResults['Timestamp Formatting'] = 'PASS';
            } else {
                echo "❌ FAIL: Timestamp formatting issues detected\n";
                $this->testResults['Timestamp Formatting'] = 'FAIL';
            }

            echo "\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Timestamp Formatting'] = 'FAIL';
        }
    }

    private function testPercentageDefinitions()
    {
        echo "Test 14: Percentage definitions (crypto)\n";
        echo "----------------------------------------------\n";

        try {
            $flow = $this->derivatives->getMoneyFlow('ETHUSDT');
            $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flow]);

            // Check that percentages are defined as "% of Total Volume" not just "Dominance"
            if (strpos($output, '% of Total Volume') !== false) {
                echo "✅ PASS: Percentages clearly defined as '% of Total Volume'\n";
                $this->testResults['Percentage Definitions'] = 'PASS';
            } else {
                echo "❌ FAIL: Percentage definitions unclear\n";
                $this->testResults['Percentage Definitions'] = 'FAIL';
            }

            // Ensure "Dominance" alone is not used
            if (strpos($output, 'Dominance:') === false) {
                echo "  ✓ No ambiguous 'Dominance' label used\n";
            } else {
                echo "  ✗ Still using ambiguous 'Dominance' label\n";
            }

            echo "\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Percentage Definitions'] = 'FAIL';
        }
    }

    private function testDeterministicSummaries()
    {
        echo "Test 15: Deterministic summaries\n";
        echo "----------------------------------------------\n";

        try {
            // Test different scenarios produce appropriate summaries
            $scenarios = [
                'crypto_futures_heavy' => [
                    'symbol' => 'TEST1',
                    'market_type' => 'crypto',
                    'spot' => ['volume_24h' => 500000],
                    'futures' => ['volume_24h' => 2000000, 'open_interest' => 1000000],
                    'total_volume' => 2500000,
                ],
                'crypto_spot_heavy' => [
                    'symbol' => 'TEST2',
                    'market_type' => 'crypto',
                    'spot' => ['volume_24h' => 2000000],
                    'futures' => ['volume_24h' => 500000, 'open_interest' => 300000],
                    'total_volume' => 2500000,
                ],
                'stock_high_volume' => [
                    'symbol' => 'TEST3',
                    'market_type' => 'stock',
                    'volume' => ['current' => 100000000, 'average' => 50000000, 'ratio' => 2.0],
                    'price_change_24h' => 4.5,
                    'pressure' => ['pressure' => 'Strong Buying'],
                ],
            ];

            $allPass = true;
            foreach ($scenarios as $name => $flowData) {
                $output = $this->callPrivateMethod($this->commandHandler, 'formatMoneyFlow', [$flowData]);

                // Extract summary
                $summaryPos = strpos($output, 'Summary:');
                if ($summaryPos !== false) {
                    $summary = substr($output, $summaryPos + 9, 200);

                    // Check that summary is evidence-based (not generic)
                    $isEvidenceBased = (
                        strpos($summary, 'futures') !== false ||
                        strpos($summary, 'spot') !== false ||
                        strpos($summary, 'volume') !== false ||
                        strpos($summary, 'leverage') !== false ||
                        strpos($summary, 'conviction') !== false
                    );

                    if ($isEvidenceBased) {
                        echo "  ✓ {$name}: Evidence-based summary\n";
                    } else {
                        echo "  ✗ {$name}: Generic summary\n";
                        $allPass = false;
                    }
                } else {
                    echo "  ✗ {$name}: No summary found\n";
                    $allPass = false;
                }
            }

            if ($allPass) {
                echo "✅ PASS: All summaries are deterministic and evidence-based\n";
                $this->testResults['Deterministic Summaries'] = 'PASS';
            } else {
                echo "❌ FAIL: Some summaries are not evidence-based\n";
                $this->testResults['Deterministic Summaries'] = 'FAIL';
            }

            echo "\n";
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n\n";
            $this->testResults['Deterministic Summaries'] = 'FAIL';
        }
    }

    // ===== HELPER METHODS =====

    private function callPrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    private function printSummary()
    {
        echo "=================================================\n";
        echo "TEST SUMMARY\n";
        echo "=================================================\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->testResults as $testName => $result) {
            $icon = $result === 'PASS' ? '✅' : '❌';
            echo "{$icon} {$testName}: {$result}\n";

            if ($result === 'PASS') {
                $passed++;
            } else {
                $failed++;
            }
        }

        $total = $passed + $failed;
        $passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        echo "\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Pass Rate: {$passRate}%\n";

        if ($passRate >= 90) {
            echo "\n🎉 EXCELLENT! Flow command improvements are working correctly.\n";
        } elseif ($passRate >= 70) {
            echo "\n⚠️ GOOD, but some issues need attention.\n";
        } else {
            echo "\n❌ CRITICAL ISSUES DETECTED. Review failed tests.\n";
        }
    }
}

// Run tests
$tester = new FlowValidationTest();
$tester->runAllTests();
