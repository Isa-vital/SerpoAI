# Project Scope & Requirements Document

## Serpo AI — Smart Contract & Grid Bot System

| Field | Detail |
|---|---|
| **Document Title** | Project Scope & Requirements Document — Serpo AI Smart Contract & Grid Bot System |
| **Version** | 3.0 |
| **Date** | March 13, 2026 |
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

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Project Scope](#2-project-scope)
3. [Deliverables](#3-deliverables)
4. [Smart Contract Overview](#4-smart-contract-overview)
5. [Crypto Grid Bot Overview](#5-crypto-grid-bot-overview)
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

This document defines the project scope, deliverables, timeline, and cost for developing a smart contract system and crypto grid bot for the Serpo AI platform. It serves as a mutual agreement between the developer and the client on what will be built, what will not be built, and the terms of engagement.

### 1.2 Background

Serpo AI is a production-deployed AI-powered trading intelligence platform accessible via Telegram bot (40+ commands) and website (ai.serpocoin.io). The platform currently provides:

- AI-powered market analysis (OpenAI GPT-4o-mini, Google Gemini, Groq)
- Real-time blockchain monitoring via TonAPI
- Technical analysis (RSI, MACD, EMA, support/resistance, divergence)
- Token verification across 20+ chains
- Whale alert tracking
- Portfolio management and custom price alerts

**Currently, all features are free.** This project introduces a SerpoCoin-powered subscription model and automated crypto grid trading to create a sustainable revenue stream and attract users.

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

**C. Frontend Integration**
- TON Connect wallet connection (Tonkeeper, MyTonWallet, OpenMask)
- Subscription status dashboard
- Referral dashboard with link sharing and earnings history
- Grid bot monitoring dashboard with P/L visualization

**D. Crypto Grid Bot Engine**
- Exchange connectors for Binance, KuCoin, and Bybit (REST + WebSocket)
- Adaptive grid engine with ATR-based spacing and dynamic order sizing
- AI signal layer for trend detection and grid activation/pause
- Risk management (stop-loss, max exposure, leverage control)
- Backtesting engine with historical data simulation
- Real-time monitoring and position tracking

### 2.2 What Will NOT Be Developed (Out of Scope)

| Item | Reason |
|------|--------|
| Forex trading (OANDA, FXCM, MetaTrader 4/5) | Not included in this phase |
| Stock trading (Interactive Brokers, Alpaca) | Not included in this phase |
| Mobile app changes | Existing app not modified |
| New token creation | SERPO token already exists on TON Mainnet |
| Marketing & user acquisition | Client responsibility |
| Community management | Client responsibility |
| Third-party security audit | Recommended but requires separate engagement and budget |
| UI/UX design work | Developer builds functional interfaces using existing design patterns |

---

## 3. Deliverables

Upon completion, the following will be delivered:

| # | Deliverable | Description |
|:-:|-------------|-------------|
| 1 | **Subscription Contract** | Tact smart contract deployed to TON Mainnet — accepts SERPO payment, activates 30-day subscription |
| 2 | **Fee Distribution Contract** | Tact smart contract — splits 7% trading fee (4% vault, 3% referrer) |
| 3 | **Vault Contract** | Tact smart contract — accumulates platform revenue, owner-only withdrawal |
| 4 | **Collateral Vault Contract** | Tact smart contract — manages user collateral deposits for CEX trade fee guarantees |
| 5 | **Backend Services** | 5 new Laravel services: SubscriptionVerificationService, WalletConnectionService, ReferralTrackingService, CollateralVaultService, GridBotService |
| 6 | **Database Migrations** | 8+ new database tables integrated with existing user system |
| 7 | **API Endpoints** | REST APIs for subscription, referral, collateral, and grid bot operations |
| 8 | **Telegram Bot Commands** | 8 new commands: `/subscribe`, `/referral`, `/collateral`, `/mystatus`, `/grid`, `/grid_start`, `/grid_stop`, `/backtest` |
| 9 | **TON Connect Integration** | Wallet connection flow for website and Telegram (QR code + deep link) |
| 10 | **Subscription Dashboard** | Web interface showing subscription status, expiry, and payment history |
| 11 | **Referral Dashboard** | Web interface showing referral link, referred users, and earnings |
| 12 | **Crypto Grid Bot Engine** | Trading engine with Binance/KuCoin/Bybit connectors, adaptive grid, AI signals, risk management |
| 13 | **Grid Bot Monitoring Dashboard** | Web interface showing active grids, P/L, trade history, and grid level visualization |
| 14 | **Backtesting Engine** | Historical data simulation to validate grid strategies before live trading |
| 15 | **Testnet Deployment** | All 4 contracts deployed and tested on TON Testnet |
| 16 | **Mainnet Deployment** | All 4 contracts deployed to TON Mainnet, backend live, grid bot operational |

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

## 5. Crypto Grid Bot Overview

### 5.1 What Is a Grid Bot?

A grid bot is an automated trading strategy that places buy and sell orders at preset price intervals (a "grid") above and below a set price. The bot profits from price oscillations — buying low and selling high repeatedly as the price moves within the grid range.

### 5.2 Supported Exchanges

| Exchange | Connection | Markets |
|----------|-----------|---------|
| **Binance** | REST API + WebSocket | Spot + Futures |
| **KuCoin** | REST API + WebSocket | Spot + Futures |
| **Bybit** | REST API + WebSocket | Spot + Futures |

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
┌─────────────────────────────────────────────────────────────┐
│                        USER JOURNEY                          │
│                                                              │
│  1. Connect       2. Subscribe      3. Deposit Collateral   │
│     Wallet     →     with SERPO  →     (for grid trading)   │
│                                                              │
│  4. Use AI Tools     5. Start Grid Bot    6. Refer Friends   │
│     /predict      →    /grid_start     →    Share link       │
│     /analyze           /backtest           Earn 50% subs     │
│     /rsi               /grid               Earn 3% fees     │
│     /scan                                                    │
│                                                              │
│  ┌──────────┐   ┌───────────────┐   ┌───────────────────┐   │
│  │ Tonkeeper│   │ Pay SERPO     │   │ Grid bot trades   │   │
│  │ Scan QR  │   │ ($10/month)   │   │ automatically on  │   │
│  │          │   │               │   │ Binance/KuCoin/   │   │
│  └────┬─────┘   └──────┬────────┘   │ Bybit             │   │
│       │                │            └───────────────────┘   │
│       ▼                ▼                                     │
│  Wallet Address   Smart Contract                             │
│  = User ID        Processes Payment                          │
│                   Activates 30-Day Access                     │
│                   Manages Collateral                          │
│                   Distributes Fees                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. Technical Approach

| Component | Technology | Notes |
|-----------|-----------|-------|
| **Smart Contracts** | Tact language on TON blockchain | 4 contracts, deployed via TON Blueprint |
| **Backend** | Laravel 12 (PHP 8.2+) | Existing application — extended, not rebuilt |
| **Database** | PostgreSQL / MySQL | Existing database — new tables added via migrations |
| **Grid Bot Engine** | Python 3.12+ | pandas, NumPy, TA-Lib for analysis; asyncio for real-time execution |
| **Exchange Connections** | REST API + WebSocket | Real-time price feeds and order management |
| **Wallet Integration** | TON Connect 2.0 | Supports Tonkeeper, MyTonWallet, OpenMask |
| **Blockchain Data** | TonAPI v2 | Subscription verification, transaction monitoring |
| **Frontend** | React / Next.js | Web dashboards for subscription, referral, grid bot monitoring |
| **Telegram** | Telegram Bot API | Existing bot — new commands added |
| **Hosting** | Existing VPS (ai.serpocoin.io) | No new infrastructure required |

**Key principle:** The existing Serpo AI platform is extended — not rebuilt. All 40+ existing bot commands continue to work unchanged.

---

## 8. Timeline

**Estimated duration:** 8–10 weeks (working 5 hours/day)

| Phase | Week(s) | Deliverables |
|-------|:-------:|-------------|
| **Smart Contracts** | 1–2 | All 4 contracts developed in Tact, unit tested, deployed to TON Testnet |
| **Backend Integration** | 3 | Laravel services, database migrations, API endpoints, Telegram bot commands |
| **Frontend & Wallet** | 4 | TON Connect integration, subscription dashboard, referral dashboard |
| **Grid Bot Engine** | 5–6 | Exchange connectors (Binance, KuCoin, Bybit), adaptive grid engine, AI signal layer |
| **Grid Bot Completion** | 7 | Risk management, backtesting engine, monitoring dashboard |
| **Testing** | 8 | End-to-end testing across all components, bug fixes |
| **Launch** | 9 | Mainnet deployment, grid bot production launch, soft launch monitoring |

**Week 10** is reserved as a buffer for unexpected issues.

> **Note:** Timeline assumes client provides timely access to server, codebase, and API keys. Delays in client-side dependencies will extend the timeline accordingly.

---

## 9. Cost & Payment Terms

### 9.1 Project Cost

| Item | Amount |
|------|--------|
| **Total Project Cost** | **1,500,000 UGX** |

### 9.2 Payment Schedule

| Payment | Amount | When |
|---------|--------|------|
| **Payment 1 (40%)** | **600,000 UGX** | Before work begins |
| **Payment 2 (60%)** | **900,000 UGX** | After successful launch (see Acceptance Criteria in Section 10) |

### 9.3 What's Included

- All development work listed in Section 3 (Deliverables)
- Testnet deployment and testing
- Mainnet deployment
- 1 month of free post-launch support (see Section 13)

### 9.4 What's NOT Included

- Third-party security audit (separate engagement, estimated $2,000–$10,000 USD)
- Ongoing hosting costs (client's existing server used)
- Exchange API subscription fees (if applicable)
- TON blockchain gas fees for contract deployment (~2–5 TON, ~$10–$25)
- Any work outside the scope defined in Section 2

---

## 10. Acceptance Criteria

"Launch" — and therefore the trigger for Payment 2 — is defined as **all** of the following being met:

| # | Criterion | How It's Verified |
|:-:|-----------|-------------------|
| 1 | All 4 smart contracts are deployed to TON Mainnet | Contract addresses provided, verified on-chain |
| 2 | Subscription flow works end-to-end: user pays SERPO → subscription activates for 30 days | Live test with real SERPO payment |
| 3 | Referral system works: shared link → referrer earns 50% of subscription payment | Balance verification |
| 4 | Fee Distribution splits correctly: 4% vault, 3% referrer | On-chain transaction verification |
| 5 | Collateral deposit and withdrawal works | Live test with real SERPO |
| 6 | All 8 new Telegram bot commands respond correctly | Manual testing of each command |
| 7 | TON Connect wallet connection works on website | Connect using Tonkeeper or MyTonWallet |
| 8 | Grid bot starts, executes trades, and stops on Binance | Live test on Binance (can use testnet or small amount) |
| 9 | Grid bot monitoring dashboard shows P/L and active grid status | Visual verification |
| 10 | Backtesting engine returns simulation results | Run backtest command and verify output |

---

## 11. Assumptions & Dependencies

| # | Assumption | Owner |
|:-:|-----------|-------|
| 1 | SERPO token is already deployed and operational on TON Mainnet | Client |
| 2 | SERPO token has sufficient liquidity on DeDust or STON.fi for price discovery | Client |
| 3 | Client provides access to existing server and Laravel codebase | Client |
| 4 | Client provides TON API key for blockchain queries | Client |
| 5 | Client provides exchange API keys for grid bot testing (Binance at minimum) | Client |
| 6 | Client handles marketing, user acquisition, and community management | Client |
| 7 | Client provides timely feedback during development (within 48 hours) | Client |
| 8 | Users have access to TON-compatible wallets (Tonkeeper, MyTonWallet) | Users |
| 9 | Existing Serpo AI bot and website remain operational during development | Client |

---

## 12. Risks

| # | Risk | Impact | Mitigation |
|:-:|------|--------|-----------|
| 1 | Smart contract vulnerability discovered after deployment | High — potential loss of funds | Thorough testnet testing; external audit recommended (not included in scope) |
| 2 | SERPO token has insufficient DEX liquidity | Medium — subscription pricing unreliable | Client to ensure liquidity before launch |
| 3 | TON network congestion delays transactions | Low — temporary user inconvenience | Retry logic and cached subscription status |
| 4 | Grid bot incurs trading losses | Medium — user dissatisfaction | AI signal layer pauses in trending markets; stop-loss protection; paper trading mode available |
| 5 | Exchange API changes break grid bot connectors | Medium — requires maintenance | API version pinning; abstraction layer for easy updates |
| 6 | No users adopt the platform after launch | High — no revenue generated | This is a marketing/product risk, not a development risk. Developer is not responsible for user acquisition |
| 7 | Client delays providing access or feedback | Medium — extends timeline | Timeline will be extended day-for-day for client-caused delays |

---

## 13. Post-Launch Support

### 13.1 Free Support Period

**Duration:** 1 month from launch date

**Included:**
- Bug fixes for delivered features
- Minor adjustments to deployed contracts (parameter changes only — not logic changes)
- Backend/frontend bug fixes
- Grid bot connector fixes if an exchange API changes during the support period

**NOT included during free support:**
- New features or functionality not listed in Section 3
- New exchange connectors beyond Binance, KuCoin, Bybit
- Forex or stock market integrations
- Design or UX redesign
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
