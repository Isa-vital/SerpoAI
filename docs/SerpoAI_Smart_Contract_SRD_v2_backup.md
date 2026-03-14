# Software Requirements Document (SRD)

## Serpo AI — TON Blockchain Smart Contract System

| Field | Detail |
|---|---|
| **Document Title** | Software Requirements Document — Serpo AI Smart Contract Architecture |
| **Version** | 2.0 |
| **Date** | March 13, 2026 |
| **Author** | SerpoAI Development Team |
| **Client** | Serpocoin Project |
| **Status** | Draft |
| **Classification** | Confidential |

---

## Document Revision History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 0.1 | March 12, 2026 | SerpoAI Team | Initial draft from architecture document |
| 1.0 | March 12, 2026 | SerpoAI Team | Complete SRD with all sections |
| 2.0 | March 13, 2026 | SerpoAI Team | Revised with Collateral Vault, Multi-Market Grid Bot Architecture, DEX auto-fee deduction, expanded exchange support |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [System Architecture](#3-system-architecture)
4. [Functional Requirements](#4-functional-requirements)
5. [Non-Functional Requirements](#5-non-functional-requirements)
6. [Smart Contract Specifications](#6-smart-contract-specifications)
7. [Data Requirements](#7-data-requirements)
8. [Interface Requirements](#8-interface-requirements)
9. [Security Requirements](#9-security-requirements)
10. [Integration Requirements](#10-integration-requirements)
11. [User Stories & Use Cases](#11-user-stories--use-cases)
12. [Acceptance Criteria](#12-acceptance-criteria)
13. [Implementation Plan](#13-implementation-plan)
14. [Cost Estimation](#14-cost-estimation)
15. [Risk Assessment](#15-risk-assessment)
16. [Testing Strategy](#16-testing-strategy)
17. [Deployment Plan](#17-deployment-plan)
18. [Maintenance & Support](#18-maintenance--support)
19. [Glossary](#19-glossary)
20. [Appendices](#20-appendices)

---

## 1. Introduction

### 1.1 Purpose

This Software Requirements Document defines the complete technical and business requirements for designing, developing, and deploying a suite of TON Blockchain smart contracts that power the Serpo AI platform's subscription system, referral program, trading fee distribution, and treasury management. The document serves as the authoritative reference for all stakeholders involved in the smart contract development lifecycle.

### 1.2 Scope

The project encompasses the creation of a blockchain-based payment and subscription infrastructure for Serpo AI — an AI-powered Telegram trading bot platform built on Laravel 12 (PHP 8.2+). The smart contract system will handle:

- **Automatic wallet generation** for every Serpo AI user across website, Telegram bot, and mobile app
- **SerpoCoin (SERPO) Jetton** integration following the TON Jetton Standard
- **Subscription smart contract** for monthly AI tool access ($10/month in SERPO tokens)
- **Referral reward distribution** (50% of subscription, 3% of trading profits)
- **Trading fee collection** (7% performance fee with automated split)
- **Collateral vault system** for guaranteeing performance fee payment on centralized exchange trades
- **Treasury vault management** with owner-only withdrawal controls
- **TON Connect wallet integration** for secure, non-custodial user authentication
- **Multi-market grid bot architecture** supporting crypto, forex, and stock markets
- **AI-driven grid engine** with adaptive spacing, dynamic order sizing, and risk management

### 1.3 Intended Audience

| Audience | Purpose |
|----------|---------|
| Smart Contract Developers | Technical implementation reference |
| Backend Engineers | Integration specifications |
| Project Managers | Scope, timeline, and cost reference |
| Security Auditors | Security requirements and threat model |
| QA Engineers | Test cases and acceptance criteria |
| Investors / Stakeholders | Business logic and revenue model overview |
| Legal / Compliance | Regulatory considerations |

### 1.4 Definitions, Acronyms, and Abbreviations

| Term | Definition |
|------|------------|
| TON | The Open Network blockchain |
| Jetton | TON's token standard (equivalent to ERC-20 on Ethereum) |
| SERPO | SerpoCoin — the native Jetton token of the Serpo AI ecosystem |
| Tact | High-level smart contract language for TON |
| FunC | Low-level smart contract language for TON |
| TON Connect | Standard protocol for wallet connection on TON |
| DEX | Decentralized Exchange |
| DeDust / STON.fi | TON-native decentralized exchanges |
| SRD | Software Requirements Document |
| API | Application Programming Interface |
| dApp | Decentralized Application |

### 1.5 References

| Reference | Description |
|-----------|-------------|
| TON Jetton Standard | TEP-74 Fungible Token Standard |
| TON Connect Protocol | Wallet connection protocol specification |
| Tact Language Docs | https://docs.tact-lang.org |
| TonAPI Documentation | https://tonapi.io/v2 |
| Serpo AI Architecture Document | Serpo_AI_TON_Architecture.docx |
| Existing SERPO Token Contract | `EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw` |

### 1.6 Document Conventions

- **SHALL** — Mandatory requirement
- **SHOULD** — Recommended but not mandatory
- **MAY** — Optional feature
- Requirements are prefixed with category codes: FR (Functional), NFR (Non-Functional), SC (Smart Contract), SEC (Security), IR (Integration)

---

## 2. Overall Description

### 2.1 Product Perspective

Serpo AI is a production-deployed AI-powered trading intelligence platform (v2.0) accessible via Telegram bot, website (ai.serpocoin.io), and mobile app. The platform currently provides:

- Multi-market analysis (crypto, forex, stocks) with 40+ bot commands
- AI-powered predictions using GPT-4o-mini, Google Gemini, and Groq
- Real-time blockchain monitoring via TonAPI
- Token verification across 20+ chains (Degen Scanner)
- Whale alert tracking, technical analysis (RSI, S/R, divergence)
- Portfolio management and universal price alerts

The smart contract system extends this platform by introducing an on-chain subscription and revenue model, replacing or augmenting the current free-access model with a sustainable SerpoCoin-powered economy.

### 2.2 Product Features Summary

| Feature | Description | Priority |
|---------|-------------|----------|
| Wallet Generation | Auto-generate TON wallet per user account | Critical |
| TON Connect Integration | Non-custodial wallet connection (Tonkeeper, MyTonWallet) | Critical |
| Subscription Contract | Monthly SERPO payment → AI access unlock | Critical |
| Referral System | 50% subscription / 3% trading profit sharing | High |
| Fee Distribution Contract | 7% trading fee split (4% vault, 3% referrer) | High |
| Vault Contract | Owner-controlled treasury for platform revenue | Critical |
| Collateral Vault | User deposits to guarantee performance fee payment for CEX trades | Critical |
| Multi-Market Grid Bot | Adaptive grid trading across crypto, forex, and stocks | High |
| AI Signal Layer | Trend detection, volatility analysis, grid activation/pause signals | High |
| Risk & Capital Management | Per-market allocation, stop-loss, hedging, leverage control | High |
| Auto-Renewal | Recurring monthly subscription with wallet approval | Medium |
| DEX Auto-Fee Deduction | On-chain trades on STON.fi / DeDust with automatic 7% fee deduction | Medium |
| Backtesting Engine | Historical data simulation and strategy validation | Medium |
| Monitoring Dashboard | Real-time P/L, grid level charts, position tracking, alerts | Medium |
| Subscription NFT Badge | Proof-of-subscription as NFT | Low |
| Referral Dashboard | Earnings history and tracking UI | Medium |
| Revenue Analytics | On-chain revenue reporting | Low |

### 2.3 User Classes and Characteristics

| User Class | Description | Interaction |
|------------|-------------|-------------|
| **Free Users** | Connected wallet, no active subscription | Browse features, connect wallet, view referral link |
| **Subscribed Users** | Active subscription via SERPO payment | Full AI tools, trading bots, premium analysis |
| **Referrers** | Users who share referral links | Earn 50% of referred subscriptions + 3% of trading profits |
| **Trading Bot Users** | Subscribers using automated trading | Execute trades via Binance/OKX/MEXC/KuCoin/Bybit (crypto), OANDA/FXCM/MT4/MT5 (forex), Interactive Brokers/Alpaca (stocks) APIs |
| **Platform Administrators** | Vault owners / project team | Withdraw vault funds, manage contract parameters |

### 2.4 Operating Environment

| Component | Technology |
|-----------|------------|
| Blockchain | TON Mainnet |
| Smart Contract Language | Tact (primary) / FunC (fallback) |
| Backend Framework | Laravel 12 (PHP 8.2+) |
| Database | PostgreSQL (primary) / MySQL 5.7+ (supported) |
| Cache | Redis |
| Blockchain Libraries | TonWeb, ton-core, TON SDK |
| Wallet Protocol | TON Connect 2.0 |
| Frontend | React / Next.js (web), Telegram Bot API (bot) |
| Trading Bot Runtime | Python 3.12+ (grid engine, AI models), Node.js (async execution) |
| Trading Libraries | pandas, NumPy, TA-Lib, PyTorch/TensorFlow, Backtrader, vectorbt |
| Real-Time Data | WebSocket streams (Python asyncio, FastAPI) |
| Server | Nginx + PHP-FPM (existing infrastructure) |
| Hosting | VPS (current: ai.serpocoin.io) |

### 2.5 Design and Implementation Constraints

| Constraint | Description |
|------------|-------------|
| C-01 | Smart contracts SHALL be written in Tact or FunC for TON compatibility |
| C-02 | SERPO token SHALL remain a standard TEP-74 Jetton — no modifications to the existing token contract |
| C-03 | The system SHALL NOT store user private keys — non-custodial design mandatory |
| C-04 | All on-chain transactions SHALL be denominated in SERPO Jetton tokens |
| C-05 | Subscription price ($10 USD equivalent) SHALL be calculated at transaction time using oracle or market data |
| C-06 | Smart contracts SHALL be immutable after deployment — use proxy patterns only if upgrade path is required |
| C-07 | The existing Laravel backend SHALL integrate with blockchain data via TonAPI / ton-core libraries |
| C-08 | Exchange API keys stored by the platform SHALL have trade-only permissions (no withdrawal) |

### 2.6 Assumptions and Dependencies

**Assumptions:**
- SerpoCoin Jetton contract is already deployed and operational on TON Mainnet
- Users have access to TON-compatible wallets (Tonkeeper, MyTonWallet, OpenMask)
- SERPO token has sufficient liquidity on DeDust or STON.fi for price discovery
- TonAPI remains available and reliable for blockchain state queries
- Users understand basic crypto wallet operations

**Dependencies:**
- TON Blockchain network uptime and gas fee stability
- TonAPI service availability for backend verification
- DEX liquidity for SERPO/TON price feeds
- Existing Serpo AI Laravel backend (v2.0) for integration
- Telegram Bot API for bot-based wallet connection flows

---

## 3. System Architecture

### 3.1 High-Level Architecture

The system follows a six-layer architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                  LAYER 1: USER INTERFACE                     │
│  ┌──────────┐   ┌──────────────┐   ┌───────────────────┐   │
│  │  Website  │   │ Telegram Bot │   │    Mobile App     │   │
│  │ (React/   │   │ (Laravel +   │   │ (React Native /   │   │
│  │  Next.js) │   │  Bot API)    │   │  Flutter)         │   │
│  └─────┬─────┘   └──────┬───────┘   └────────┬──────────┘   │
├────────┼────────────────┼────────────────────┼──────────────┤
│                  LAYER 2: WALLET CONNECTION                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              TON Connect 2.0 Protocol                 │   │
│  │  Tonkeeper │ MyTonWallet │ OpenMask │ Other Wallets   │   │
│  └──────────────────────┬───────────────────────────────┘   │
├─────────────────────────┼───────────────────────────────────┤
│                  LAYER 3: BACKEND ENGINE                      │
│  ┌──────────────────────┼───────────────────────────────┐   │
│  │   Laravel 12 Backend (ai.serpocoin.io)                │   │
│  │   ┌─────────────┐ ┌──────────────┐ ┌─────────────┐  │   │
│  │   │ User Mgmt   │ │ Subscription │ │  Referral   │  │   │
│  │   │ Service     │ │ Verifier     │ │  Tracker    │  │   │
│  │   └─────────────┘ └──────────────┘ └─────────────┘  │   │
│  │   ┌─────────────┐ ┌──────────────┐ ┌─────────────┐  │   │
│  │   │ TonAPI      │ │ Exchange API │ │  Profit     │  │   │
│  │   │ Client      │ │ Manager      │ │  Calculator │  │   │
│  │   └─────────────┘ └──────────────┘ └─────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│                  LAYER 4: MULTI-MARKET GRID BOT ENGINE       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │   MARKET CONNECTOR LAYER                              │   │
│  │   Crypto: Binance │ KuCoin │ Bybit (REST/WebSocket)  │   │
│  │   Forex:  OANDA │ FXCM │ MT4/MT5 (Python API)        │   │
│  │   Stocks: Interactive Brokers │ Alpaca                │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │   AI SIGNAL LAYER                                     │   │
│  │   Trend Detection │ Volatility Analysis │ Sentiment   │   │
│  │   Grid Activation / Pause Signals                     │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │   GRID ENGINE                                         │   │
│  │   Adaptive Grid Spacing (ATR/Volatility-based)        │   │
│  │   Dynamic Order Sizing │ Partial Profit Taking        │   │
│  │   Multi-Direction (Long/Short) │ Market-Specific Logic│   │
│  ├──────────────────────────────────────────────────────┤   │
│  │   EXECUTION ENGINE                                    │   │
│  │   Real-time Order Placement │ Slippage Management     │   │
│  │   Multi-Market Async Execution │ Latency Optimization │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │   RISK & CAPITAL MANAGEMENT                           │   │
│  │   Max Exposure per Market │ Stop-Loss/Hedge           │   │
│  │   Leverage Control │ Real-time Risk Alerts            │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │   DATA LOGGING & BACKTESTING                          │   │
│  │   Historical Data Storage │ Simulation Engine         │   │
│  │   Performance Analytics & Optimization                │   │
│  └──────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│                  LAYER 5: SMART CONTRACT LAYER               │
│  ┌──────────────┐ ┌──────────────┐ ┌────────────────────┐  │
│  │ Subscription │ │    Fee       │ │   Vault            │  │
│  │ Contract     │ │ Distribution │ │   Contract         │  │
│  │              │ │ Contract     │ │   (Trading Fee     │  │
│  │ • Receive    │ │ • 7% fee     │ │    Vault)          │  │
│  │   SERPO      │ │ • 4% vault   │ │ • Store revenue    │  │
│  │ • Referral   │ │ • 3% referrer│ │ • Owner-only       │  │
│  │   split      │ │ • DEX swap   │ │   withdrawal       │  │
│  │ • Activate   │ │ • DEX auto   │ │ • Multisig option  │  │
│  │   30 days    │ │   deduction  │ │                    │  │
│  └──────────────┘ └──────────────┘ └────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │   COLLATERAL VAULT                                    │   │
│  │   • User SERPO deposits to guarantee CEX fee payment  │   │
│  │   • Balance deducted on profitable trades             │   │
│  │   • Withdrawal only by wallet owner                   │   │
│  │   • Not needed for DEX trades (auto-deducted)         │   │
│  └──────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│                  LAYER 6: EXCHANGE INTEGRATION               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │   STON.fi │ DeDust — SERPO/TON Liquidity Pools       │   │
│  │   Fee → SERPO Swap → Distribution Contract           │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Smart Contract Architecture

```
┌──────────────────────────────────────────────────────────┐
│                   SERPO Jetton Master                     │
│              (Existing Deployed Contract)                 │
│         EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw│
└──────────────┬──────────────────────┬────────────────────┘
               │                      │
    ┌──────────▼──────────┐  ┌───────▼──────────────┐
    │  User Jetton Wallet │  │  User Jetton Wallet  │
    │  (Per-User Auto)    │  │  (Per-User Auto)     │
    └──────────┬──────────┘  └───────┬──────────────┘
               │                      │
               │  Jetton Transfer     │
               │  (with payload)      │
               ▼                      ▼
    ┌─────────────────────────────────────────────┐
    │        SUBSCRIPTION CONTRACT                 │
    │  ┌────────────────────────────────────────┐  │
    │  │ Storage:                               │  │
    │  │  • owner_address                       │  │
    │  │  • subscription_price (SERPO amount)   │  │
    │  │  • vault_wallet                        │  │
    │  │  • users: Map<Address, UserData>       │  │
    │  │    - subscription_expiry               │  │
    │  │    - referrer_wallet                   │  │
    │  └────────────────────────────────────────┘  │
    │  ┌────────────────────────────────────────┐  │
    │  │ Logic:                                 │  │
    │  │  1. Receive Jetton transfer_notification│  │
    │  │  2. Verify amount == subscription_price│  │
    │  │  3. Read referral from payload         │  │
    │  │  4. If referral: 50% referrer / 50%    │  │
    │  │     vault                              │  │
    │  │  5. If no referral: 100% vault         │  │
    │  │  6. Set expiry = now + 30 days         │  │
    │  └────────────────────────────────────────┘  │
    └──────────────────┬──────────────────────────┘
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
    ┌───────────┐ ┌─────────┐ ┌──────────────┐
    │ Referrer  │ │  Vault  │ │ Subscription │
    │ Wallet    │ │ Contract│ │ State Update │
    │ (50%)     │ │ (50%/   │ │ (30 days)    │
    │           │ │  100%)  │ │              │
    └───────────┘ └─────────┘ └──────────────┘

    ┌─────────────────────────────────────────────┐
    │        FEE DISTRIBUTION CONTRACT             │
    │  ┌────────────────────────────────────────┐  │
    │  │ Triggered by: Trading bot profit event │  │
    │  │                                        │  │
    │  │ Input: 7% of trade profit in SERPO     │  │
    │  │                                        │  │
    │  │ Split:                                 │  │
    │  │  • 4% → Vault Contract                 │  │
    │  │  • 3% → Referrer Wallet                │  │
    │  └────────────────────────────────────────┘  │
    └─────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────┐
    │             VAULT CONTRACT                   │
    │  ┌────────────────────────────────────────┐  │
    │  │ • Accumulates all platform revenue     │  │
    │  │ • Owner-only withdrawal                │  │
    │  │ • Optional: Multisig control           │  │
    │  │ • Balance query (public)               │  │
    │  └────────────────────────────────────────┘  │
    └─────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────┐
    │          COLLATERAL VAULT CONTRACT            │
    │  ┌────────────────────────────────────────┐  │
    │  │ • Users deposit SERPO as collateral    │  │
    │  │   to guarantee CEX performance fees    │  │
    │  │ • Per-user balance tracking            │  │
    │  │   (wallet_A → 200 SERPO, etc.)         │  │
    │  │ • Backend deducts 7% fee from balance  │  │
    │  │   on profitable CEX trades             │  │
    │  │ • Fee split: 4% → vault, 3% → referrer│  │
    │  │ • Only wallet owner can withdraw       │  │
    │  │   unused collateral                    │  │
    │  │ • NOT required for DEX trades          │  │
    │  │   (fees auto-deducted on-chain)        │  │
    │  └────────────────────────────────────────┘  │
    └─────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────┐
    │       DEX TRADE FEE (AUTOMATIC)              │
    │  ┌────────────────────────────────────────┐  │
    │  │ On STON.fi / DeDust trades:            │  │
    │  │ • Trade executes in smart contract     │  │
    │  │ • Profit calculated on-chain           │  │
    │  │ • 7% fee deducted automatically        │  │
    │  │ • Split: 4% → vault, 3% → referrer    │  │
    │  │ • No collateral deposit needed         │  │
    │  └────────────────────────────────────────┘  │
    └─────────────────────────────────────────────┘
```

### 3.3 Data Flow Diagrams

#### 3.3.1 Subscription Flow

```
User                    Frontend          TON Connect       Subscription         Vault
 │                         │                  │              Contract              │
 │─── Click Subscribe ────►│                  │                 │                  │
 │                         │── Prepare Tx ───►│                 │                  │
 │                         │   (amount,       │                 │                  │
 │                         │    referral,      │                 │                  │
 │                         │    payload)       │                 │                  │
 │◄── Sign Request ────────│◄─────────────────│                 │                  │
 │─── Approve in Wallet ──►│                  │                 │                  │
 │                         │                  │── Jetton Tx ───►│                  │
 │                         │                  │                 │── Verify ────────►│
 │                         │                  │                 │   Amount          │
 │                         │                  │                 │── Split & Send ──►│
 │                         │                  │                 │   (50%/100%)      │
 │                         │                  │                 │── Update Expiry   │
 │                         │                  │                 │   (+30 days)      │
 │◄── Subscription Active ─│◄── State Query ──│◄────────────────│                  │
 │                         │                  │                 │                  │
```

#### 3.3.2 Trading Fee Flow

```
Trading Bot          Exchange API        Backend           Fee Contract         Vault
    │                     │                 │                   │                 │
    │── Execute Trade ───►│                 │                   │                 │
    │◄── Trade Result ────│                 │                   │                 │
    │── Report Profit ───►│                 │                   │                 │
    │                     │                 │── Calculate 7% ──►│                 │
    │                     │                 │   fee in SERPO     │                 │
    │                     │                 │                   │── 4% to vault ─►│
    │                     │                 │                   │── 3% to referrer │
    │                     │                 │◄── Confirmation ──│                 │
    │                     │                 │                   │                 │
```

#### 3.3.3 Collateral Vault Flow (CEX Trades)

```
User                    Backend           Collateral Vault     Fee Distributor     Vault
 │                         │                   │                    │                │
 │── Deposit Collateral ──►│                   │                    │                │
 │   (e.g. 200 SERPO)      │── Send SERPO ────►│                    │                │
 │                         │                   │── Store balance    │                │
 │                         │                   │   wallet_A → 200   │                │
 │                         │                   │                    │                │
 │   [Bot closes trade]    │                   │                    │                │
 │                         │── Profit: 100 ───►│                    │                │
 │                         │   Fee: 7 SERPO    │── Check balance    │                │
 │                         │                   │   200 >= 7? Yes    │                │
 │                         │                   │── Deduct 7 ───────►│                │
 │                         │                   │   balance → 193    │── 4 → vault ──►│
 │                         │                   │                    │── 3 → referrer  │
 │                         │                   │                    │                │
 │── Withdraw Unused ─────►│                   │                    │                │
 │   (e.g. 100 SERPO)      │                   │── Verify owner     │                │
 │                         │                   │── Send 100 SERPO   │                │
 │◄── 100 SERPO ───────────│◄──────────────────│   balance → 93     │                │
 │                         │                   │                    │                │
```

#### 3.3.4 DEX Trade Flow (Automatic Fee Deduction)

```
User                    DEX (STON.fi/DeDust)    Smart Contract      Vault
 │                         │                        │                 │
 │── Swap on DEX ─────────►│                        │                 │
 │   input: 1000 SERPO     │                        │                 │
 │                         │── Execute swap          │                 │
 │                         │   output: 1100 SERPO    │                 │
 │                         │   profit: 100 SERPO     │                 │
 │                         │── Deduct fee ──────────►│                 │
 │                         │   fee: 7 SERPO          │── 4 → vault ──►│
 │                         │                        │── 3 → referrer  │
 │◄── 1093 SERPO ──────────│                        │                 │
 │   (no collateral needed) │                        │                 │
 │                         │                        │                 │
```

### 3.4 Component Interaction Model

| Component | Interacts With | Protocol | Direction |
|-----------|---------------|----------|-----------|
| Website / App | TON Connect | TON Connect 2.0 | Bidirectional |
| Telegram Bot | Laravel Backend | HTTPS Webhook | Inbound |
| Laravel Backend | TonAPI | HTTPS REST | Outbound |
| Laravel Backend | Subscription Contract | ton-core / TonWeb | Read (state query) |
| User Wallet | Subscription Contract | TON Blockchain | Jetton Transfer |
| Subscription Contract | Vault Contract | TON Blockchain | Jetton Forward |
| Subscription Contract | Referrer Wallet | TON Blockchain | Jetton Forward |
| Trading Bot | Exchange APIs (Crypto/Forex/Stock) | HTTPS REST / WebSocket | Bidirectional |
| Grid Engine | AI Signal Layer | Internal | Bidirectional |
| Fee Distribution | Vault Contract | TON Blockchain | Jetton Forward |
| Fee Distribution | Referrer Wallet | TON Blockchain | Jetton Forward |
| User Wallet | Collateral Vault | TON Blockchain | Jetton Deposit/Withdraw |
| Backend | Collateral Vault | TON Blockchain | Fee Deduction Trigger |
| DEX Smart Contract | Fee Distribution | TON Blockchain | Auto Fee Forward |

---

## 4. Functional Requirements

### 4.1 Wallet Management

| ID | Requirement | Priority | Approach |
|----|-------------|----------|----------|
| **FR-W01** | The system SHALL support two wallet approaches: (A) Auto-generated custodial wallets for seamless onboarding, and (B) TON Connect non-custodial wallet connection | Critical | Dual-mode |
| **FR-W02** | Auto-generated wallets SHALL use TonWeb/ton-core to create a key pair, derive wallet address, and encrypt the private key with AES-256 before storage | Critical | Mode A |
| **FR-W03** | Each auto-generated wallet SHALL automatically receive a Jetton wallet address for SerpoCoin | Critical | Mode A |
| **FR-W04** | TON Connect integration SHALL support Tonkeeper, MyTonWallet, and OpenMask | Critical | Mode B |
| **FR-W05** | Wallet connection via TON Connect SHALL present a QR code for scanning on mobile | High | Mode B |
| **FR-W06** | The connected wallet address SHALL become the primary user identifier | Critical | Both |
| **FR-W07** | Users SHALL be able to disconnect and reconnect different wallets | Medium | Mode B |
| **FR-W08** | The system SHALL store the wallet address, referrer wallet, and subscription status per user | Critical | Both |

### 4.2 Subscription System

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-S01** | The system SHALL accept subscription payments of $10 USD equivalent in SERPO tokens | Critical |
| **FR-S02** | Subscription price in SERPO tokens SHALL be calculated at transaction time using current market price from DeDust/STON.fi or CoinGecko | Critical |
| **FR-S03** | Upon valid payment, the subscription contract SHALL set the user's expiry to `current_time + 30 days` | Critical |
| **FR-S04** | If the user already has an active subscription, renewal SHALL extend the existing expiry by 30 days | High |
| **FR-S05** | The subscription contract SHALL reject payments below the required SERPO amount | Critical |
| **FR-S06** | The subscription contract SHALL reject payments of incorrect Jetton types (non-SERPO tokens) | Critical |
| **FR-S07** | The backend SHALL verify subscription status by reading the smart contract state via TonAPI | Critical |
| **FR-S08** | Subscription verification SHALL work across all platforms: website, Telegram bot, and mobile app | Critical |
| **FR-S09** | The system SHOULD support auto-renewal with user wallet approval | Medium |
| **FR-S10** | Expired subscriptions SHALL immediately restrict access to premium AI tools | High |

### 4.3 Referral System

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-R01** | Each user SHALL receive a unique referral link in the format `serpo.ai/?ref=wallet_address` or `SERPOAI.COM/?ref=wallet_address` | High |
| **FR-R02** | When a referred user subscribes, the subscription contract SHALL split payment: 50% to referrer, 50% to vault | Critical |
| **FR-R03** | When a non-referred user subscribes, 100% of payment SHALL go to the vault | Critical |
| **FR-R04** | The smart contract SHALL prevent self-referral (referrer ≠ subscriber) | Critical |
| **FR-R05** | The smart contract SHALL validate that the referrer wallet exists and is a registered user | High |
| **FR-R06** | Referral relationships SHALL be stored permanently — the same referrer earns on every renewal | High |
| **FR-R07** | Monthly subscription renewals SHALL continue paying the assigned referrer | High |
| **FR-R08** | The referral system SHALL pay 3% of trading bot profits to the referrer | High |
| **FR-R09** | The system SHOULD provide a referral dashboard showing earnings history | Medium |
| **FR-R10** | Referral payouts SHALL be in SERPO tokens sent directly to the referrer's wallet | High |

### 4.4 Trading Fee System

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-T01** | The system SHALL collect a 7% performance fee on profitable trades executed by the trading bots | High |
| **FR-T02** | The 7% fee SHALL be split: 4% to the project vault, 3% to the user's referrer | High |
| **FR-T03** | If the user has no referrer, the full 7% SHALL go to the vault | High |
| **FR-T04** | Trading fees SHALL be converted to SERPO tokens before distribution | High |
| **FR-T05** | Fee conversion SHALL use DEX integration (STON.fi or DeDust) for on-chain swaps | Medium |
| **FR-T06** | The fee distribution contract SHALL handle the split and forwarding automatically | High |
| **FR-T07** | No fee SHALL be charged on losing trades | Critical |
| **FR-T08** | For CEX trades, fees SHALL be deducted from the user's collateral vault balance | High |
| **FR-T09** | For DEX trades (STON.fi / DeDust), the 7% fee SHALL be automatically deducted on-chain within the trade smart contract — no collateral required | High |
| **FR-T10** | The backend SHALL record each trade's entry_price, exit_price, amount, profit, and timestamp | High |

### 4.5 Collateral Vault System

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-C01** | Users SHALL deposit SERPO tokens into the collateral vault to guarantee performance fee payment on centralized exchange trades | Critical |
| **FR-C02** | The collateral vault SHALL maintain per-user balance tracking (e.g., `wallet_A → 200 SERPO`) | Critical |
| **FR-C03** | When a profitable CEX trade is recorded, the backend SHALL trigger a fee deduction from the user's collateral balance | Critical |
| **FR-C04** | The contract SHALL verify `collateral_balance >= fee_amount` before deducting | Critical |
| **FR-C05** | After deduction, the fee SHALL be split: 4% → trading fee vault, 3% → referrer wallet | High |
| **FR-C06** | Users SHALL be able to withdraw unused collateral at any time | High |
| **FR-C07** | Only the wallet owner SHALL be able to trigger collateral withdrawal | Critical |
| **FR-C08** | The contract SHALL verify `balance >= withdrawal_amount` before processing withdrawals | Critical |
| **FR-C09** | Collateral deposits and withdrawals SHALL be logged on-chain for transparency | High |
| **FR-C10** | Collateral is NOT required for DEX trades — fees are auto-deducted on-chain | High |

### 4.6 Vault Management

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-V01** | The vault contract SHALL accumulate all platform revenue (subscription + trading fees) | Critical |
| **FR-V02** | Only the designated owner address SHALL be able to withdraw funds from the vault | Critical |
| **FR-V03** | The vault balance SHALL be publicly queryable on-chain | High |
| **FR-V04** | The vault SHOULD support multisignature control (e.g., 3 signatures required) for enhanced security | Medium |
| **FR-V05** | Withdrawal events SHALL emit on-chain logs for transparency | High |

### 4.7 Multi-Market Grid Bot

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-G01** | The grid bot SHALL support three market types: cryptocurrency, forex, and stocks | High |
| **FR-G02** | The Market Connector Layer SHALL handle REST and WebSocket connections to all supported exchanges with authentication, rate-limit handling, and error recovery | Critical |
| **FR-G03** | Supported crypto exchanges SHALL include Binance, KuCoin, and Bybit | High |
| **FR-G04** | Supported forex platforms SHALL include OANDA, FXCM, and MetaTrader 4/5 (MT5 Python API) | High |
| **FR-G05** | Supported stock platforms SHALL include Interactive Brokers API and Alpaca API | Medium |
| **FR-G06** | The Grid Engine SHALL dynamically create buy/sell grids based on price range, volatility (ATR-based), and capital allocation | Critical |
| **FR-G07** | The Grid Engine SHALL support adaptive grid spacing, dynamic order sizing, partial profit taking, and multi-direction (long/short) grid activation | High |
| **FR-G08** | The AI Signal Layer SHALL detect market conditions (sideways vs trending), volatility spikes, and market sentiment to trigger grid activation or pause | High |
| **FR-G09** | The Risk & Capital Management module SHALL enforce per-market capital allocation, max open positions, stop-loss/hedging strategies, and leverage control for margin accounts | Critical |
| **FR-G10** | The Execution Engine SHALL support real-time order placement with retries, slippage management, and multi-market async execution | High |
| **FR-G11** | The system SHALL provide a monitoring dashboard with real-time P/L visualization, grid level charts, and position tracking across all markets | Medium |
| **FR-G12** | The system SHALL support data logging and backtesting with historical data storage, simulation engine, and performance analytics for strategy optimization | Medium |
| **FR-G13** | Grid bot execution SHALL use async programming (Python asyncio or Node.js) for parallel multi-market execution | High |
| **FR-G14** | The bot SHALL implement auto-hedge or stop-loss mechanisms to protect capital in worst-case scenarios (strong trends, liquidity issues, margin calls) | Critical |

### 4.8 Platform Access Control

| ID | Requirement | Priority |
|----|-------------|----------|
| **FR-A01** | Upon user login/connection, the backend SHALL query the subscription contract to check `subscription_expiry > current_time` | Critical |
| **FR-A02** | If the subscription is active, all AI tools, trading bots, and premium features SHALL be unlocked | Critical |
| **FR-A03** | If the subscription is expired or absent, the user SHALL see limited/free-tier features only | Critical |
| **FR-A04** | Subscription status checks SHALL be cached for 5 minutes to reduce blockchain queries | High |
| **FR-A05** | The access control logic SHALL be consistent across website, Telegram bot, and mobile app | Critical |

---

## 5. Non-Functional Requirements

### 5.1 Performance

| ID | Requirement | Target |
|----|-------------|--------|
| **NFR-P01** | Subscription contract execution time | < 10 seconds (block confirmation) |
| **NFR-P02** | Backend subscription verification response | < 2 seconds |
| **NFR-P03** | Wallet connection via TON Connect | < 5 seconds |
| **NFR-P04** | Referral payment forwarding | Within same transaction or next block |
| **NFR-P05** | Fee distribution processing | < 30 seconds from profit calculation |
| **NFR-P06** | Concurrent subscription verifications | 1,000+ per minute |

### 5.2 Scalability

| ID | Requirement | Target |
|----|-------------|--------|
| **NFR-S01** | Support registered users | 100,000+ wallets |
| **NFR-S02** | Concurrent active subscriptions | 10,000+ |
| **NFR-S03** | Daily transaction throughput | 50,000+ on-chain operations |
| **NFR-S04** | Smart contract storage | Scale with TON's sharded architecture |

### 5.3 Reliability & Availability

| ID | Requirement | Target |
|----|-------------|--------|
| **NFR-R01** | Backend service uptime | 99.9% |
| **NFR-R02** | Smart contract availability | 100% (inherent to blockchain) |
| **NFR-R03** | Subscription verification fallback | Cache-based if TonAPI is unavailable |
| **NFR-R04** | Transaction failure handling | Automatic retry with backoff |

### 5.4 Maintainability

| ID | Requirement | Description |
|----|-------------|-------------|
| **NFR-M01** | Smart contract code SHALL be well-documented with inline comments | Tact-native documentation |
| **NFR-M02** | Contract parameters (price, vault address) SHOULD be updatable by the owner | Admin functions |
| **NFR-M03** | Backend integration code SHALL follow Laravel service pattern conventions | Existing architecture |
| **NFR-M04** | All smart contract ABIs SHALL be versioned and documented | API compatibility |

### 5.5 Compliance & Legal

| ID | Requirement | Description |
|----|-------------|-------------|
| **NFR-C01** | The system SHALL NOT constitute an unregistered securities offering | Legal review required |
| **NFR-C02** | User data handling SHALL comply with GDPR where applicable | Privacy by design |
| **NFR-C03** | Smart contract source code SHALL be verified and published on-chain | Transparency |
| **NFR-C04** | Terms of service SHALL clearly disclose the referral and fee structure | User consent |

---

## 6. Smart Contract Specifications

### 6.1 Subscription Contract

**Contract Name:** `SerpoSubscription`
**Language:** Tact
**Network:** TON Mainnet

#### 6.1.1 Storage Variables

```
contract SerpoSubscription {
    owner: Address               // Contract deployer / admin
    jetton_master: Address       // SERPO Jetton master contract address
    subscription_price: Int      // Price in SERPO nano-units
    vault_wallet: Address        // Treasury vault address
    
    // User subscription data (on-chain mapping)
    users: map<Address, UserSubscription>
}

struct UserSubscription {
    expiry: Int               // Unix timestamp of subscription end
    referrer: Address?        // Referrer wallet (nullable)
}
```

#### 6.1.2 Entry Points / Messages

| Message | Parameters | Description |
|---------|------------|-------------|
| `receive(msg: JettonTransferNotification)` | `amount`, `sender`, `forward_payload` | Main entry: processes subscription payment |
| `receive("renew")` | — | Trigger renewal (requires prior Jetton transfer) |
| `get fun subscriptionStatus(user: Address): UserSubscription?` | `user` | Read-only: returns user subscription data |
| `receive("updatePrice")` | `new_price: Int` | Owner-only: update subscription price |
| `receive("updateVault")` | `new_vault: Address` | Owner-only: update vault address |

#### 6.1.3 Core Logic (Pseudocode)

```
on JettonTransferNotification(amount, sender, payload):
    // 1. Verify the Jetton is SERPO
    require(jetton_sender == self.jetton_master.jetton_wallet)
    
    // 2. Verify correct amount
    require(amount >= self.subscription_price)
    
    // 3. Parse referral from payload
    referrer = parse_referral(payload)
    
    // 4. Validate referral
    if referrer != null:
        require(referrer != sender)  // No self-referral
        // Split payment
        send_jetton(referrer, amount * 50 / 100)
        send_jetton(vault_wallet, amount * 50 / 100)
    else:
        send_jetton(vault_wallet, amount)
    
    // 5. Activate/extend subscription
    user_data = self.users.get(sender)
    if user_data != null AND user_data.expiry > now():
        // Extension: add 30 days to existing expiry
        new_expiry = user_data.expiry + 30_DAYS
    else:
        // New subscription
        new_expiry = now() + 30_DAYS
    
    // 6. Store
    self.users.set(sender, UserSubscription{
        expiry: new_expiry,
        referrer: referrer ?? user_data?.referrer
    })
```

#### 6.1.4 Gas Estimation

| Operation | Estimated Gas (TON) |
|-----------|---------------------|
| Subscribe (no referral) | ~0.05 TON |
| Subscribe (with referral) | ~0.08 TON |
| State query | Free (get method) |
| Price update (owner) | ~0.02 TON |

### 6.2 Fee Distribution Contract

**Contract Name:** `SerpoFeeDistributor`
**Language:** Tact

#### 6.2.1 Storage Variables

```
contract SerpoFeeDistributor {
    owner: Address
    vault_wallet: Address
    vault_share: Int          // 4 (out of 7 = ~57.14%)
    referrer_share: Int       // 3 (out of 7 = ~42.86%)
}
```

#### 6.2.2 Core Logic

```
on JettonTransferNotification(amount, sender, payload):
    referrer = parse_referrer(payload)
    
    vault_amount = amount * self.vault_share / (self.vault_share + self.referrer_share)
    
    if referrer != null:
        referrer_amount = amount - vault_amount
        send_jetton(referrer, referrer_amount)
        send_jetton(vault_wallet, vault_amount)
    else:
        send_jetton(vault_wallet, amount)
```

### 6.3 Vault Contract

**Contract Name:** `SerpoVault`
**Language:** Tact

#### 6.3.1 Storage Variables

```
contract SerpoVault {
    owner: Address
    total_received: Int       // Running total for analytics
}
```

#### 6.3.2 Core Logic

```
receive(msg: JettonTransferNotification):
    // Accept and accumulate
    self.total_received += msg.amount

receive("withdraw"):
    require(sender() == self.owner, "Only owner")
    // Transfer all SERPO balance to owner
    send_all_jettons(self.owner)

get fun balance(): Int
get fun totalReceived(): Int
```

### 6.4 Collateral Vault Contract

**Contract Name:** `SerpoCollateralVault`
**Language:** Tact

#### 6.4.1 Storage Variables

```
contract SerpoCollateralVault {
    owner: Address                          // Platform admin
    vault_wallet: Address                   // Trading fee vault address
    fee_rate: Int                           // 7 (percent)
    vault_share: Int                        // 4 (out of 7)
    referrer_share: Int                     // 3 (out of 7)
    balances: map<Address, Int>             // Per-user collateral balances
    total_deposited: Int                    // Running total deposits
}
```

#### 6.4.2 Entry Points / Messages

| Message | Parameters | Description |
|---------|------------|-------------|
| `receive(msg: JettonTransferNotification)` | `amount`, `sender` | Deposit: receive SERPO collateral from user |
| `receive("deductFee")` | `trader: Address, fee_amount: Int, referrer: Address?` | Owner-only: deduct fee from trader's collateral after profitable CEX trade |
| `receive("withdraw")` | `amount: Int` | User withdraws unused collateral |
| `get fun collateralOf(user: Address): Int` | `user` | Read-only: returns user's collateral balance |
| `get fun totalDeposited(): Int` | — | Read-only: total deposited collateral |

#### 6.4.3 Core Logic (Pseudocode)

```
// Deposit collateral
on JettonTransferNotification(amount, sender, payload):
    depositor = parse_sender(payload)
    current_balance = self.balances.get(depositor) ?? 0
    self.balances.set(depositor, current_balance + amount)
    self.total_deposited += amount

// Deduct fee after profitable CEX trade (owner-only)
on deductFee(trader, fee_amount, referrer):
    require(sender() == self.owner, "Only owner")
    
    balance = self.balances.get(trader)
    require(balance != null, "No collateral")
    require(balance >= fee_amount, "Insufficient collateral")
    
    // Deduct from balance
    self.balances.set(trader, balance - fee_amount)
    
    // Split and distribute
    vault_amount = fee_amount * self.vault_share / (self.vault_share + self.referrer_share)
    
    if referrer != null:
        referrer_amount = fee_amount - vault_amount
        send_jetton(referrer, referrer_amount)
        send_jetton(self.vault_wallet, vault_amount)
    else:
        send_jetton(self.vault_wallet, fee_amount)

// Withdraw unused collateral
on withdraw(amount):
    balance = self.balances.get(sender())
    require(balance != null, "No collateral")
    require(balance >= amount, "Insufficient balance")
    
    self.balances.set(sender(), balance - amount)
    send_jetton(sender(), amount)
```

#### 6.4.4 Gas Estimation

| Operation | Estimated Gas (TON) |
|-----------|---------------------|
| Deposit collateral | ~0.05 TON |
| Fee deduction (with referrer) | ~0.08 TON |
| Fee deduction (no referrer) | ~0.05 TON |
| Withdraw collateral | ~0.05 TON |
| Balance query | Free (get method) |

---

## 7. Data Requirements

### 7.1 On-Chain Data (Smart Contract Storage)

| Data Element | Storage Location | Type | Retention |
|--------------|-----------------|------|-----------|
| User subscription expiry | Subscription Contract | Int (Unix timestamp) | Permanent (overwritten on renewal) |
| User referrer wallet | Subscription Contract | Address (nullable) | Permanent |
| Subscription price | Subscription Contract | Int (nanoSERPO) | Updatable by owner |
| Vault wallet address | Subscription Contract | Address | Updatable by owner |
| Total revenue received | Vault Contract | Int | Permanent (cumulative) |
| Fee split ratios | Fee Distribution Contract | Int (numerator) | Updatable by owner |
| Per-user collateral balance | Collateral Vault Contract | Map<Address, Int> | Updated on deposit/withdrawal/deduction |
| Total collateral deposited | Collateral Vault Contract | Int | Permanent (cumulative) |

### 7.2 Off-Chain Data (Database)

#### 7.2.1 New Database Tables

**`user_wallets_extended`** (extends existing `user_wallets`)

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| user_id | BIGINT FK | Reference to users table |
| wallet_address | VARCHAR(68) | TON wallet address |
| referrer_wallet | VARCHAR(68) | Referrer's wallet address |
| subscription_status | ENUM | 'active', 'expired', 'never' |
| subscription_expiry | TIMESTAMP | Cached from smart contract |
| subscription_cache_at | TIMESTAMP | When subscription was last verified |
| connection_method | ENUM | 'auto_generated', 'ton_connect' |
| created_at | TIMESTAMP | Record creation |
| updated_at | TIMESTAMP | Last update |

**`referral_earnings`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| referrer_wallet | VARCHAR(68) | Wallet earning the reward |
| referred_wallet | VARCHAR(68) | Wallet that generated the earning |
| amount_serpo | DECIMAL(20,9) | SERPO amount earned |
| source | ENUM | 'subscription', 'trading_fee' |
| tx_hash | VARCHAR(64) | On-chain transaction hash |
| created_at | TIMESTAMP | Earning timestamp |

**`subscription_transactions`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| wallet_address | VARCHAR(68) | Subscriber wallet |
| amount_serpo | DECIMAL(20,9) | SERPO paid |
| amount_usd | DECIMAL(10,2) | USD equivalent at time |
| referrer_wallet | VARCHAR(68) | Referrer (nullable) |
| tx_hash | VARCHAR(64) | On-chain transaction hash |
| subscription_start | TIMESTAMP | Subscription period start |
| subscription_end | TIMESTAMP | Subscription period end |
| created_at | TIMESTAMP | Record creation |

**`trading_fee_distributions`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| trader_wallet | VARCHAR(68) | Trader's wallet |
| trade_profit_usd | DECIMAL(12,2) | Gross profit |
| fee_serpo | DECIMAL(20,9) | Total 7% fee in SERPO |
| vault_share_serpo | DECIMAL(20,9) | 4% to vault |
| referrer_share_serpo | DECIMAL(20,9) | 3% to referrer |
| referrer_wallet | VARCHAR(68) | Referrer (nullable) |
| tx_hash | VARCHAR(64) | Distribution tx hash |
| created_at | TIMESTAMP | Distribution timestamp |

**`collateral_deposits`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| wallet_address | VARCHAR(68) | Depositor wallet |
| amount_serpo | DECIMAL(20,9) | SERPO deposited |
| action | ENUM | 'deposit', 'withdrawal', 'fee_deduction' |
| balance_after | DECIMAL(20,9) | Collateral balance after action |
| tx_hash | VARCHAR(64) | On-chain transaction hash |
| created_at | TIMESTAMP | Action timestamp |

**`grid_bot_configurations`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| user_id | BIGINT FK | Reference to users table |
| market_type | ENUM | 'crypto', 'forex', 'stocks' |
| exchange | VARCHAR(50) | Exchange/platform name (e.g., Binance, OANDA) |
| trading_pair | VARCHAR(20) | Trading pair (e.g., BTC/USDT, EUR/USD) |
| grid_spacing_mode | ENUM | 'static', 'adaptive_atr' |
| grid_levels | INT | Number of grid levels |
| price_range_low | DECIMAL(20,8) | Lower grid boundary |
| price_range_high | DECIMAL(20,8) | Upper grid boundary |
| capital_allocated | DECIMAL(20,2) | Capital assigned to this grid |
| direction | ENUM | 'long', 'short', 'both' |
| status | ENUM | 'active', 'paused', 'stopped' |
| created_at | TIMESTAMP | Grid creation |
| updated_at | TIMESTAMP | Last update |

**`grid_bot_trades`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| grid_config_id | BIGINT FK | Reference to grid_bot_configurations |
| trade_id | VARCHAR(100) | Exchange trade ID |
| side | ENUM | 'buy', 'sell' |
| entry_price | DECIMAL(20,8) | Entry price |
| exit_price | DECIMAL(20,8) | Exit price (nullable if open) |
| amount | DECIMAL(20,8) | Trade amount |
| profit | DECIMAL(20,8) | Net profit/loss (nullable if open) |
| fee_deducted | DECIMAL(20,9) | SERPO fee deducted (if profitable) |
| status | ENUM | 'open', 'closed', 'cancelled' |
| opened_at | TIMESTAMP | Trade open time |
| closed_at | TIMESTAMP | Trade close time (nullable) |

**`grid_bot_backtests`**

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Auto-increment |
| grid_config_id | BIGINT FK | Reference to grid_bot_configurations |
| start_date | DATE | Backtest period start |
| end_date | DATE | Backtest period end |
| total_trades | INT | Number of simulated trades |
| total_profit | DECIMAL(20,8) | Net simulated profit |
| win_rate | DECIMAL(5,2) | Win rate percentage |
| max_drawdown | DECIMAL(5,2) | Maximum drawdown percentage |
| sharpe_ratio | DECIMAL(8,4) | Risk-adjusted return |
| created_at | TIMESTAMP | Backtest run timestamp |

### 7.3 Data Migration Strategy

The existing Serpo AI database (`users`, `user_wallets`, `user_alerts`, etc.) SHALL be preserved. New tables listed above SHALL be added via Laravel migrations without modifying existing schemas. Foreign keys SHALL reference the existing `users` table.

---

## 8. Interface Requirements

### 8.1 User Interfaces

#### 8.1.1 Telegram Bot Commands (New/Modified)

| Command | Description | Parameters |
|---------|-------------|------------|
| `/connectwallet` | Initiate TON Connect wallet connection | None |
| `/subscribe` | Begin subscription payment flow | None |
| `/subscription` | Check current subscription status and expiry | None |
| `/renew` | Renew/extend subscription | None |
| `/referral` | Display user's referral link and earnings | None |
| `/earnings` | Show detailed referral earnings history | None |
| `/disconnect` | Disconnect wallet from Serpo AI | None |
| `/collateral` | View collateral balance and deposit/withdraw | None |
| `/deposit [amount]` | Deposit SERPO collateral for CEX trading fees | SERPO amount |
| `/withdraw [amount]` | Withdraw unused collateral | SERPO amount |
| `/grid` | View active grid bot configurations | None |
| `/grid_start [pair] [market]` | Start a new grid bot on specified pair and market | Trading pair, market type |
| `/grid_stop [id]` | Stop a running grid bot | Grid bot ID |
| `/grid_status` | View grid bot P/L and positions across all markets | None |
| `/backtest [pair] [days]` | Run historical backtest simulation | Trading pair, days |

#### 8.1.2 Website UI Components

| Component | Description |
|-----------|-------------|
| Wallet Connect Button | TON Connect modal with QR code |
| Subscription Dashboard | Status, expiry countdown, renew button |
| Referral Panel | Unique link, copy button, earnings chart |
| Payment Modal | SERPO amount, confirm in wallet |
| Transaction History | List of all subscription/referral transactions |
| Collateral Dashboard | Collateral balance, deposit/withdraw controls, fee deduction history |
| Grid Bot Dashboard | Active grids, P/L per market, grid level visualizations |
| Grid Bot Configuration | Create/edit grid parameters, market selection, capital allocation |
| Backtesting Panel | Run simulations, view historical performance analytics |
| Risk Management | Per-market exposure, stop-loss configuration, leverage controls |

#### 8.1.3 Mobile App Screens

| Screen | Description |
|--------|-------------|
| Wallet Screen | Connect/disconnect wallet, balance display |
| Subscription Screen | Plan details, subscribe/renew CTA |
| Referral Screen | Share link, earnings tracker |
| Settings | Wallet management, notification preferences |

### 8.2 API Interfaces

#### 8.2.1 Backend REST APIs (New Endpoints)

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `POST /api/wallet/connect` | POST | Register wallet connection | Session |
| `GET /api/subscription/status` | GET | Check subscription status | Wallet-signed |
| `POST /api/subscription/prepare` | POST | Prepare subscription transaction | Session |
| `GET /api/referral/link` | GET | Get user's referral link | Session |
| `GET /api/referral/earnings` | GET | Fetch referral earnings history | Session |
| `POST /api/trading/report-profit` | POST | Report trading profit for fee calc | Internal |
| `GET /api/vault/balance` | GET | Public vault balance query | None |
| `GET /api/collateral/balance` | GET | User's collateral vault balance | Wallet-signed |
| `POST /api/collateral/deposit` | POST | Prepare collateral deposit transaction | Session |
| `POST /api/collateral/withdraw` | POST | Withdraw unused collateral | Wallet-signed |
| `GET /api/collateral/history` | GET | Collateral deposit/deduction history | Session |
| `POST /api/grid/create` | POST | Create and start a new grid bot | Session |
| `GET /api/grid/list` | GET | List user's active grid configurations | Session |
| `PUT /api/grid/{id}/pause` | PUT | Pause a running grid bot | Session |
| `PUT /api/grid/{id}/resume` | PUT | Resume a paused grid bot | Session |
| `DELETE /api/grid/{id}` | DELETE | Stop and remove a grid bot | Session |
| `GET /api/grid/{id}/trades` | GET | Get trade history for a grid bot | Session |
| `GET /api/grid/{id}/pnl` | GET | Get P/L summary for a grid bot | Session |
| `POST /api/grid/backtest` | POST | Run backtest simulation | Session |
| `GET /api/grid/dashboard` | GET | Multi-market grid overview | Session |

#### 8.2.2 External API Dependencies

| API | Purpose | Frequency |
|-----|---------|-----------|
| TonAPI v2 | Read contract state, transaction history | Per user login + 5-min cache |
| DeDust API | SERPO/TON price for USD conversion | Every subscription attempt |
| STON.fi API | Alternative SERPO price feed | Fallback |
| CoinGecko API | SERPO price (existing integration) | Fallback |
| Binance API | Crypto trading/grid bot execution (existing) | Per trade |
| KuCoin API | Crypto grid bot execution | Per trade |
| Bybit API | Crypto grid bot execution | Per trade |
| OANDA API | Forex grid bot execution | Per trade |
| FXCM API | Forex grid bot execution | Per trade |
| MetaTrader 4/5 API | Forex grid bot execution (MT5 Python API) | Per trade |
| Interactive Brokers API | Stock grid bot execution | Per trade |
| Alpaca API | Stock grid bot execution | Per trade |

### 8.3 Hardware Interfaces

No specific hardware interfaces required. The system operates on standard VPS infrastructure (existing Nginx + PHP-FPM stack) with blockchain interaction via HTTP APIs.

### 8.4 Software Interfaces

| Interface | Component | Protocol |
|-----------|-----------|----------|
| TON Blockchain | Smart contracts | TON P2P Network |
| TonAPI | Blockchain data | HTTPS REST |
| TON Connect | Wallet bridge | WebSocket + HTTPS |
| Laravel Queue | Async job processing | Database / Redis |
| MySQL / PostgreSQL | Data persistence | TCP/IP |

---

## 9. Security Requirements

### 9.1 Wallet Security

| ID | Requirement | Priority |
|----|-------------|----------|
| **SEC-W01** | The preferred approach (Mode B) SHALL NOT store user private keys — all transactions require user wallet approval via TON Connect | Critical |
| **SEC-W02** | If Mode A (auto-generated wallets) is used, private keys SHALL be encrypted with AES-256-GCM before database storage | Critical |
| **SEC-W03** | Encryption keys for wallet storage SHALL be managed via environment variables, never committed to source control | Critical |
| **SEC-W04** | The platform SHALL recommend Mode B (TON Connect) as the primary wallet approach | High |

### 9.2 Smart Contract Security

| ID | Requirement | Priority |
|----|-------------|----------|
| **SEC-SC01** | All smart contracts SHALL undergo professional third-party security audit before mainnet deployment | Critical |
| **SEC-SC02** | Contracts SHALL validate the Jetton sender to prevent fake token attacks | Critical |
| **SEC-SC03** | Contracts SHALL implement reentrancy protection | Critical |
| **SEC-SC04** | Vault withdrawal SHALL be restricted to the owner address with `require(sender() == owner)` | Critical |
| **SEC-SC05** | The vault SHOULD implement multisignature control (2-of-3 or 3-of-5) | High |
| **SEC-SC06** | Contract source code SHALL be verified and published on-chain for transparency | High |
| **SEC-SC07** | Smart contracts SHALL handle integer overflow/underflow safely | Critical |
| **SEC-SC08** | Contracts SHALL validate all input parameters and reject malformed payloads | Critical |

### 9.3 Exchange API Security

| ID | Requirement | Priority |
|----|-------------|----------|
| **SEC-E01** | Exchange API keys SHALL be configured with trade-only permissions — NO withdrawal access | Critical |
| **SEC-E02** | API keys SHALL be encrypted at rest in the database using AES-256 | Critical |
| **SEC-E03** | API key access SHALL be rate-limited and monitored | High |
| **SEC-E04** | API key transmission SHALL occur only over TLS 1.2+ | Critical |

### 9.4 Backend Security

| ID | Requirement | Priority |
|----|-------------|----------|
| **SEC-B01** | All API endpoints SHALL validate and sanitize input parameters | Critical |
| **SEC-B02** | Authentication SHALL use wallet-signed messages for sensitive operations | High |
| **SEC-B03** | Rate limiting SHALL be applied to all API endpoints (100 req/min per user) | High |
| **SEC-B04** | Backend SHALL implement CSRF protection on all state-changing endpoints | Critical |
| **SEC-B05** | Database queries SHALL use parameterized statements (Laravel Eloquent) | Critical |
| **SEC-B06** | All server communication SHALL use HTTPS with valid TLS certificates | Critical |
| **SEC-B07** | Security monitoring and alerting SHALL be implemented for suspicious activity | High |

### 9.5 Threat Model

| Threat | Attack Vector | Mitigation |
|--------|--------------|------------|
| Fake Jetton payment | Attacker sends fake token to subscription contract | SEC-SC02: Validate Jetton master address |
| Self-referral abuse | User refers themselves to earn 50% back | FR-R04: Smart contract rejects referrer == sender |
| Unauthorized vault drain | Attacker attempts to withdraw vault funds | SEC-SC04: Owner-only withdrawal with address check |
| Replay attack | Resubmitting old subscription proof | On-chain state: expiry is verified in real-time |
| Man-in-the-middle | Intercepting wallet connection | TON Connect uses end-to-end encryption |
| Database breach | SQL injection or server compromise | SEC-B05: Parameterized queries; SEC-W02: Encrypted keys |
| Griefing / DoS | Spam transactions to contract | TON gas fees make spam costly; backend rate limiting |
| Oracle manipulation | Manipulating SERPO price for cheap subs | Use multiple price sources with median calculation |
| Reentrancy | Recursive calls to drain contract | SEC-SC03: Checks-effects-interactions pattern |
| Collateral underfunding | User has insufficient collateral for fee deduction | FR-C04: Balance check before deduction; bot paused if insufficient |
| Unauthorized collateral withdrawal | Attacker attempts to withdraw another user's collateral | FR-C07: Only wallet owner can trigger withdrawal |
| Grid bot API key abuse | Compromised API keys used maliciously | SEC-E01: Trade-only permissions, no withdrawals; rate limiting |

---

## 10. Integration Requirements

### 10.1 Integration with Existing Serpo AI Backend

| ID | Requirement | Description |
|----|-------------|-------------|
| **IR-01** | New services SHALL follow the existing Laravel service pattern in `app/Services/` | Architecture consistency |
| **IR-02** | A new `SubscriptionVerificationService` SHALL be created to check on-chain subscription status | Core integration |
| **IR-03** | A new `WalletConnectionService` SHALL handle TON Connect session management | Wallet flows |
| **IR-04** | A new `ReferralTrackingService` SHALL manage referral links, tracking, and earnings queries | Referral system |
| **IR-05** | Existing `CommandHandler` SHALL be extended with new commands (`/subscribe`, `/referral`, etc.) | Bot commands |
| **IR-06** | The existing `BlockchainMonitorService` SHALL be extended to watch subscription contract events | Event monitoring |
| **IR-07** | Integration SHALL NOT break existing 40+ bot commands or services | Backward compatibility |
| **IR-07a** | A new `CollateralVaultService` SHALL manage collateral deposits, withdrawals, and fee deductions | Collateral system |
| **IR-07b** | A new `GridBotService` SHALL manage grid bot configurations, execution, and monitoring | Grid bot system |
| **IR-07c** | A new `MarketConnectorService` SHALL abstract connections to crypto, forex, and stock exchange APIs | Multi-market |

### 10.2 Blockchain Integration

| ID | Requirement | Description |
|----|-------------|-------------|
| **IR-08** | Backend SHALL use `ton-core` or `TonWeb` npm package for blockchain interactions | Library choice |
| **IR-09** | A Node.js microservice MAY be deployed alongside Laravel for native TON library access | Architecture option |
| **IR-10** | Alternative: PHP wrapper around `ton-core` CLI calls for pure-Laravel integration | Architecture option |
| **IR-11** | Contract ABI/interface definitions SHALL be stored in the repository for type-safe interaction | Developer experience |

### 10.3 DEX Integration

| ID | Requirement | Description |
|----|-------------|-------------|
| **IR-12** | The system SHALL integrate with STON.fi and/or DeDust for SERPO price feeds | Price oracle |
| **IR-13** | Trading fee conversion (fiat → SERPO) SHALL use DEX swap APIs | On-chain fee payment |
| **IR-14** | DEX price feeds SHALL be cached for 60 seconds to prevent excessive API calls | Performance |

### 10.4 Multi-Market Exchange Integration

| ID | Requirement | Description |
|----|-------------|-------------|
| **IR-15** | The system SHALL integrate with crypto exchanges (Binance, KuCoin, Bybit) via REST and WebSocket APIs | Crypto market |
| **IR-16** | The system SHALL integrate with forex platforms (OANDA, FXCM, MetaTrader 4/5) via their respective APIs | Forex market |
| **IR-17** | The system SHALL integrate with stock platforms (Interactive Brokers, Alpaca) via their APIs | Stock market |
| **IR-18** | All exchange connectors SHALL handle authentication, rate-limit management, and error recovery | Reliability |
| **IR-19** | WebSocket streams SHALL be used for real-time price and order updates where available | Performance |
| **IR-20** | The Market Connector Layer SHALL handle API-specific quirks (rate limits, reconnections, order reconciliation) | Robustness |

---

## 11. User Stories & Use Cases

### 11.1 User Stories

| ID | As a... | I want to... | So that... | Priority |
|----|---------|-------------|------------|----------|
| US-01 | New User | Connect my Tonkeeper wallet to Serpo AI | I can access the platform using my existing wallet | Critical |
| US-02 | Connected User | Subscribe to Serpo AI by paying SERPO tokens | I can unlock all AI trading tools and bots | Critical |
| US-03 | Subscriber | See my subscription expiry date and status | I know when to renew | High |
| US-04 | Subscriber | Renew my subscription before it expires | I don't lose access to AI tools | High |
| US-05 | User | Share my referral link with friends | I can earn passive income when they subscribe | High |
| US-06 | Referrer | See how much I've earned from referrals | I can track my passive income | Medium |
| US-07 | Referrer | Receive 50% of each referred subscription automatically | I earn without manual intervention | Critical |
| US-08 | Subscriber | Use AI trading bots on Binance/OKX | I can automate my trading strategy | High |
| US-09 | Trader | Know that only 7% is taken from profits | I understand the fee structure | High |
| US-10 | Admin | Withdraw accumulated vault funds | I can fund project operations | Critical |
| US-11 | Admin | Update subscription price in SERPO | I can adjust pricing as token value changes | High |
| US-12 | User | Access Serpo AI via Telegram, website, or app | I can use the platform on any device | High |
| US-13 | Trader | Deposit SERPO collateral to guarantee my trading fees | My grid bot can trade on centralized exchanges | High |
| US-14 | Trader | Withdraw my unused collateral at any time | I retain full control of my funds | High |
| US-15 | Trader | Run an adaptive grid bot on crypto markets | I can profit from sideways markets automatically | High |
| US-16 | Trader | Run a grid bot on forex pairs (EUR/USD, GBP/USD) | I can diversify my automated trading across markets | Medium |
| US-17 | Trader | Run a grid bot on stock markets | I can trade equities with the same AI-driven grid strategy | Medium |
| US-18 | Trader | Backtest my grid strategy with historical data | I can validate strategy before risking real capital | Medium |
| US-19 | Trader | See real-time P/L across all my active grids | I can monitor multi-market performance in one dashboard | Medium |
| US-20 | Trader | Trade on DeDust/STON.fi with automatic fee deduction | I don't need to deposit collateral for DEX trades | High |

### 11.2 Use Case Descriptions

#### UC-01: Wallet Connection via TON Connect

| Field | Description |
|-------|-------------|
| **Actor** | Registered User |
| **Precondition** | User has a TON wallet app installed (Tonkeeper, MyTonWallet) |
| **Trigger** | User clicks "Connect Wallet" or sends `/connectwallet` in Telegram |
| **Main Flow** | 1. System generates TON Connect session<br>2. QR code displayed (website/app) or deep link sent (Telegram)<br>3. User scans QR / clicks link in wallet app<br>4. Wallet signs authentication message<br>5. System stores wallet address as user ID<br>6. System checks for referral parameter<br>7. User sees "Wallet Connected" confirmation |
| **Postcondition** | User account linked to wallet address; referrer recorded if applicable |
| **Alternative Flow** | If wallet connection fails → display error and retry option |

#### UC-02: Subscription Payment

| Field | Description |
|-------|-------------|
| **Actor** | Connected User (wallet linked) |
| **Precondition** | User has connected wallet with sufficient SERPO balance |
| **Trigger** | User clicks "Subscribe" or sends `/subscribe` |
| **Main Flow** | 1. Backend calculates current SERPO price equivalent of $10<br>2. System displays amount: "Pay X SERPO for 30 days"<br>3. System prepares Jetton transfer transaction to subscription contract<br>4. Transaction payload includes referrer address (if any)<br>5. User approves transaction in wallet<br>6. Smart contract receives Jetton notification<br>7. Contract validates amount and token<br>8. Contract splits payment (50/50 if referral, 100% vault if not)<br>9. Contract sets expiry = now + 30 days<br>10. Backend detects state change → unlocks AI tools |
| **Postcondition** | User subscription active for 30 days; referrer paid; vault funded |
| **Exception** | Insufficient balance → wallet shows error; wrong amount → contract rejects |

#### UC-03: Trading Fee Distribution

| Field | Description |
|-------|-------------|
| **Actor** | System (Trading Bot) |
| **Precondition** | Subscriber has active trading bot with profitable trade |
| **Trigger** | Trading bot closes a profitable position |
| **Main Flow** | 1. Bot calculates net profit in USD<br>2. Backend calculates 7% fee<br>3. Fee amount converted to SERPO via DEX<br>4. SERPO sent to Fee Distribution Contract<br>5. Contract splits: 4% to vault, 3% to referrer<br>6. If no referrer: full 7% to vault<br>7. Distribution logged in database |
| **Postcondition** | Fee distributed; vault and referrer credited |

---

## 12. Acceptance Criteria

### 12.1 Smart Contract Acceptance

| ID | Criterion | Test Method |
|----|-----------|-------------|
| AC-SC01 | Subscription contract accepts valid SERPO payment and sets 30-day expiry | Automated test on testnet |
| AC-SC02 | Contract rejects payment below required amount | Negative test |
| AC-SC03 | Contract rejects non-SERPO Jetton transfers | Negative test with fake token |
| AC-SC04 | Referral split sends exactly 50% to referrer and 50% to vault | Balance verification |
| AC-SC05 | Non-referral payment sends 100% to vault | Balance verification |
| AC-SC06 | Self-referral is rejected by the contract | Negative test |
| AC-SC07 | Subscription renewal extends existing expiry by 30 days (not reset) | State verification |
| AC-SC08 | Only owner can withdraw from vault | Access control test |
| AC-SC09 | Fee distribution splits 4/3 correctly | Balance verification |
| AC-SC10 | Contract handles edge cases (zero referrer, expired user, concurrent txns) | Stress test |
| AC-SC11 | Gas consumption is within estimated ranges | Gas profiling |
| AC-SC12 | Collateral deposit increases per-user balance correctly | Balance verification |
| AC-SC13 | Collateral fee deduction fails when balance < fee amount | Negative test |
| AC-SC14 | Collateral fee deduction splits 4/3 correctly | Balance verification |
| AC-SC15 | Only wallet owner can withdraw collateral | Access control test |
| AC-SC16 | Collateral withdrawal fails when requested amount > balance | Negative test |
| AC-SC17 | DEX trades auto-deduct 7% fee without requiring collateral | Integration test |

### 12.2 Backend Integration Acceptance

| ID | Criterion | Test Method |
|----|-----------|-------------|
| AC-BE01 | Backend correctly reads subscription status from contract | Integration test |
| AC-BE02 | Subscription check is cached for 5 minutes | Cache verification |
| AC-BE03 | Expired subscription blocks premium features | Access control test |
| AC-BE04 | Active subscription unlocks all AI tools | Feature access test |
| AC-BE05 | Referral link generation works for all users | Unit test |
| AC-BE06 | Earnings history returns correct transaction data | Data verification |
| AC-BE07 | New bot commands respond correctly | Bot test |
| AC-BE08 | Existing 40+ commands are not broken | Regression test |
| AC-BE09 | Wallet connection works on website, bot, and app | Cross-platform test |
| AC-BE10 | Collateral deposit/withdrawal flows work end-to-end | Integration test |
| AC-BE11 | Grid bot creates and executes grids on crypto test exchange | Integration test |
| AC-BE12 | Grid bot correctly calculates and records profit/loss per trade | Unit test |
| AC-BE13 | Grid bot AI signal layer pauses grid during trending markets | Simulation test |
| AC-BE14 | Multi-market grid bot runs parallel grids across different exchanges | Load test |

### 12.3 Business Logic Acceptance

| ID | Criterion | Test Method |
|----|-----------|-------------|
| AC-BL01 | $10 USD → SERPO conversion uses real-time market price | Manual verification |
| AC-BL02 | 7% trading fee is only applied to profitable trades | Trade simulation |
| AC-BL03 | Referrer earns on every renewal, not just first subscription | Multi-month simulation |
| AC-BL04 | Vault accumulates correct total across all revenue streams | Accounting audit |
| AC-BL05 | Collateral fee deduction matches 7% of recorded trade profit | Trade simulation |
| AC-BL06 | Grid bot adaptive spacing adjusts correctly based on ATR/volatility | Backtesting |
| AC-BL07 | Grid bot backtest results match expected historical returns within tolerance | Data verification |

---

## 13. Implementation Plan

### 13.1 Project Phases

#### Phase 1: Smart Contracts + Backend Integration (Week 1)

| Day | Milestone | Deliverables |
|-----|-----------|-------------|
| 1–2 | Environment setup + Subscription Contract | Tact dev environment, Subscription contract with tests on testnet |
| 3 | Fee Distribution + Vault contracts | Fee distributor + Vault deployed to testnet |
| 4 | Collateral Vault contract | Collateral deposit/deduct/withdraw on testnet |
| 5 | Backend services + database | Laravel services, migrations, models, API endpoints |

**Key Activities:**
- Set up Tact development environment with TON SDK
- Implement all 4 smart contracts using AI coding agent for rapid iteration
- Write unit tests using TON contract testing framework
- Deploy all contracts to TON Testnet
- Create Laravel services: `SubscriptionVerificationService`, `WalletConnectionService`, `ReferralTrackingService`, `CollateralVaultService`
- Write database migrations for all new tables
- Implement new API endpoints and Telegram bot commands

#### Phase 2: Frontend + Grid Bot Engine (Week 2)

| Day | Milestone | Deliverables |
|-----|-----------|-------------|
| 1 | TON Connect integration | Wallet connect flow for website and Telegram |
| 2 | UI components | Subscription dashboard, referral panel, collateral dashboard |
| 3–4 | Grid Bot Engine (crypto first) | Market connectors (Binance, KuCoin, Bybit), adaptive grid engine |
| 5 | AI Signal Layer + Risk Management | Trend detection, stop-loss, leverage control |

**Key Activities:**
- Integrate TON Connect SDK into frontend
- Build dashboards (subscription, referral, collateral, grid bot monitoring)
- Build Market Connector Layer for crypto exchanges (REST + WebSocket)
- Implement Grid Engine with adaptive ATR-based spacing
- Implement AI Signal Layer for grid activation/pause
- Build Risk & Capital Management module

#### Phase 3: Testing + Deployment (Week 3)

| Day | Milestone | Deliverables |
|-----|-----------|-------------|
| 1–2 | Forex + stock connectors | OANDA, MT4/5, Interactive Brokers, Alpaca integrations |
| 3 | Backtesting engine | Historical data simulation and validation |
| 4 | End-to-end testing | Full flow testing, regression, bug fixes |
| 5 | Mainnet deployment | Live contracts, production backend, grid bot launch |

**Key Activities:**
- Add forex and stock exchange connectors
- Build backtesting engine with historical data
- End-to-end testing of all smart contract flows
- Regression test existing 40+ bot commands
- Deploy smart contracts to TON Mainnet
- Deploy grid bot engine (Docker containers)
- Soft launch with crypto grids, then forex and stocks
- Monitor systems post-launch

> **Note:** Security audit by a third-party firm is recommended before mainnet deployment but can be scheduled independently. A self-audit using AI-assisted static analysis tools is included in the 3-week timeline.

### 13.2 Resource Requirements

| Role | Count | Duration | Responsibility |
|------|-------|----------|----------------|
| Solo Developer + AI Coding Agent | 1 | 2–3 weeks | All development: smart contracts, backend, frontend, grid bot engine |
| Security Auditor (External, optional) | 1 firm | 1 week | Smart contract audit (can be scheduled post-launch) |

### 13.3 Development Milestones & Dependencies

```
Week 1: Smart Contracts + Backend ──────┐
  (4 contracts on testnet + Laravel       │
   services + DB + API endpoints)         ├──► Week 3: Testing + Deployment
                                          │
Week 2: Frontend + Grid Bot Engine ──────┘
  (TON Connect, dashboards, grid engine,
   AI signals, risk management)
```

**Critical Path:** Smart Contracts → Backend Integration → Frontend + Grid Bot → Testing → Deploy

**AI Coding Agent Acceleration:**
- AI generates boilerplate Tact contracts from specifications
- AI handles Laravel service scaffolding, migrations, and API routes
- AI assists with exchange API connector code and WebSocket handling
- AI-assisted testing generates comprehensive test cases
- Single developer reviews, tests, and refines all AI-generated code

**Dependencies:**
- Backend integration starts same week as contracts (Day 5 of Week 1)
- Frontend and grid bot work begins Week 2 after backend APIs are ready
- End-to-end testing in Week 3 validates all flows before mainnet deployment
- External security audit is optional and can be scheduled post-launch

---

## 14. Cost Estimation

### 14.1 Development Cost Context

**Benchmark:** The existing Serpo AI Telegram bot (Laravel 12, 40+ commands, multi-AI integration, real-time blockchain monitoring, whale alerts, token verification across 20+ chains) was developed by a single developer with AI coding agent for **1,300,000 UGX** (~$350 USD).

The smart contract system represents a comparable-to-higher complexity extension:
- 4 Tact smart contracts (Subscription, Fee Distribution, Vault, Collateral Vault)
- Backend integration (new Laravel services, migrations, API endpoints)
- Frontend wallet integration (TON Connect, dashboards)
- Multi-market grid bot engine (crypto, forex, stocks)
- On-chain code handles real funds → higher security rigor required
- Specialized blockchain knowledge (Tact, TON, Jettons)

### 14.2 Development Cost Estimate (Single Developer + AI Coding Agent)

| Component | Complexity vs Bot | Estimated Cost (UGX) | Estimated Cost (USD) |
|-----------|-------------------|---------------------|---------------------|
| **4 Smart Contracts** (Tact) | Higher — handles real funds, security-critical | 1,500,000 – 2,000,000 | ~$405 – $540 |
| **Backend Integration** (Laravel services, DB, APIs) | Similar — extends existing codebase | 800,000 – 1,200,000 | ~$215 – $325 |
| **Frontend & Wallet** (TON Connect, dashboards) | Lower — UI layer with SDK integration | 500,000 – 800,000 | ~$135 – $215 |
| **Grid Bot Engine** (Python/Node.js, multi-market) | Higher — async execution, AI signals, risk mgmt | 1,200,000 – 1,800,000 | ~$325 – $485 |
| **Testing & Deployment** | Standard | 300,000 – 500,000 | ~$80 – $135 |
| | | | |
| **Total Development** | | **4,300,000 – 6,300,000 UGX** | **~$1,160 – $1,700 USD** |

> **Pricing Rationale:** The smart contract + grid bot system is approximately 3.3–4.8× the complexity of the original bot. The original bot at 1.3M UGX establishes a baseline per unit of work for a solo dev with AI coding agent. Smart contracts command a premium due to security criticality (code handles real money on-chain).

### 14.3 Recommended Pricing Tiers

| Tier | Scope | Price (UGX) | Price (USD) |
|------|-------|------------|------------|
| **Core** | 4 Smart Contracts + Backend Integration only | 2,500,000 | ~$675 |
| **Standard** | Core + Frontend + TON Connect | 3,500,000 | ~$945 |
| **Full** | Standard + Multi-Market Grid Bot Engine | 5,000,000 | ~$1,350 |
| **Premium** | Full + External Security Audit coordination | 6,500,000 | ~$1,755 |

### 14.4 Security & Audit Costs (External, Optional)

| Item | Estimated Cost |
|------|---------------|
| Smart Contract Security Audit (4 contracts, reputable firm) | $2,000 – $10,000 USD |
| Self-audit with AI-assisted static analysis | Included in development |
| Bug bounty program (optional, post-launch) | $500 – $2,000 USD |

### 14.5 Infrastructure Costs (Annual)

| Item | Monthly | Annual |
|------|---------|--------|
| VPS / Cloud Hosting (existing + grid bot servers) | $200 – $600 | $2,400 – $7,200 |
| Database hosting (PostgreSQL primary) | $50 – $150 | $600 – $1,800 |
| Redis cache server | $25 – $75 | $300 – $900 |
| TonAPI Pro subscription | $0 – $100 | $0 – $1,200 |
| Domain & SSL certificates | $10 | $120 |
| Monitoring & logging services | $25 – $100 | $300 – $1,200 |
| CDN / DDoS protection | $20 – $80 | $240 – $960 |
| Exchange API subscriptions (pro tiers) | $0 – $200 | $0 – $2,400 |
| Docker container hosting (grid bots) | $50 – $200 | $600 – $2,400 |
| | | |
| **Subtotal Infrastructure** | **$380 – $1,315/mo** | **$4,560 – $15,780** |

### 14.6 Blockchain Costs

| Item | Estimated Cost |
|------|---------------|
| Contract deployment to Mainnet (4 contracts) | 2–5 TON (~$10 – $25) |
| Gas fees for owner operations (monthly) | 0.5–2 TON (~$2 – $10) |
| Testnet deployment (free) | $0 |
| | |
| **Subtotal Blockchain (Year 1)** | **$30 – $135** |

### 14.7 Ongoing Operational Costs (Monthly, Solo Dev)

| Item | Monthly Cost (UGX) | Monthly Cost (USD) |
|------|-------------------|-------------------|
| Maintenance & updates (part-time) | 300,000 – 500,000 | ~$80 – $135 |
| VPS / hosting | 75,000 – 225,000 | ~$20 – $60 |
| Exchange API subscriptions | 0 – 75,000 | $0 – $20 |
| | | |
| **Monthly Operations** | **375,000 – 800,000 UGX** | **~$100 – $215** |

### 14.8 Total Cost Summary

| Category | Low Estimate (UGX) | High Estimate (UGX) | Low (USD) | High (USD) |
|----------|-------------------|--------------------|-----------|-----------|
| Development (Solo Dev + AI Agent) | 4,300,000 | 6,300,000 | $1,160 | $1,700 |
| External Audit (optional) | 7,400,000 | 37,000,000 | $2,000 | $10,000 |
| Infrastructure (Year 1) | 4,500,000 | 15,000,000 | $1,215 | $4,050 |
| Blockchain Costs | 37,000 | 93,000 | $10 | $25 |
| | | | | |
| **Total (without external audit)** | **8,837,000** | **21,393,000** | **~$2,385** | **~$5,775** |
| **Total (with external audit)** | **16,237,000** | **58,393,000** | **~$4,385** | **~$15,775** |

> **For client pricing:** Recommended to quote **5,000,000 UGX (~$1,350 USD)** for the full package (contracts + backend + frontend + grid bot) as a single developer with AI coding agent, delivering in 2–3 weeks.

### 14.9 Revenue Projections

| Metric | Conservative | Moderate | Optimistic |
|--------|-------------|----------|------------|
| Monthly subscribers (Year 1 avg) | 500 | 2,000 | 10,000 |
| Subscription revenue/month | $5,000 | $20,000 | $100,000 |
| Active traders | 100 | 500 | 2,500 |
| Avg trading profit/trader/month | $500 | $1,000 | $2,000 |
| Trading fee revenue/month (7% × vault share) | $1,400 | $14,000 | $140,000 |
| Grid bot traders (forex + stocks) | 20 | 100 | 500 |
| Grid bot additional fee revenue/month | $560 | $5,600 | $56,000 |
| **Total monthly revenue** | **$6,960** | **$39,600** | **$296,000** |
| **Annual revenue** | **$83,520** | **$475,200** | **$3,552,000** |
| **Break-even (dev cost: 5M UGX)** | ~7 months | ~1.2 months | ~6 days |

---

## 15. Risk Assessment

### 15.1 Risk Register

| ID | Risk | Probability | Impact | Severity | Mitigation Strategy |
|----|------|------------|--------|----------|-------------------|
| R-01 | Smart contract vulnerability exploited post-deployment | Medium | Critical | **High** | Professional audit, bug bounty, staged rollout |
| R-02 | SERPO token price volatility makes $10 subscription unpredictable | High | Medium | **High** | Dynamic pricing with oracle; floor/ceiling bounds |
| R-03 | Low SERPO liquidity on DEX prevents fee conversion | Medium | High | **High** | Market-making program; multi-DEX integration |
| R-04 | TON network congestion delays subscription processing | Low | Medium | **Medium** | Retry logic; cached subscription status |
| R-05 | TonAPI service outage blocks subscription verification | Medium | High | **High** | Fallback to direct node queries; cached status |
| R-06 | Self-referral bypass via multiple wallets | Medium | Medium | **Medium** | KYC-light measures; pattern detection; earnings caps |
| R-07 | Regulatory action against token-based subscriptions | Low | Critical | **Medium** | Legal opinion; jurisdictional compliance review |
| R-08 | User wallet compromise leads to support burden | Medium | Low | **Low** | Non-custodial design; clear user responsibility |
| R-09 | Scope creep delays launch beyond 3 weeks | High | Medium | **High** | Strict scope management; MVP-first approach (crypto grids first) |
| R-10 | Solo developer unavailability | Medium | Critical | **High** | Documentation; AI agent can resume from codebase; version control |
| R-11 | Exchange API changes break trading bot integration | Medium | Medium | **Medium** | API version pinning; monitoring; abstraction layer |
| R-12 | Oracle/price manipulation for cheap subscriptions | Low | High | **Medium** | TWAP pricing; multi-source median; minimum SERPO floor |
| R-13 | Grid bot losses exceed user expectations | High | Medium | **High** | AI signal layer pauses grids in trending markets; mandatory backtesting; risk warnings |
| R-14 | Forex/stock broker API rate limits block grid execution | Medium | High | **High** | Co-located servers; batch order management; fallback brokers |
| R-15 | Collateral vault insufficient for accumulated fees | Medium | Medium | **Medium** | Low-balance alerts; bot pause when collateral < threshold |
| R-16 | Regulatory requirements differ across forex/stock/crypto markets | Medium | High | **High** | Market-specific compliance review; geo-restriction capabilities |

### 15.2 Risk Response Matrix

| Severity | Response |
|----------|----------|
| **Critical** | Immediate action required; project may be paused until resolved |
| **High** | Dedicated mitigation plan; regular monitoring; contingency budget allocated |
| **Medium** | Accepted with planned monitoring; trigger points defined for escalation |
| **Low** | Accepted; logged for awareness |

---

## 16. Testing Strategy

### 16.1 Testing Levels

| Level | Scope | Tools |
|-------|-------|-------|
| **Unit Testing** | Individual contract functions and backend methods | TON contract tester, PHPUnit |
| **Integration Testing** | Contract ↔ backend, backend ↔ frontend | Testnet deployment, Laravel test suite |
| **System Testing** | Full end-to-end subscription and referral flows | TON Testnet, staging environment |
| **Security Testing** | Vulnerability scanning, penetration testing | Third-party audit, OWASP tools |
| **Performance Testing** | Load/stress testing subscription verification | k6, Artillery |
| **User Acceptance Testing** | Beta user group validates real-world flows | Manual testing with real wallets |
| **Regression Testing** | Ensure existing Serpo AI features are unaffected | Existing PHPUnit suite + manual |

### 16.2 Smart Contract Test Cases

| ID | Test Case | Expected Result | Type |
|----|-----------|----------------|------|
| TC-SC01 | Send exact subscription amount in SERPO | Subscription activated for 30 days | Positive |
| TC-SC02 | Send less than subscription amount | Transaction rejected | Negative |
| TC-SC03 | Send more than subscription amount | Subscription activated; excess handled | Edge |
| TC-SC04 | Send non-SERPO Jetton to contract | Transaction rejected | Negative |
| TC-SC05 | Subscribe with valid referral | 50/50 split; both wallets credited | Positive |
| TC-SC06 | Subscribe with self as referrer | Transaction rejected | Negative |
| TC-SC07 | Subscribe with non-existent referrer | Transaction rejected or treated as no-referral | Negative |
| TC-SC08 | Renew while subscription is active | Expiry extended by 30 days from current expiry | Positive |
| TC-SC09 | Renew after subscription expired | New 30-day period from now | Positive |
| TC-SC10 | Owner withdraws vault funds | Funds transferred to owner | Positive |
| TC-SC11 | Non-owner attempts vault withdrawal | Transaction rejected | Negative |
| TC-SC12 | Fee distribution with referrer | 4/7 vault, 3/7 referrer | Positive |
| TC-SC13 | Fee distribution without referrer | 100% vault | Positive |
| TC-SC14 | Concurrent subscriptions from different users | All processed correctly | Stress |
| TC-SC15 | Owner updates subscription price | New price effective for next subscription | Positive |
| TC-SC16 | Send 0 amount to contract | Transaction rejected | Negative |
| TC-SC17 | Deposit SERPO collateral | Per-user balance increased correctly | Positive |
| TC-SC18 | Deduct fee from sufficient collateral balance | Balance decreased; fee split to vault and referrer | Positive |
| TC-SC19 | Deduct fee from insufficient collateral balance | Transaction rejected | Negative |
| TC-SC20 | Withdraw collateral as wallet owner | SERPO sent to wallet; balance decreased | Positive |
| TC-SC21 | Withdraw collateral as non-owner | Transaction rejected | Negative |
| TC-SC22 | Withdraw more than collateral balance | Transaction rejected | Negative |
| TC-SC23 | DEX trade with automatic fee deduction | 7% deducted on-chain; 4% vault, 3% referrer | Positive |
| TC-SC24 | Non-owner attempts fee deduction from collateral | Transaction rejected | Negative |

### 16.3 Backend Test Cases

| ID | Test Case | Expected Result |
|----|-----------|----------------|
| TC-BE01 | Query subscription status for active user | Returns active with correct expiry |
| TC-BE02 | Query subscription for expired user | Returns expired status |
| TC-BE03 | Query subscription for non-subscriber | Returns 'never' status |
| TC-BE04 | Subscription cache within 5 minutes | Returns cached result without blockchain query |
| TC-BE05 | Subscription cache expired (>5 min) | Fresh blockchain query executed |
| TC-BE06 | Generate referral link for connected user | Valid URL with wallet address |
| TC-BE07 | Existing bot commands work after integration | All 40+ commands respond correctly |
| TC-BE08 | New /subscribe command | Generates payment transaction correctly |
| TC-BE09 | New /referral command | Shows link and earnings |
| TC-BE10 | Trading profit fee calculation | Correct 7% fee computed |
| TC-BE11 | Collateral deposit creates correct on-chain balance | Deposit confirmed, balance updated |
| TC-BE12 | Collateral withdrawal returns SERPO to wallet | Balance decreased, tokens received |
| TC-BE13 | /collateral command shows correct balance | Balance matches on-chain state |
| TC-BE14 | Grid bot creation with valid parameters | Grid started on exchange |
| TC-BE15 | Grid bot executes buy/sell orders at grid levels | Orders placed at correct prices |
| TC-BE16 | Grid bot calculates profit and triggers fee deduction | Fee deducted from collateral |
| TC-BE17 | Grid bot AI signal pauses grid in trending market | Grid paused, no new orders |
| TC-BE18 | Grid bot adaptive spacing adjusts on volatility change | Grid levels recalculated |
| TC-BE19 | Grid bot backtest with historical data | Simulation results returned |
| TC-BE20 | Grid bot multi-market async execution | Multiple grids run simultaneously |

### 16.4 Test Environment

| Environment | Purpose | Network |
|-------------|---------|---------|
| Local | Unit tests, development | TON Emulator |
| Testnet | Integration & system tests | TON Testnet |
| Staging | UAT, performance tests | TON Testnet + staging server |
| Production | Live system | TON Mainnet |

---

## 17. Deployment Plan

### 17.1 Pre-Deployment Checklist

- [ ] All smart contracts pass 100% of test cases (including Collateral Vault)
- [ ] Security audit completed with no critical/high findings open
- [ ] Backend integration tests passing on staging
- [ ] Database migrations tested and verified
- [ ] All API endpoints documented and tested
- [ ] Telegram bot commands tested in staging bot
- [ ] Frontend flows tested across browsers (Chrome, Firefox, Safari)
- [ ] TON Connect tested with Tonkeeper and MyTonWallet
- [ ] Grid bot tested on all supported exchanges (crypto, forex, stocks)
- [ ] Grid bot backtested with historical data across all markets
- [ ] Collateral deposit/withdrawal/deduction flows verified end-to-end
- [ ] Monitoring dashboards configured (including grid bot P/L)
- [ ] Rollback plan documented and tested
- [ ] Incident response runbook created

### 17.2 Deployment Sequence

| Step | Action | Responsible | Rollback |
|------|--------|-------------|----------|
| 1 | Deploy smart contracts to TON Mainnet | Smart Contract Dev | N/A (immutable) |
| 2 | Verify contract source code on-chain | Smart Contract Dev | — |
| 3 | Run database migrations on production | Backend Dev | `php artisan migrate:rollback` |
| 4 | Deploy updated Laravel backend | DevOps | Revert to previous release tag |
| 5 | Update environment variables (contract addresses, API keys) | DevOps | Restore previous .env |
| 6 | Deploy frontend updates | Frontend Dev | Revert build |
| 7 | Update Telegram webhook | DevOps | Revert webhook URL |
| 8 | Smoke test all new features | QA | — |
| 9 | Deploy grid bot engine (Docker containers) | DevOps | Stop containers |
| 10 | Start crypto grid bots (limited users) | PM | Grid pause |
| 11 | Soft launch (10% user rollout) | PM | Feature flag disable |
| 12 | Monitor for 72 hours | All | — |
| 13 | Enable forex grid bots | PM | Grid pause |
| 14 | Enable stock grid bots | PM | Grid pause |
| 15 | Full public launch | PM | — |

### 17.3 Post-Deployment Monitoring

| Metric | Alert Threshold | Tool |
|--------|----------------|------|
| Subscription contract gas usage | >0.15 TON per transaction | TonAPI monitoring |
| Backend subscription check latency | >5 seconds P95 | Application monitoring |
| Failed Jetton transfers | >2% failure rate | Transaction monitoring |
| API error rate | >1% of requests | Server monitoring |
| Vault balance | Cross-verify with expected revenue | Manual daily check |
| Database response time | >500ms P95 | MySQL/PostgreSQL monitoring |

---

## 18. Maintenance & Support

### 18.1 Maintenance Categories

| Category | Description | Frequency |
|----------|-------------|-----------|
| **Corrective** | Bug fixes in backend or contract interaction | As needed |
| **Adaptive** | Updates for TON protocol changes, library upgrades | Quarterly |
| **Perfective** | Performance optimization, UX improvements | Monthly |
| **Preventive** | Security patching, dependency updates | Monthly |

### 18.2 Support Tiers

| Tier | Issue Type | Response Time | Resolution Time |
|------|-----------|---------------|-----------------|
| P1 — Critical | Smart contract exploit, vault compromise, total outage | 15 minutes | 4 hours |
| P2 — High | Subscription verification failure, payment failures | 1 hour | 8 hours |
| P3 — Medium | UI bugs, incorrect earnings display, slow performance | 4 hours | 48 hours |
| P4 — Low | Feature requests, cosmetic issues, documentation | 24 hours | Next sprint |

### 18.3 Operational Procedures

| Procedure | Description | Frequency |
|-----------|-------------|-----------|
| Vault balance reconciliation | Compare on-chain vault balance with expected revenue from subscriptions + fees | Daily |
| Subscription expiry audit | Verify backend cache matches on-chain state for random sample of users | Weekly |
| Security dependency scan | Check for CVEs in npm/composer dependencies | Weekly |
| Contract gas monitoring | Ensure gas costs remain within expected range | Weekly |
| Backup verification | Test database backup restoration | Monthly |
| Incident post-mortem | Document and learn from any production incidents | Per incident |

### 18.4 Contract Upgrade Strategy

Since TON smart contracts are immutable once deployed:

| Scenario | Strategy |
|----------|----------|
| Bug in contract logic | Deploy new contract; migrate users; update backend to point to new address |
| Price update needed | Use owner-only `updatePrice` message (built into contract) |
| Vault address change | Use owner-only `updateVault` message (built into contract) |
| Major feature addition | Deploy new version; run both contracts during migration period |
| Emergency shutdown | Owner sends pause message; backend falls back to cache |

---

## 19. Glossary

| Term | Definition |
|------|------------|
| **Jetton** | TON's fungible token standard (TEP-74), equivalent to ERC-20 on Ethereum |
| **Jetton Wallet** | A per-user contract that holds Jetton token balances on TON |
| **Jetton Master** | The main Jetton contract that manages token minting and metadata |
| **TON Connect** | Open protocol for connecting TON wallets to dApps |
| **Tact** | High-level programming language for TON smart contracts |
| **FunC** | Low-level programming language for TON smart contracts |
| **TVM** | TON Virtual Machine — executes smart contract code |
| **Gas** | Unit of computational cost for executing TON smart contracts |
| **Vault** | Smart contract that securely stores platform revenue |
| **Multisig** | Multi-signature wallet requiring multiple approvals for transactions |
| **DEX** | Decentralized Exchange — peer-to-peer token trading on-chain |
| **STON.fi** | Decentralized exchange on the TON blockchain |
| **DeDust** | Decentralized exchange on the TON blockchain |
| **SERPO** | SerpoCoin — the native utility token of the Serpo AI ecosystem |
| **Non-custodial** | Wallet design where the platform never holds user private keys |
| **TWAP** | Time-Weighted Average Price — smoothed price over a time period |
| **Webhook** | HTTP callback for receiving real-time event notifications |
| **ABI** | Application Binary Interface — contract method signature definitions |
| **Nanoton** | Smallest unit of TON (1 TON = 10^9 nanoton) |
| **UAT** | User Acceptance Testing |
| **SLA** | Service Level Agreement |
| **MVP** | Minimum Viable Product |

---

## 20. Appendices

### Appendix A: Existing SERPO Token Contract Details

| Property | Value |
|----------|-------|
| Contract Address | `EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw` |
| Standard | TEP-74 (TON Jetton) |
| Network | TON Mainnet |
| DEX Listings | DeDust, STON.fi |
| Token Symbol | SERPO |

### Appendix B: Existing Serpo AI Technical Stack

| Component | Technology | Status |
|-----------|------------|--------|
| Framework | Laravel 12 (PHP 8.2+) | Production |
| Database | PostgreSQL (primary) / MySQL 5.7+ (supported) | Production |
| AI Models | OpenAI GPT-4o-mini, Gemini, Groq | Active |
| Messaging | Telegram Bot API | Active |
| Blockchain Data | TonAPI v2 | Active |
| Market Data | Binance, CoinGecko, DexScreener | Active |
| Grid Bot Runtime | Python 3.12+ (grid engine, AI models) | Planned |
| Grid Bot Libraries | pandas, NumPy, TA-Lib, PyTorch/TensorFlow | Planned |
| Grid Bot Execution | Python asyncio, FastAPI, WebSocket streams | Planned |
| Backtesting | Backtrader, vectorbt | Planned |
| Hosting | VPS (ai.serpocoin.io) + Docker containers | Production |
| Bot Commands | 40+ commands | Active |
| Services | 40+ Laravel services | Active |
| Database Models | 20+ Eloquent models | Active |

### Appendix C: Subscription Contract Interface (Tact)

```tact
import "@stdlib/deploy";
import "@stdlib/ownable";

message Subscribe {
    referrer: Address?;
}

message UpdatePrice {
    new_price: Int as coins;
}

message UpdateVault {
    new_vault: Address;
}

message Withdraw {
    amount: Int as coins;
}

struct UserSubscription {
    expiry: Int as uint64;
    referrer: Address?;
}

contract SerpoSubscription with Deployable, Ownable {
    owner: Address;
    jetton_wallet: Address;      // Contract's own SERPO Jetton wallet
    subscription_price: Int;     // Price in nanoSERPO
    vault_wallet: Address;
    users: map<Address, UserSubscription>;

    init(owner: Address, jetton_wallet: Address, price: Int, vault: Address) {
        self.owner = owner;
        self.jetton_wallet = jetton_wallet;
        self.subscription_price = price;
        self.vault_wallet = vault;
    }

    // Main subscription handler — receives Jetton transfer notification
    receive(msg: JettonTransferNotification) {
        require(sender() == self.jetton_wallet, "Invalid Jetton source");
        require(msg.amount >= self.subscription_price, "Insufficient payment");
        
        let subscriber: Address = msg.sender;
        let referrer: Address? = self.parseReferrer(msg.forward_payload);
        
        // Prevent self-referral
        if (referrer != null) {
            require(referrer!! != subscriber, "Self-referral not allowed");
        }
        
        // Distribute payment
        if (referrer != null) {
            let half: Int = msg.amount / 2;
            self.sendJetton(referrer!!, half);
            self.sendJetton(self.vault_wallet, msg.amount - half);
        } else {
            self.sendJetton(self.vault_wallet, msg.amount);
        }
        
        // Update subscription
        let existing: UserSubscription? = self.users.get(subscriber);
        let new_expiry: Int;
        
        if (existing != null && existing!!.expiry > now()) {
            new_expiry = existing!!.expiry + 2592000; // +30 days
        } else {
            new_expiry = now() + 2592000;
        }
        
        self.users.set(subscriber, UserSubscription{
            expiry: new_expiry,
            referrer: referrer ?? existing?.referrer
        });
    }

    // Owner functions
    receive(msg: UpdatePrice) {
        self.requireOwner();
        self.subscription_price = msg.new_price;
    }

    receive(msg: UpdateVault) {
        self.requireOwner();
        self.vault_wallet = msg.new_vault;
    }

    // Getters
    get fun subscriptionOf(user: Address): UserSubscription? {
        return self.users.get(user);
    }

    get fun price(): Int {
        return self.subscription_price;
    }

    get fun vault(): Address {
        return self.vault_wallet;
    }
}
```

### Appendix D: System Interaction Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER JOURNEY                              │
│                                                                  │
│  1. Connect     2. Subscribe      3. Deposit     4. Trade       │
│     Wallet   →     with SERPO  →   Collateral →   with Bots     │
│                                                                  │
│  5. Earn Referrals    6. Monitor Grid Bot P/L                    │
│                                                                  │
│  ┌─────────┐   ┌──────────────┐   ┌──────────┐  ┌───────────┐  │
│  │Tonkeeper│   │ Pay 1000     │   │ /predict │  │ Share     │  │
│  │ Scan QR │   │ SERPO        │   │ /analyze │  │ Link      │  │
│  │         │   │              │   │ /scan    │  │ Earn 50%  │  │
│  └────┬────┘   └──────┬───────┘   │ /rsi     │  │ Per Sub   │  │
│       │               │           │ /grid    │  └───────────┘  │
│       ▼               ▼           │ /bots    │                  │
│  Wallet Address  Smart Contract   └──────────┘                  │
│  = User ID       Processes Payment                               │
│                  Splits Rewards                                   │
│                  Activates 30-Day Access                          │
│                  Manages Collateral                               │
└─────────────────────────────────────────────────────────────────┘
```

### Appendix E: Approval & Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Project Owner | | | |
| Technical Lead | | | |
| Smart Contract Lead | | | |
| Security Auditor | | | |
| QA Lead | | | |
| Product Manager | | | |

---

*This document is maintained by the SerpoAI Development Team. All changes must be approved through the document revision process and reflected in the revision history table.*

**Document End — SerpoAI Smart Contract System SRD v2.0**
