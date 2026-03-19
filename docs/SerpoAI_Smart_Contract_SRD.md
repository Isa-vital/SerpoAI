# Project Scope & Requirements Document

## Serpo AI — Web Platform, Smart Contracts & Grid Bot System

| Field | Detail |
|---|---|
| **Document Title** | Project Scope & Requirements Document — Serpo AI Web Platform, Smart Contracts & Grid Bot System |
| **Version** | 4.0 |
| **Date** | March 19, 2026 |
| **Developer** | Isaac Mukonyezi — Tech Market Ug |
| **Client** | Serpocoin Project |
| **Status** | For Agreement |

---

## Document Revision History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0 | March 12, 2026 | SerpoAI Team | Initial technical SRD |
| 2.0 | March 13, 2026 | SerpoAI Team | Expanded with Collateral Vault and Grid Bot |
| 3.0 | March 13, 2026 | Isaac Mukonyezi | Restructured as stakeholder scope document |
| 4.0 | March 19, 2026 | Isaac Mukonyezi | Added full web platform redesign, updated tech stack (Inertia.js + React), revised timeline and cost |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Project Scope](#2-project-scope)
3. [Deliverables](#3-deliverables)
4. [Smart Contract Overview](#4-smart-contract-overview)
5. [Multi-Market Grid Bot Overview](#5-multi-market-grid-bot-overview)
6. [How It Works — User Journey](#6-how-it-works--user-journey)
7. [Technical Approach](#7-technical-approach)
8. [Timeline](#8-timeline)
9. [Cost & Payment Terms](#9-cost--payment-terms)
10. [Acceptance Criteria](#10-acceptance-criteria)
11. [Assumptions & Dependencies](#11-assumptions--dependencies)
12. [Risks](#12-risks)
13. [Post-Launch Support](#13-post-launch-support)
14. [Agreement](#14-agreement)

---

## 1. Introduction

### 1.1 Purpose

This document defines the project scope, deliverables, timeline, and cost for developing a full web platform redesign, a smart contract system, and a multi-market grid bot (crypto, forex, stocks) for the Serpo AI platform. It serves as a mutual agreement between the developer and the client on what will be built, what will not be built, and the terms of engagement.

### 1.2 Background

Serpo AI is a production-deployed AI-powered trading intelligence platform accessible via Telegram bot (40+ commands) and website (ai.serpocoin.io). The platform currently provides:

- AI-powered market analysis (OpenAI GPT-4o-mini, Google Gemini, Groq)
- Real-time blockchain monitoring via TonAPI
- Technical analysis (RSI, MACD, EMA, support/resistance, divergence)
- Token verification across 20+ chains
- Whale alert tracking
- Portfolio management and custom price alerts

**Currently, all features are free and accessible only via Telegram.** The existing website (ai.serpocoin.io) is a basic static landing page. This project introduces a modern data-driven web platform exposing all 40+ features, a SerpoCoin-powered subscription model, and automated multi-market grid trading (crypto, forex, stocks) to create a sustainable revenue stream and attract users.

### 1.3 Existing SERPO Token

The SerpoCoin (SERPO) Jetton token is already deployed on TON Mainnet:

**Contract Address:** `EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw`

No new token will be created. All smart contracts in this project integrate with this existing token.

---

## 2. Project Scope

### 2.1 What Will Be Developed (In Scope)

**A. Smart Contracts on TON Blockchain**
- Subscription Contract — monthly SERPO payment unlocks AI tool access
- Fee Distribution Contract — automated 7% trading fee split
- Vault Contract — owner-controlled treasury for platform revenue
- Collateral Vault Contract — user deposits to guarantee performance fees

**B. Backend Integration (extending existing Laravel application)**
- New Laravel services for subscription verification, wallet connection, referral tracking, collateral management, and grid bot control
- Database migrations for new tables (subscriptions, referrals, collateral, grid bot configurations, trades, backtests)
- New API endpoints for subscription, referral, collateral, and grid bot operations
- New Telegram bot commands (`/subscribe`, `/referral`, `/collateral`, `/mystatus`, `/grid`, `/grid_start`, `/grid_stop`, `/backtest`)

**C. Full Web Platform Redesign**
- Complete redesign of the web frontend from a static landing page to a modern, data-driven platform (CoinGecko-style, dark theme)
- All 40+ existing Telegram bot features exposed on the web (market data, technical analysis, signals, whale alerts, portfolio, AI predictions, charts, heatmaps, news, education)
- 12 main sections: Dashboard, Market Data, Technical Analysis, Derivatives & Flow, Whale Activity, Charts & Visualization, Trading, AI & Predictions, Token Metrics, News & Learning, Copy Trading, Account
- Telegram Login Widget for authentication (same user identity as bot, no new accounts)
- TON Connect wallet connection (Tonkeeper, MyTonWallet, OpenMask)
- Subscription, referral, collateral, and grid bot dashboards integrated into the web platform

**D. Backend API Layer**
- New API controller with ~30 endpoints to serve the web platform
- All existing services (42) exposed via REST API to the frontend
- Telegram Login verification middleware

**E. Multi-Market Grid Bot Engine**
- Crypto exchange connectors: Binance, KuCoin, and Bybit (REST + WebSocket)
- Forex broker connectors: OANDA (REST API v20), FXCM (REST API), MetaTrader 5 (Python API)
- Stock broker connectors: Interactive Brokers (REST API + WebSocket), Alpaca (REST API v2 + WebSocket)
- Adaptive grid engine with ATR-based spacing and dynamic order sizing
- AI signal layer for trend detection and grid activation/pause
- Risk management (stop-loss, max exposure, leverage control)
- Backtesting engine with historical data simulation across all 3 markets
- Real-time monitoring and position tracking

### 2.2 What Will NOT Be Developed (Out of Scope)

| Item | Reason |
|------|--------|
| Mobile app changes | Existing app not modified |
| New token creation | SERPO token already exists on TON Mainnet |
| Marketing & user acquisition | Client responsibility |
| Community management | Client responsibility |
| Third-party security audit | Recommended but requires separate engagement and budget |

---

## 3. Deliverables

Upon completion, the following will be delivered:

| # | Deliverable | Description |
|:-:|-------------|-------------|
| 1 | **Full Web Platform** | Complete redesign of ai.serpocoin.io — 12 sections, 25 pages, all 40+ features accessible on the web with modern dark-theme UI (CoinGecko-style) |
| 2 | **API Controller Layer** | ~30 REST API endpoints serving the web platform, backed by existing 42 services |
| 3 | **Telegram Login Integration** | Web authentication via Telegram Login Widget — same user identity as Telegram bot |
| 4 | **Subscription Contract** | Tact smart contract deployed to TON Mainnet — accepts SERPO payment, activates 30-day subscription |
| 5 | **Fee Distribution Contract** | Tact smart contract — splits 7% trading fee (4% vault, 3% referrer) |
| 6 | **Vault Contract** | Tact smart contract — accumulates platform revenue, owner-only withdrawal |
| 7 | **Collateral Vault Contract** | Tact smart contract — manages user collateral deposits for CEX trade fee guarantees |
| 8 | **Backend Services** | 5 new Laravel services: SubscriptionVerificationService, WalletConnectionService, ReferralTrackingService, CollateralVaultService, GridBotService |
| 9 | **Database Migrations** | 8+ new database tables integrated with existing user system |
| 10 | **Telegram Bot Commands** | 8 new commands: `/subscribe`, `/referral`, `/collateral`, `/mystatus`, `/grid`, `/grid_start`, `/grid_stop`, `/backtest` |
| 11 | **TON Connect Integration** | Wallet connection flow for website and Telegram (QR code + deep link) |
| 12 | **Subscription Dashboard** | Web page showing subscription status, expiry, and payment history |
| 13 | **Referral Dashboard** | Web page showing referral link, referred users, and earnings |
| 14 | **Multi-Market Grid Bot Engine** | Trading engine with connectors for Binance/KuCoin/Bybit (crypto), OANDA/FXCM/MT5 (forex), Interactive Brokers/Alpaca (stocks) — adaptive grid, AI signals, risk management |
| 15 | **Grid Bot Monitoring Dashboard** | Web page showing active grids, P/L, trade history, and grid level visualization |
| 16 | **Backtesting Engine** | Historical data simulation to validate grid strategies before live trading |
| 17 | **Testnet Deployment** | All 4 contracts deployed and tested on TON Testnet |
| 18 | **Mainnet Deployment** | All 4 contracts deployed to TON Mainnet, backend live, grid bot operational |

---

## 4. Smart Contract Overview

### 4.1 Subscription Contract

**Purpose:** Accepts SERPO token payments from users and activates a 30-day subscription to Serpo AI's premium features.

**How it works:**
1. User sends SERPO tokens to the contract
2. Contract verifies the correct amount was sent
3. If the user was referred by someone, the payment is split 50/50 — half to the referrer, half to the vault
4. If no referrer, 100% goes to the vault
5. User's subscription is activated for 30 days
6. If the user already has an active subscription, 30 days are added to the existing expiry

**Subscription price:** Equivalent of $10 USD in SERPO tokens (dynamically adjustable by contract owner).

### 4.2 Fee Distribution Contract

**Purpose:** Handles the 7% performance fee charged on profitable trades made by the platform's trading bots.

**How it works:**
1. When a user's trade is profitable, a 7% fee is calculated
2. The fee (in SERPO tokens) is sent to this contract
3. Contract splits the fee: **4% to the vault** (platform revenue), **3% to the user's referrer**
4. If the user has no referrer, the full 7% goes to the vault
5. No fee is charged on losing trades

### 4.3 Vault Contract

**Purpose:** Treasury contract that accumulates all platform revenue (subscription payments + trading fees).

**How it works:**
1. Receives SERPO tokens from the Subscription Contract and Fee Distribution Contract
2. Tracks total revenue received
3. Only the designated owner can withdraw funds
4. Balance is publicly queryable on-chain for transparency

### 4.4 Collateral Vault Contract

**Purpose:** Allows users to deposit SERPO tokens as collateral to guarantee performance fee payment when trading on centralized exchanges (Binance, KuCoin, Bybit).

**How it works:**
1. User deposits SERPO tokens into the contract — balance tracked per wallet
2. When the platform records a profitable CEX trade, the 7% fee is deducted from the user's collateral balance
3. Deducted fees are split: 4% to vault, 3% to referrer (same as Fee Distribution)
4. Users can withdraw their unused collateral at any time
5. Only the wallet owner can withdraw their own collateral

**Why collateral is needed:** On centralized exchanges, the platform cannot deduct fees on-chain during the trade. Collateral ensures fees can always be collected after a profitable trade.

**Note:** Collateral is NOT required for DEX trades (STON.fi / DeDust) — fees are deducted automatically on-chain during the trade.

### 4.5 Payment Flow Summary

```
                    ┌──────────────────────┐
                    │   User Pays SERPO    │
                    │   (Subscription)     │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  Subscription        │
                    │  Contract            │
                    └──────────┬───────────┘
                               │
                ┌──────────────┼──────────────┐
                │ Has Referrer?│               │
                ▼              ▼               │
        ┌───────────┐  ┌───────────┐          │
        │ 50% to    │  │ 50% to    │    No Referrer:
        │ Referrer  │  │ Vault     │    100% to Vault
        └───────────┘  └───────────┘          │
                                              ▼
                                       ┌───────────┐
                                       │   Vault   │
                                       │ Contract  │
                                       └───────────┘


                    ┌──────────────────────┐
                    │  Profitable Trade    │
                    │  (7% Fee)            │
                    └──────────┬───────────┘
                               │
            ┌──────────────────┼──────────────────┐
            │ CEX Trade        │                   │ DEX Trade
            ▼                  │                   ▼
    ┌───────────────┐          │          ┌────────────────┐
    │  Deduct from  │          │          │  Auto-deduct   │
    │  Collateral   │          │          │  on-chain      │
    │  Vault        │          │          │  during swap   │
    └───────┬───────┘          │          └────────┬───────┘
            │                  │                   │
            └──────────────────┼───────────────────┘
                               │
                    ┌──────────▼───────────┐
                    │  Fee Distribution    │
                    │  Contract            │
                    └──────────┬───────────┘
                               │
                    ┌──────────┼──────────┐
                    ▼                     ▼
             ┌───────────┐        ┌───────────┐
             │ 4% Vault  │        │ 3% Referrer│
             └───────────┘        └───────────┘
```

---

## 5. Multi-Market Grid Bot Overview

### 5.1 What Is a Grid Bot?

A grid bot is an automated trading strategy that places buy and sell orders at preset price intervals (a "grid") above and below a set price. The bot profits from price oscillations — buying low and selling high repeatedly as the price moves within the grid range.

### 5.2 Supported Exchanges & Brokers

**Crypto Exchanges**

| Exchange | Connection | Markets |
|----------|-----------|--------|
| **Binance** | REST API + WebSocket | Spot + Futures |
| **KuCoin** | REST API + WebSocket | Spot + Futures |
| **Bybit** | REST API + WebSocket | Spot + Futures |

**Forex Brokers**

| Broker | Connection | Markets |
|--------|-----------|--------|
| **OANDA** | REST API v20 | 108+ forex pairs, commodities (XAU, XAG) |
| **FXCM** | REST API | Forex pairs, practice accounts |
| **MetaTrader 5** | Python API (MetaTrader5 package) | Forex pairs, commodities |

**Stock Brokers**

| Broker | Connection | Markets |
|--------|-----------|--------|
| **Interactive Brokers** | REST API + WebSocket | 103+ global exchanges (US, UK, Europe, Asia) |
| **Alpaca** | REST API v2 + WebSocket | US equities (NYSE, NASDAQ), fractional shares |

### 5.3 Key Features

**Adaptive Grid Engine**
- Dynamic grid spacing based on market volatility (ATR-based)
- Automatic order sizing based on capital allocation
- Partial profit-taking at grid levels
- Support for both long and short grids

**AI Signal Layer**
- Detects market conditions (sideways vs trending)
- Automatically pauses grids during strong trends (to prevent losses)
- Volatility analysis for optimal grid spacing
- Resumes grids when sideways conditions return

**Risk Management**
- Maximum exposure limits per trading pair
- Stop-loss protection
- Leverage control for margin/futures accounts
- Real-time risk alerts via Telegram

**Backtesting**
- Test strategies against historical price data before risking real money
- Simulated P/L, win rate, max drawdown, and Sharpe ratio reports

**Monitoring Dashboard**
- Real-time profit/loss visualization
- Active grid level display
- Trade history and performance analytics
- Accessible via web dashboard and Telegram commands

### 5.4 How It Connects to Smart Contracts

When the grid bot closes a profitable trade:
1. The 7% performance fee is calculated
2. Fee is deducted from the user's SERPO collateral (via Collateral Vault Contract)
3. Fee is split: 4% to vault, 3% to referrer (via Fee Distribution Contract)
4. User keeps 93% of profits

---

## 6. How It Works — User Journey

```
┌──────────────────────────────────────────────────────────────────────┐
│                          USER JOURNEY                                │
│                                                                      │
│  ACCESS VIA WEB                       ACCESS VIA TELEGRAM            │
│  ┌───────────────────┐                ┌───────────────────┐          │
│  │ ai.serpocoin.io    │                │ @SerpoAIBot       │          │
│  │ Log in with        │                │ /start             │          │
│  │ Telegram Login     │                │                    │          │
│  └────────┬──────────┘                └─────────┬─────────┘          │
│           │                                     │                    │
│           └──────────────┬──────────────────────┘                    │
│                          ▼                                           │
│                  Same User Identity                                  │
│                  (telegram_id)                                       │
│                          │                                           │
│  1. Connect       2. Subscribe      3. Deposit Collateral            │
│     Wallet     →     with SERPO  →     (for grid trading)            │
│                                                                      │
│  4. Use AI Tools     5. Start Grid Bot    6. Refer Friends           │
│     Web: Dashboard →    Web: Grid UI    →    Share link              │
│     Bot: /predict       Bot: /grid_start     Earn 50% subs          │
│     Bot: /analyze       Bot: /backtest       Earn 3% fees           │
│     Bot: /rsi           Bot: /grid                                   │
│     Bot: /scan                                                       │
│                                                                      │
│  ┌──────────┐   ┌───────────────┐   ┌───────────────────┐            │
│  │ Tonkeeper│   │ Pay SERPO     │   │ Grid bot trades   │            │
│  │ Scan QR  │   │ ($10/month)   │   │ on 8 connectors:  │            │
│  │          │   │               │   │ Crypto: Binance,  │            │
│  └────┬─────┘   └──────┬────────┘   │  KuCoin, Bybit    │            │
│       │                │            │ Forex: OANDA,     │            │
│       │                │            │  FXCM, MT5        │            │
│       │                │            │ Stocks: IB,       │            │
│       │                │            │  Alpaca           │            │
│       │                │            └───────────────────┘            │
│       ▼                ▼                                             │
│  Wallet Address   Smart Contract                                     │
│  = User ID        Processes Payment                                  │
│                   Activates 30-Day Access                             │
│                   Manages Collateral                                  │
│                   Distributes Fees                                    │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 7. Technical Approach

| Component | Technology | Notes |
|-----------|-----------|-------|
| **Smart Contracts** | Tact language on TON blockchain | 4 contracts, deployed via TON Blueprint |
| **Backend** | Laravel 12 (PHP 8.2+) | Existing application — extended, not rebuilt |
| **Database** | PostgreSQL / MySQL | Existing database — new tables added via migrations |
| **Grid Bot Engine** | Python 3.12+ | pandas, NumPy, TA-Lib for analysis; asyncio for real-time execution |
| **Crypto APIs** | Binance, KuCoin, Bybit | REST + WebSocket — spot and futures |
| **Forex APIs** | OANDA v20, FXCM REST, MT5 Python | REST + streaming — 108+ forex pairs, commodities |
| **Stock APIs** | Interactive Brokers, Alpaca v2 | REST + WebSocket — US and global equities |
| **Wallet Integration** | TON Connect 2.0 | Supports Tonkeeper, MyTonWallet, OpenMask |
| **Blockchain Data** | TonAPI v2 | Subscription verification, transaction monitoring |
| **Frontend** | Inertia.js 2.0 + React 19 | Integrated with Laravel — no separate API server needed; Tailwind CSS 4.0 + Vite 7.0 |
| **Telegram** | Telegram Bot API | Existing bot — new commands added |
| **Hosting** | Existing VPS (ai.serpocoin.io) | No new infrastructure required |

**Key principle:** The existing Serpo AI platform is extended — not rebuilt. All 40+ existing bot commands continue to work unchanged.

---

## 8. Timeline

**Estimated duration:** 14–16 weeks (working 5 hours/day)

| Phase | Week(s) | Deliverables |
|-------|:-------:|-------------|
| **Web Platform Redesign** | 1–3 | Full web platform — 12 sections, 25 pages, Inertia.js + React, Telegram Login, ~30 API endpoints |
| **Smart Contracts** | 4–5 | All 4 contracts developed in Tact, unit tested, deployed to TON Testnet |
| **Backend Integration** | 6 | Laravel services, database migrations, API endpoints, Telegram bot commands |
| **Frontend New Features** | 7 | TON Connect integration, subscription dashboard, referral dashboard on web |
| **Crypto Grid Bot** | 8–9 | Crypto exchange connectors (Binance, KuCoin, Bybit), adaptive grid engine, AI signal layer |
| **Forex & Stock Connectors** | 10–11 | Forex connectors (OANDA, FXCM, MT5), stock connectors (Interactive Brokers, Alpaca), market-specific adapters |
| **Grid Bot Completion** | 12 | Risk management, backtesting engine (all 3 markets), monitoring dashboard |
| **Testing** | 13 | End-to-end testing across all components and all 3 markets, bug fixes |
| **Launch** | 14 | Mainnet deployment, grid bot production launch, soft launch monitoring |

**Weeks 15–16** are reserved as a buffer for unexpected issues.

> **Note:** Timeline assumes client provides timely access to server, codebase, API keys, and broker accounts (OANDA demo, Alpaca paper trading). Delays in client-side dependencies will extend the timeline accordingly.

---

## 9. Cost & Payment Terms

### 9.1 Project Cost

| Item | Amount |
|------|--------|
| **Total Project Cost** | **2,750,000 UGX** |

### 9.2 Payment Schedule

| Payment | Amount | When |
|---------|--------|------|
| **Payment 1 (40%)** | **1,100,000 UGX** | Before work begins |
| **Payment 2 (60%)** | **1,650,000 UGX** | After successful launch (see Acceptance Criteria in Section 10) |

### 9.3 What's Included

- All development work listed in Section 3 (Deliverables)
- Testnet deployment and testing
- Mainnet deployment
- 1 month of free post-launch support (see Section 13)

### 9.4 What's NOT Included

- Third-party security audit (separate engagement, estimated $2,000–$10,000 USD)
- Ongoing hosting costs (client's existing server used)
- Exchange/broker API subscription fees (if applicable)
- Forex broker account fees (OANDA, FXCM) and stock broker fees (Interactive Brokers)
- TON blockchain gas fees for contract deployment (~2–5 TON, ~$10–$25)
- Any work outside the scope defined in Section 2

---

## 10. Acceptance Criteria

"Launch" — and therefore the trigger for Payment 2 — is defined as **all** of the following being met:

| # | Criterion | How It's Verified |
|:-:|-----------|-------------------|
| 1 | Web platform loads at ai.serpocoin.io — all 12 sections accessible | Navigate every section in browser |
| 2 | Telegram Login works — user clicks "Log in with Telegram", lands on authenticated dashboard | Live test |
| 3 | Market data loads on web — prices, charts, whale alerts render correctly | Visual verification |
| 4 | All 40+ existing bot features are accessible on web (same data, same user) | Cross-check web vs. Telegram |
| 5 | All 4 smart contracts are deployed to TON Mainnet | Contract addresses provided, verified on-chain |
| 6 | Subscription flow works end-to-end: user pays SERPO → subscription activates for 30 days | Live test with real SERPO payment |
| 7 | Referral system works: shared link → referrer earns 50% of subscription payment | Balance verification |
| 8 | Fee Distribution splits correctly: 4% vault, 3% referrer | On-chain transaction verification |
| 9 | Collateral deposit and withdrawal works | Live test with real SERPO |
| 10 | All 8 new Telegram bot commands respond correctly | Manual testing of each command |
| 11 | TON Connect wallet connection works on website | Connect using Tonkeeper or MyTonWallet |
| 12 | Grid bot starts, executes trades, and stops on Binance (crypto) | Live test on Binance (can use testnet or small amount) |
| 13 | Grid bot starts, executes trades, and stops on OANDA (forex) | Live test on OANDA demo/practice account |
| 14 | Grid bot starts, executes trades, and stops on Alpaca (stocks) | Live test on Alpaca paper trading account |
| 15 | Grid bot monitoring dashboard shows P/L and active grid status across all 3 markets | Visual verification |
| 16 | Backtesting engine returns simulation results for crypto, forex, and stocks | Run backtest command and verify output |

---

## 11. Assumptions & Dependencies

| # | Assumption | Owner |
|:-:|-----------|-------|
| 1 | SERPO token is already deployed and operational on TON Mainnet | Client |
| 2 | SERPO token has sufficient liquidity on DeDust or STON.fi for price discovery | Client |
| 3 | Client provides access to existing server and Laravel codebase | Client |
| 4 | Client provides TON API key for blockchain queries | Client |
| 5 | Client provides exchange API keys for crypto grid bot testing (Binance at minimum) | Client |
| 6 | Client provides OANDA demo/practice account for forex grid bot testing | Client |
| 7 | Client provides Alpaca paper trading account for stock grid bot testing | Client |
| 8 | Client handles marketing, user acquisition, and community management | Client |
| 9 | Client provides timely feedback during development (within 48 hours) | Client |
| 10 | Users have access to TON-compatible wallets (Tonkeeper, MyTonWallet) | Users |
| 11 | Existing Serpo AI bot and website remain operational during development | Client |

---

## 12. Risks

| # | Risk | Impact | Mitigation |
|:-:|------|--------|-----------|
| 1 | Smart contract vulnerability discovered after deployment | High — potential loss of funds | Thorough testnet testing; external audit recommended (not included in scope) |
| 2 | SERPO token has insufficient DEX liquidity | Medium — subscription pricing unreliable | Client to ensure liquidity before launch |
| 3 | TON network congestion delays transactions | Low — temporary user inconvenience | Retry logic and cached subscription status |
| 4 | Grid bot incurs trading losses | Medium — user dissatisfaction | AI signal layer pauses in trending markets; stop-loss protection; paper trading mode available |
| 5 | Exchange/broker API changes break grid bot connectors | Medium — requires maintenance | API version pinning; abstraction layer for easy updates |
| 6 | Forex/stock broker regulatory restrictions limit access in certain regions | Medium — some connectors may not work for all users | Document supported regions; OANDA and Alpaca have broad availability |
| 7 | MetaTrader 5 or Interactive Brokers API complexity causes delays | Medium — could extend forex/stock connector development | Use well-documented Python libraries (MetaTrader5 package, ib_insync); allocate buffer weeks |
| 8 | No users adopt the platform after launch | High — no revenue generated | This is a marketing/product risk, not a development risk. Developer is not responsible for user acquisition |
| 9 | Client delays providing access or feedback | Medium — extends timeline | Timeline will be extended day-for-day for client-caused delays |

---

## 13. Post-Launch Support

### 13.1 Free Support Period

**Duration:** 1 month from launch date

**Included:**
- Bug fixes for delivered features
- Minor adjustments to deployed contracts (parameter changes only — not logic changes)
- Backend/frontend bug fixes
- Grid bot connector fixes if an exchange or broker API changes during the support period

**NOT included during free support:**
- New features or functionality not listed in Section 3
- New exchange/broker connectors beyond the 8 delivered (Binance, KuCoin, Bybit, OANDA, FXCM, MT5, Interactive Brokers, Alpaca)
- Design or UX changes beyond the delivered web platform
- Performance optimization beyond original specifications

### 13.2 After Free Support Period

Ongoing support and maintenance will be billed separately at a rate to be agreed upon before the free period ends. If no agreement is reached, support ends after the free month.

---

## 14. Agreement

By signing below, both parties agree to the project scope, deliverables, timeline, cost, and terms described in this document.

### Developer

| Field | Detail |
|-------|--------|
| **Name** | Isaac Mukonyezi |
| **Company** | Tech Market Ug |
| **Signature** | _________________________ |
| **Date** | _________________________ |

### Client

| Field | Detail |
|-------|--------|
| **Name** | _________________________ |
| **Company** | Serpocoin Project |
| **Signature** | _________________________ |
| **Date** | _________________________ |

---

*This document constitutes the complete scope agreement for the Serpo AI Smart Contract & Grid Bot System project. Any changes to scope, timeline, or cost must be agreed upon in writing by both parties.*

**Document End — Serpo AI Project Scope & Requirements Document v3.0**
