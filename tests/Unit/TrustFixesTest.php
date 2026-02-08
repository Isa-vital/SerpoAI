<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for the 9 pre-launch trust fixes.
 * Validates that crashes are eliminated and trust-undermining content is removed.
 */
class TrustFixesTest extends TestCase
{
    private string $commandHandlerSource;
    private string $premiumServiceSource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commandHandlerSource = file_get_contents(app_path('Services/CommandHandler.php'));
        $this->premiumServiceSource = file_get_contents(app_path('Services/PremiumService.php'));
    }

    // ===== RED FIX #1: /backtest no longer uses undefined $this->dexscreener =====

    public function test_backtest_does_not_reference_dexscreener(): void
    {
        $this->assertStringNotContainsString(
            '$this->dexscreener',
            $this->commandHandlerSource,
            '/backtest handler still references undefined $this->dexscreener property'
        );
    }

    public function test_backtest_uses_multimarket_service(): void
    {
        $this->assertStringContainsString(
            '$this->multiMarket->getCurrentPrice($symbol)',
            $this->commandHandlerSource,
            '/backtest should use multiMarket service for price data'
        );
    }

    public function test_backtest_does_not_use_pairs_array_format(): void
    {
        $this->assertStringNotContainsString(
            "\$marketData['pairs'][0]",
            $this->commandHandlerSource,
            '/backtest should not reference DexScreener pairs[0] format'
        );
    }

    // ===== RED FIX #2: /copy no longer references $hub['coming_soon'] =====

    public function test_copy_does_not_reference_coming_soon_key(): void
    {
        $this->assertStringNotContainsString(
            "hub['coming_soon']",
            $this->commandHandlerSource,
            '/copy formatCopyTradingHub still references removed coming_soon key'
        );
    }

    public function test_copy_displays_top_traders(): void
    {
        $this->assertStringContainsString(
            "hub['top_traders']",
            $this->commandHandlerSource,
            '/copy should display top_traders from CopyTradingService'
        );
    }

    // ===== RED FIX #3: /trader callback buttons now route properly =====

    public function test_callback_handler_routes_chart_prefix(): void
    {
        $this->assertMatchesRegularExpression(
            '/preg_match.*chart\|alert\|analyze\|signals/',
            $this->commandHandlerSource,
            'handleCallback should match chart_, alert_, analyze_, signals_ prefixes'
        );
    }

    public function test_trader_buttons_have_matching_routes(): void
    {
        // Verify the trader command creates buttons with these prefixes
        $this->assertStringContainsString('chart_{$symbol}', $this->commandHandlerSource);
        $this->assertStringContainsString('alert_{$symbol}', $this->commandHandlerSource);
        $this->assertStringContainsString('analyze_{$symbol}', $this->commandHandlerSource);
        $this->assertStringContainsString('signals_{$symbol}', $this->commandHandlerSource);

        // Verify the callback handler maps them to real commands
        $this->assertStringContainsString("'chart' => '/charts'", $this->commandHandlerSource);
        $this->assertStringContainsString("'alert' => '/setalert'", $this->commandHandlerSource);
        $this->assertStringContainsString("'analyze' => '/analyze'", $this->commandHandlerSource);
        $this->assertStringContainsString("'signals' => '/signals'", $this->commandHandlerSource);
    }

    // ===== RED FIX #4: /trending alias is registered =====

    public function test_trending_alias_registered_in_router(): void
    {
        $this->assertMatchesRegularExpression(
            "/['\"]\/trending['\"]\s*=>/",
            $this->commandHandlerSource,
            '/trending should be registered as a command alias'
        );
    }

    public function test_trending_routes_to_trendcoins_handler(): void
    {
        $this->assertStringContainsString(
            "'/trending' => \$this->handleTrendCoins",
            $this->commandHandlerSource,
            '/trending should map to handleTrendCoins method'
        );
    }

    // ===== YELLOW FIX #5: /start message is confident, no "coming soon" =====

    public function test_start_has_no_preview_mode(): void
    {
        // Extract handleStart method content
        $startSection = $this->extractMethod('handleStart');

        $this->assertStringNotContainsString('preview mode', $startSection, '/start still mentions preview mode');
    }

    public function test_start_has_no_coming_soon(): void
    {
        $startSection = $this->extractMethod('handleStart');

        $this->assertStringNotContainsString('Coming Soon', $startSection, '/start still shows Coming Soon');
    }

    public function test_start_has_no_under_construction(): void
    {
        $startSection = $this->extractMethod('handleStart');

        $this->assertStringNotContainsString('Under Construction', $startSection, '/start still shows Under Construction');
    }

    public function test_start_has_no_features_will_unlock(): void
    {
        $startSection = $this->extractMethod('handleStart');

        $this->assertStringNotContainsString('Features will unlock', $startSection, '/start still promises future unlocking');
    }

    public function test_start_showcases_multi_market(): void
    {
        $startSection = $this->extractMethod('handleStart');

        // Strings are now in lang files, check translation keys reference multi-market content
        $this->assertStringContainsString('commands.start.coverage', $startSection);
        $this->assertStringContainsString('commands.start.market_intel', $startSection);
    }

    public function test_start_has_action_oriented_cta(): void
    {
        $startSection = $this->extractMethod('handleStart');

        $this->assertStringContainsString('/help', $startSection, '/start should reference /help command');
        $this->assertStringContainsString('get_started', $startSection, '/start should have a Get Started section');
    }

    // ===== YELLOW FIX #6: /backtest AI disclosure =====

    public function test_backtest_discloses_ai_estimation(): void
    {
        $this->assertStringContainsString(
            'AI-estimated simulation',
            $this->commandHandlerSource,
            '/backtest must disclose that results are AI-estimated'
        );
    }

    public function test_backtest_clarifies_not_real_backtest(): void
    {
        $this->assertStringContainsString(
            'NOT a real historical backtest',
            $this->commandHandlerSource,
            '/backtest must clarify this is NOT a real backtest'
        );
    }

    // ===== YELLOW FIX #7: /predict supports multi-market =====

    public function test_predict_uses_market_type_detection(): void
    {
        $this->assertStringContainsString(
            'detectMarketType($symbol)',
            $this->commandHandlerSource,
            '/predict should detect whether symbol is crypto/stock/forex'
        );
    }

    public function test_predict_has_multimarket_fallback(): void
    {
        $predictSection = $this->extractMethod('handlePredict');

        $this->assertStringContainsString(
            'multiMarket->getCurrentPrice',
            $predictSection,
            '/predict should fall back to multiMarket for non-crypto symbols'
        );
    }

    public function test_predict_error_mentions_supported_types(): void
    {
        $this->assertStringContainsString(
            'stocks',
            $this->commandHandlerSource,
            '/predict error message should mention stocks as supported'
        );
    }

    // ===== YELLOW FIX #8: SERPO removed from sentiment map =====

    public function test_no_serpo_in_sentiment_map(): void
    {
        // The exact legacy line was: 'SERPO' => 'Serpo',
        $this->assertStringNotContainsString(
            "'SERPO' => 'Serpo'",
            $this->commandHandlerSource,
            'Legacy SERPO reference still exists in sentiment symbol map'
        );
    }

    // ===== YELLOW FIX #9: /premium is early access, no fake pricing =====

    public function test_premium_has_no_fake_prices(): void
    {
        $this->assertStringNotContainsString('$9.99', $this->premiumServiceSource, 'Premium still shows fake $9.99 price');
        $this->assertStringNotContainsString('$24.99', $this->premiumServiceSource, 'Premium still shows fake $24.99 price');
        $this->assertStringNotContainsString('$49.99', $this->premiumServiceSource, 'Premium still shows fake $49.99 price');
    }

    public function test_premium_has_no_payment_options(): void
    {
        $formatMethod = $this->extractMethodFromSource($this->premiumServiceSource, 'formatPremiumInfo');

        $this->assertStringNotContainsString('Credit/Debit', $formatMethod, 'Premium still lists payment methods with no payment flow');
        $this->assertStringNotContainsString('Telegram Stars', $formatMethod, 'Premium still lists Telegram Stars payment');
    }

    public function test_premium_mentions_early_access(): void
    {
        $this->assertStringContainsString(
            'early access',
            strtolower($this->premiumServiceSource),
            'Premium should mention early access period'
        );
    }

    public function test_premium_states_features_are_free(): void
    {
        $this->assertStringContainsString(
            'FREE',
            $this->premiumServiceSource,
            'Premium should clearly state all features are free'
        );
    }

    public function test_premium_has_no_contact_support_to_upgrade(): void
    {
        $this->assertStringNotContainsString(
            'Contact support to upgrade',
            $this->premiumServiceSource,
            'Premium should not tell users to contact support to upgrade (no upgrade flow exists)'
        );
    }

    // ===== INTEGRATION: CopyTradingService returns top_traders =====

    public function test_copy_trading_service_returns_top_traders_key(): void
    {
        $service = app(\App\Services\CopyTradingService::class);
        $hub = $service->getCopyTradingHub();

        $this->assertIsArray($hub);
        $this->assertArrayHasKey('top_traders', $hub, 'CopyTradingService should return top_traders key');
        $this->assertArrayNotHasKey('coming_soon', $hub, 'CopyTradingService should NOT have coming_soon key');
    }

    // ===== INTEGRATION: PremiumService formatPremiumInfo doesn't crash =====

    public function test_premium_format_does_not_crash(): void
    {
        $service = app(\App\Services\PremiumService::class);
        $output = $service->formatPremiumInfo();

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('FREE', $output);
    }

    // ===== INTEGRATION: MultiMarketDataService detectMarketType works =====

    public function test_multimarket_detects_crypto(): void
    {
        $service = app(\App\Services\MultiMarketDataService::class);
        $type = $service->detectMarketType('BTCUSDT');
        $this->assertEquals('crypto', $type);
    }

    public function test_multimarket_detects_stock(): void
    {
        $service = app(\App\Services\MultiMarketDataService::class);
        $type = $service->detectMarketType('AAPL');
        $this->assertEquals('stock', $type);
    }

    public function test_multimarket_detects_forex(): void
    {
        $service = app(\App\Services\MultiMarketDataService::class);
        $type = $service->detectMarketType('EURUSD');
        $this->assertEquals('forex', $type);
    }

    // ===== HELPER METHODS =====

    /**
     * Extract a method body from CommandHandler source by name
     */
    private function extractMethod(string $methodName): string
    {
        return $this->extractMethodFromSource($this->commandHandlerSource, $methodName);
    }

    /**
     * Extract method body from given source code
     */
    private function extractMethodFromSource(string $source, string $methodName): string
    {
        $pattern = '/function\s+' . preg_quote($methodName) . '\s*\([^)]*\)[^{]*\{/';
        if (!preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $start = $matches[0][1];
        $braceCount = 0;
        $length = strlen($source);
        $inMethod = false;

        for ($i = $start; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $braceCount++;
                $inMethod = true;
            } elseif ($source[$i] === '}') {
                $braceCount--;
                if ($inMethod && $braceCount === 0) {
                    return substr($source, $start, $i - $start + 1);
                }
            }
        }

        return substr($source, $start, 2000); // fallback
    }
}
