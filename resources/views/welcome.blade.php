<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERPO AI - Crypto Trading Intelligence</title>
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
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            padding: 15px 20px;
            margin: 30px 0;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
        }

        .beta-banner h3 {
            background: linear-gradient(90deg, #00ffff 0%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .beta-banner p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
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
            <h3>🚧 BETA VERSION - Under Active Development 🚧</h3>
            <p>We're actively building and improving SerpoAI. Some features may be incomplete or in testing.</p>
        </div>

        <!-- Hero Section -->
        <section class="hero">
            <h1>SERPO AI</h1>
            <p>Your intelligent Telegram companion for crypto trading insights, market analysis, and SERPO token information</p>
            <div class="cta-buttons">
                <a href="https://t.me/SerpoAI_bot" class="btn btn-primary" target="_blank">Start on Telegram</a>
                <a href="https://serpocoin.io" class="btn btn-secondary" target="_blank">Visit Serpocoin.io</a>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <h2 class="section-title">Powered by AI Intelligence</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Real-Time Market Data</h3>
                    <p>Get instant SERPO token price, market cap, and trading volume directly in Telegram</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Technical Analysis</h3>
                    <p>Interactive price charts with support/resistance levels and trading indicators</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>AI-Powered Insights</h3>
                    <p>Smart trading signals and market sentiment analysis using advanced AI</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3>Custom Alerts</h3>
                    <p>Set price alerts and get instant notifications when your targets are hit</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Natural Conversations</h3>
                    <p>Ask anything about SERPO or crypto markets in plain English</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Educational Resources</h3>
                    <p>Learn about SERPO token, blockchain technology, and trading strategies</p>
                </div>
            </div>
        </section>

        <!-- Commands Section -->
        <section class="commands">
            <h2 class="section-title">Available Commands</h2>
            <div class="commands-grid">
                <div class="command-item">
                    <div class="command-content">
                        <code>/start</code>
                        <p>Begin your journey with SerpoAI</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/price</code>
                        <p>Check current SERPO price</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/chart</code>
                        <p>View interactive price charts</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/signals</code>
                        <p>Get AI trading signals</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/sentiment</code>
                        <p>Market sentiment analysis</p>
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
                        <code>/explain</code>
                        <p>Learn about crypto concepts</p>
                    </div>
                </div>
                <div class="command-item">
                    <div class="command-content">
                        <code>/ask</code>
                        <p>Ask any crypto question</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <h2 class="section-title">Why Choose SerpoAI?</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h4>24/7</h4>
                    <p>Always Available</p>
                </div>
                <div class="stat-item">
                    <h4>13+</h4>
                    <p>Smart Commands</p>
                </div>
                <div class="stat-item">
                    <h4>&lt;5min</h4>
                    <p>Response Time</p>
                </div>
                <div class="stat-item">
                    <h4>AI</h4>
                    <p>Powered Intelligence</p>
                </div>
            </div>
        </section>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 SerpoAI. All rights reserved.</p>
            <p>
                <a href="https://serpocoin.io" target="_blank">Serpocoin.io</a>
                <a href="https://t.me/SerpoAI_bot" target="_blank">Telegram Bot</a>
            </p>
            <p style="color: rgba(0,255,255,0.6); margin-top: 10px;">Beta v0.1.0 - Under Active Development</p>
        </div>
    </footer>
</body>

</html>