<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERPO AI - Multi-Market Trading Intelligence Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0a0e27;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }

        /* Starfield effect */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(2px 2px at 20px 30px, rgba(0, 255, 255, 0.3), transparent),
                radial-gradient(2px 2px at 60px 70px, rgba(0, 255, 255, 0.3), transparent),
                radial-gradient(1px 1px at 50px 50px, rgba(0, 255, 0, 0.3), transparent),
                radial-gradient(1px 1px at 130px 80px, rgba(0, 255, 255, 0.3), transparent),
                radial-gradient(2px 2px at 90px 10px, rgba(0, 255, 0, 0.3), transparent);
            background-size: 200px 200px;
            background-position: 0 0, 40px 60px, 130px 270px, 70px 100px, 150px 50px;
            pointer-events: none;
            z-index: 0;
            animation: twinkle 3s ease-in-out infinite;
        }

        @keyframes twinkle {

            0%,
            100% {
                opacity: 0.3;
            }

            50% {
                opacity: 0.6;
            }
        }

        /* Background gradient overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, rgba(0, 255, 255, 0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        header {
            padding: 20px 0;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            width: 50px;
            height: 50px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Beta Banner */
        .beta-banner {
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.15) 0%, rgba(0, 255, 0, 0.1) 100%);
            border: 1px solid rgba(0, 255, 255, 0.4);
            border-radius: 12px;
            padding: 20px 30px;
            margin: 30px 0;
            text-align: center;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
        }

        .beta-banner h3 {
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .beta-banner p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            line-height: 1.6;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 80px 0;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: bold;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            color: #0a0e27;
        }

        .btn-primary:hover {
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(10, 14, 39, 0.8);
            color: #00ffff;
            border: 1px solid #00ffff;
        }

        .btn-secondary:hover {
            background: rgba(0, 255, 255, 0.1);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        /* Features Section */
        .features {
            padding: 60px 0;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .feature-card {
            background: rgba(10, 14, 39, 0.8);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 12px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: rgba(0, 255, 255, 0.5);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.2);
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
        }

        .feature-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .badge-available {
            background: rgba(0, 255, 0, 0.2);
            color: #00ff00;
            border: 1px solid rgba(0, 255, 0, 0.4);
        }

        .badge-coming-soon {
            background: rgba(255, 165, 0, 0.2);
            color: #ffa500;
            border: 1px solid rgba(255, 165, 0, 0.4);
        }

        .badge-beta {
            background: rgba(0, 255, 255, 0.2);
            color: #00ffff;
            border: 1px solid rgba(0, 255, 255, 0.4);
        }

        /* Gallery Section */
        .gallery {
            padding: 60px 0;
        }

        .showcase-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .showcase-card {
            background: rgba(10, 14, 39, 0.8);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            position: relative;
        }

        .showcase-card:hover {
            border-color: rgba(0, 255, 255, 0.6);
            box-shadow: 0 10px 40px rgba(0, 255, 255, 0.3);
            transform: translateY(-10px);
        }

        .showcase-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .showcase-card:hover .showcase-image {
            transform: scale(1.05);
        }

        .showcase-content {
            padding: 25px;
        }

        .showcase-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .showcase-badge.live {
            background: linear-gradient(135deg, rgba(0, 255, 0, 0.3), rgba(0, 255, 255, 0.2));
            color: #00ff00;
            border: 1px solid rgba(0, 255, 0, 0.5);
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.3);
        }

        .showcase-badge.coming {
            background: linear-gradient(135deg, rgba(255, 165, 0, 0.3), rgba(255, 100, 0, 0.2));
            color: #ffa500;
            border: 1px solid rgba(255, 165, 0, 0.5);
        }

        .showcase-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .showcase-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .showcase-features {
            list-style: none;
            padding: 0;
        }

        .showcase-features li {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }

        .showcase-features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #00ff00;
            font-weight: bold;
        }

        .section-divider {
            text-align: center;
            margin: 80px 0 40px;
            position: relative;
        }

        .section-divider::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.4), transparent);
        }

        .section-divider span {
            background: #0a0e27;
            padding: 0 30px;
            position: relative;
            font-size: 14px;
            color: rgba(0, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        /* Gallery Section - Simple grid for remaining images */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .gallery-item:hover {
            border-color: rgba(0, 255, 255, 0.5);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
            transform: translateY(-5px);
        }

        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        /* Commands Section */
        .commands {
            padding: 60px 0;
        }

        .commands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .command-item {
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            padding: 2px;
            border-radius: 8px;
        }

        .command-content {
            background: #0a0e27;
            padding: 20px;
            border-radius: 6px;
        }

        .command-content code {
            color: #00ffff;
            font-size: 16px;
            font-family: 'Courier New', monospace;
        }

        .command-content p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-top: 8px;
        }

        /* Stats Section */
        .stats {
            padding: 60px 0;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .stat-item h4 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-item p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        /* Footer */
        footer {
            padding: 40px 0;
            border-top: 1px solid rgba(0, 255, 255, 0.2);
            text-align: center;
            margin-top: 60px;
        }

        footer p {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 10px;
        }

        footer a {
            color: #00ffff;
            text-decoration: none;
            margin: 0 10px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 16px;
            }

            .section-title {
                font-size: 28px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="https://serpocoin.io/logo.png" alt="Serpo Logo">
                    <div class="logo-text">SERPO AI</div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Beta Banner -->
        <div class="beta-banner">
            <h3>� Preview Mode - You're Early</h3>
            <p>SERPO AI is live in preview mode. Features unlock progressively as modules go live.<br>
                You're accessing this platform before broader public rollout. <em>There is no pressure — only progress.</em></p>
        </div>

        <!-- Hero Section -->
        <section class="hero">
            <h1>SERPO AI</h1>
            <p>Comprehensive multi-market trading intelligence platform for Crypto, Forex, and Stocks</p>
            <div class="cta-buttons">
                <a href="https://t.me/SerpoAI_bot" class="btn btn-primary" target="_blank">Launch Bot on Telegram</a>
                <a href="https://serpocoin.io" class="btn btn-secondary" target="_blank">Learn About SerpoCoin</a>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <h2 class="section-title">Available Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Multi-Market Signals</h3>
                    <p>Professional trading signals for Crypto, Forex, and Stocks with confidence scoring (1-5)</p>
                    <span class="feature-badge badge-available">✓ Available</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Token Verification</h3>
                    <p>Professional risk assessment with transparent scoring across 7 weighted factors</p>
                    <span class="feature-badge badge-available">✓ Available</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Technical Analysis</h3>
                    <p>RSI, support/resistance, divergence, moving averages, and Fibonacci retracements</p>
                    <span class="feature-badge badge-available">✓ Available</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Derivatives & OI</h3>
                    <p>Open interest tracking, funding rates, and liquidation heatmaps</p>
                    <span class="feature-badge badge-available">✓ Available</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🐋</div>
                    <h3>Whale Tracker</h3>
                    <p>Real-time whale transaction monitoring and alerts</p>
                    <span class="feature-badge badge-available">✓ Available</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3>Price Alerts</h3>
                    <p>Custom price alerts with instant Telegram notifications</p>
                    <span class="feature-badge badge-beta">Beta</span>
                </div>
            </div>

            <h2 class="section-title" style="margin-top: 80px;">Coming Soon</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🧠</div>
                    <h3>Quant AI Engine</h3>
                    <p>Advanced quantitative analysis with machine learning-powered predictions</p>
                    <span class="feature-badge badge-coming-soon">Coming Soon</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>Automated Trading</h3>
                    <p>Copy trading, grid bots, DCA bots, arbitrage systems, and Forex sniper bots</p>
                    <span class="feature-badge badge-coming-soon">Coming Soon</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔬</div>
                    <h3>Backtesting Lab</h3>
                    <p>Test strategies with historical data and optimize parameters</p>
                    <span class="feature-badge badge-coming-soon">Coming Soon</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚙️</div>
                    <h3>Execution & Liquidity</h3>
                    <p>CEX & DEX trading access, broker integrations, liquidity flow tracking</p>
                    <span class="feature-badge badge-coming-soon">Coming Soon</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Trader Workspace</h3>
                    <p>Strategy builder, performance analytics, and trading journal</p>
                    <span class="feature-badge badge-coming-soon">Coming Soon</span>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Premium Channels</h3>
                    <p>Exclusive signals unlockable via SerpoCoin with on-chain verification</p>
                    <span class="feature-badge badge-coming-soon">Coming Soon</span>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section class="gallery">
            <h2 class="section-title">Features Showcase</h2>
            <p style="text-align: center; color: rgba(255, 255, 255, 0.8); margin-bottom: 20px; max-width: 700px; margin-left: auto; margin-right: auto;">
                Experience real bot interactions and see how SERPO AI delivers professional-grade trading intelligence
            </p>

            <h3 style="text-align: center; color: #00ff00; font-size: 20px; margin-top: 50px; margin-bottom: 30px;">✨ Available Now</h3>
            
            <div class="showcase-grid">
                <div class="showcase-card">
                    <img src="/images/photo_1_2026-02-01_08-46-47.jpg" alt="Multi-Market Signals" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge live">⚡ Live</span>
                        <h3 class="showcase-title">Multi-Market Signals</h3>
                        <p class="showcase-description">
                            Professional trading signals across Crypto, Forex, and Stocks with AI-powered confidence scoring
                        </p>
                        <ul class="showcase-features">
                            <li>1-5 confidence scoring system</li>
                            <li>Clear reasoning & flip conditions</li>
                            <li>Real-time market metadata</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_2_2026-02-01_08-46-47.jpg" alt="Token Verification" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge live">⚡ Live</span>
                        <h3 class="showcase-title">Token Verification</h3>
                        <p class="showcase-description">
                            Transparent risk assessment with professional-grade scoring across 7 weighted factors
                        </p>
                        <ul class="showcase-features">
                            <li>Raw metrics & holder analysis</li>
                            <li>Ownership detection</li>
                            <li>Works without API keys</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_3_2026-02-01_08-46-47.jpg" alt="Technical Analysis" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge live">⚡ Live</span>
                        <h3 class="showcase-title">Advanced Technical Analysis</h3>
                        <p class="showcase-description">
                            Multi-timeframe RSI, support/resistance, and comprehensive indicator suite
                        </p>
                        <ul class="showcase-features">
                            <li>RSI heatmaps across timeframes</li>
                            <li>Fibonacci & divergence detection</li>
                            <li>Moving average crossovers</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_4_2026-02-01_08-46-47.jpg" alt="Whale Tracking" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge live">⚡ Live</span>
                        <h3 class="showcase-title">Whale Transaction Tracker</h3>
                        <p class="showcase-description">
                            Real-time monitoring of large transactions and whale movements across multiple chains
                        </p>
                        <ul class="showcase-features">
                            <li>Instant whale alerts</li>
                            <li>Transaction value & destination</li>
                            <li>Historical whale activity</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_5_2026-02-01_08-46-47.jpg" alt="Open Interest" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge live">⚡ Live</span>
                        <h3 class="showcase-title">Derivatives & Open Interest</h3>
                        <p class="showcase-description">
                            Track open interest, funding rates, and liquidation data for informed trading decisions
                        </p>
                        <ul class="showcase-features">
                            <li>Real-time OI tracking</li>
                            <li>Funding rate analysis</li>
                            <li>Liquidation heatmaps</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="section-divider">
                <span>More Features Coming Soon</span>
            </div>

            <div class="showcase-grid">
                <div class="showcase-card">
                    <img src="/images/photo_6_2026-02-01_08-46-47.jpg" alt="AI Trading Bots" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge coming">🚀 Coming Soon</span>
                        <h3 class="showcase-title">Automated Trading Bots</h3>
                        <p class="showcase-description">
                            Copy trading, grid bots, DCA bots, and arbitrage systems for automated profit generation
                        </p>
                        <ul class="showcase-features">
                            <li>Set & forget automation</li>
                            <li>Risk-managed strategies</li>
                            <li>Multi-exchange support</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_7_2026-02-01_08-46-47.jpg" alt="Backtesting Lab" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge coming">🚀 Coming Soon</span>
                        <h3 class="showcase-title">Strategy Backtesting Lab</h3>
                        <p class="showcase-description">
                            Test your strategies against historical data with detailed performance analytics
                        </p>
                        <ul class="showcase-features">
                            <li>Historical data simulation</li>
                            <li>Performance metrics</li>
                            <li>Strategy optimization</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_8_2026-02-01_08-46-47.jpg" alt="Quant AI Engine" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge coming">🚀 Coming Soon</span>
                        <h3 class="showcase-title">Quant AI Engine</h3>
                        <p class="showcase-description">
                            Machine learning-powered predictions and quantitative analysis for edge trading
                        </p>
                        <ul class="showcase-features">
                            <li>ML-powered predictions</li>
                            <li>Pattern recognition</li>
                            <li>Adaptive algorithms</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_9_2026-02-01_08-46-47.jpg" alt="Trading Workspace" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge coming">🚀 Coming Soon</span>
                        <h3 class="showcase-title">Professional Trader Workspace</h3>
                        <p class="showcase-description">
                            Complete trading suite with strategy builder, analytics, and performance journal
                        </p>
                        <ul class="showcase-features">
                            <li>Visual strategy builder</li>
                            <li>Performance analytics</li>
                            <li>Trading journal & notes</li>
                        </ul>
                    </div>
                </div>

                <div class="showcase-card">
                    <img src="/images/photo_10_2026-02-01_08-46-47.jpg" alt="Premium Channels" class="showcase-image">
                    <div class="showcase-content">
                        <span class="showcase-badge coming">🚀 Coming Soon</span>
                        <h3 class="showcase-title">Premium Market Channels</h3>
                        <p class="showcase-description">
                            Exclusive crypto, forex, and stock signals unlockable via SerpoCoin with on-chain verification
                        </p>
                        <ul class="showcase-features">
                            <li>On-chain token verification</li>
                            <li>Exclusive high-conviction signals</li>
                            <li>Priority support access</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Commands Section -->
        <section class="commands">
            <h2 class="section-title">Bot Commands</h2>
            <div class="commands-grid">
                <div class="command-item">
                    <div class="command-content">
                        <code>/signals [symbol]</code>
                        <p>Multi-market trading signals</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/verify [address]</code>
                        <p>Professional token analysis</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/rsi [symbol]</code>
                        <p>Multi-timeframe RSI analysis</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/chart [symbol]</code>
                        <p>TradingView charts</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/oi [symbol]</code>
                        <p>Open interest tracking</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/whales</code>
                        <p>Whale transaction tracker</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/alerts</code>
                        <p>Manage price alerts</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/news</code>
                        <p>Latest crypto news</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/price [symbol]</code>
                        <p>Current price data</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/sr [symbol]</code>
                        <p>Support/Resistance levels</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/sentiment [symbol]</code>
                        <p>Market sentiment analysis</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/analyze [symbol]</code>
                        <p>AI-powered analysis</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/predict [symbol]</code>
                        <p>AI price predictions</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/flow [symbol]</code>
                        <p>Money flow analysis</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/rates [symbol]</code>
                        <p>Funding rates</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/liquidation</code>
                        <p>Liquidation heatmaps</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/divergence</code>
                        <p>Divergence detection</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/scan</code>
                        <p>Market scanner</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/radar</code>
                        <p>Market radar</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/portfolio</code>
                        <p>Portfolio tracking</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/ask [question]</code>
                        <p>Ask any trading question</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/backtest</code>
                        <p>Strategy backtesting</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/calendar</code>
                        <p>Economic calendar</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/help</code>
                        <p>View all 40+ commands</p>
                    </div>
                </div>
            </div>
            <p style="text-align: center; margin-top: 30px; color: rgba(255, 255, 255, 0.7);">
                💡 Type <code style="color: #00ffff;">/help</code> in the bot to see all available commands and features
            </p>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <h2 class="section-title">Platform Capabilities</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h4>40+</h4>
                    <p>Bot Commands</p>
                </div>
                <div class="stat-item">
                    <h4>3</h4>
                    <p>Market Types</p>
                </div>
                <div class="stat-item">
                    <h4>24/7</h4>
                    <p>Live Monitoring</p>
                </div>
                <div class="stat-item">
                    <h4>AI</h4>
                    <p>Powered Analysis</p>
                </div>
            </div>
        </section>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2026 SERPO AI. All rights reserved.</p>
            <p>
                <a href="https://serpocoin.io" target="_blank">SerpoCoin</a>
                <a href="https://t.me/SerpoAI_bot" target="_blank">Telegram Bot</a>
                <a href="https://t.me/serpocoin" target="_blank">Community</a>
            </p>
            <p style="color: rgba(0,255,255,0.6); margin-top: 15px; font-size: 13px;">
                Preview Mode • Features unlock progressively • No pressure, only progress
            </p>
        </div>
    </footer>
</body>

</html>