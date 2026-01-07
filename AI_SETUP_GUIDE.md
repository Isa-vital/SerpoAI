# 🤖 AI Provider Setup Guide

## Overview

SerpoAI now supports **multiple FREE AI providers** with automatic fallback:
1. **Google Gemini** (Primary) - 60 req/min, 1500/day FREE
2. **Groq** (Fallback) - 30 req/min, unlimited FREE
3. **OpenAI** (Final Fallback) - Limited free tier

---

## 🎯 Quick Setup (5 minutes)

### Step 1: Get Google Gemini API Key (FREE)

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Click "Get API Key"
3. Click "Create API Key in new project"
4. Copy the API key
5. Add to `.env`:
   ```env
   GEMINI_API_KEY=AIzaSy...your_key_here
   ```

**Limits**: 60 requests/minute, 1500 requests/day (FREE forever)

---

### Step 2: Get Groq API Key (FREE) - Optional but Recommended

1. Go to [Groq Console](https://console.groq.com)
2. Sign up/Login (GitHub OAuth available)
3. Go to API Keys section
4. Click "Create API Key"
5. Copy the key
6. Add to `.env`:
   ```env
   GROQ_API_KEY=gsk_
   ```

**Limits**: 30 requests/minute (FREE, no daily limit)

---

### Step 3: Restart Server

```bash
# Stop current server
Get-Process php | Where-Object {$_.Path -like '*xampp*'} | Stop-Process -Force

# Start server
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 🔄 How It Works

### Provider Priority Chain:

```
User Command
     ↓
[1] Try Gemini (60 req/min)
     ↓ (if fails)
[2] Try Groq (30 req/min)
     ↓ (if fails)
[3] Try OpenAI (limited)
     ↓ (if fails)
[4] Use Built-in Knowledge Base
```

### Caching Strategy:

- **`/explain` commands**: 24 hours cache (concepts don't change)
- **AI completions**: 24 hours cache
- **Built-in fallbacks**: Instant (20+ common terms)

---

## 💡 Features Using AI

### Commands Using AI Providers:

1. **`/explain [term]`** - Trading concept explanations
   - Cached: 24h
   - Fallback: Built-in knowledge base (20+ terms)

2. **`/ask [question]`** - Trading Q&A
   - Context-aware responses
   - Fallback: Generic helpful response

3. **`/predict [coin]`** - Price predictions
   - JSON-formatted predictions
   - Fallback: Technical indicator summary

4. **`/recommend`** - Personalized advice
   - User profile-based
   - Fallback: General trading tips

5. **`/query [question]`** - Natural language queries
   - Data-driven answers
   - Fallback: Direct data display

6. **`/sr [symbol]`** - Support/Resistance insights
   - AI interpretation of levels
   - Fallback: Level numbers only

7. **`/trendcoins`** - Market insights
   - AI trend analysis
   - Fallback: Raw data display

---

## 📊 Provider Comparison

| Feature | Gemini | Groq | OpenAI |
|---------|--------|------|--------|
| **Cost** | FREE | FREE | Paid/Limited |
| **Requests/min** | 60 | 30 | 3-5 (free) |
| **Daily Limit** | 1500 | Unlimited | Very limited |
| **Speed** | Fast | Very Fast | Medium |
| **Quality** | Excellent | Excellent | Excellent |
| **Reasoning** | ✅ Strong | ✅ Strong | ✅ Strong |
| **Technical Analysis** | ✅ Great | ✅ Good | ✅ Excellent |
| **Registration** | Google Account | Email/GitHub | Email + Phone |
| **Reliability** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

---

## 🚀 Recommended Setup

### For Development/Testing:
```env
GEMINI_API_KEY=your_key_here
GROQ_API_KEY=your_key_here
OPENAI_API_KEY=  # Can leave empty
```

### For Production (High Traffic):
```env
GEMINI_API_KEY=your_key_here        # Primary (1500/day free)
GROQ_API_KEY=your_key_here          # Fallback (unlimited free)
OPENAI_API_KEY=your_paid_key_here   # Emergency fallback
```

### For Low Budget:
```env
GEMINI_API_KEY=your_key_here   # Only Gemini needed
GROQ_API_KEY=                  # Optional
OPENAI_API_KEY=                # Not needed
```

---

## 🔍 Testing Your Setup

### Test AI Integration:

1. **Test Explain Command** (uses all providers):
   ```
   /explain RSI
   ```
   Expected: Detailed explanation of RSI

2. **Test Ask Command**:
   ```
   /ask What is the best indicator for day trading?
   ```
   Expected: Contextual answer

3. **Test Trend Insights**:
   ```
   /trendcoins
   ```
   Expected: Trending coins with AI insights

### Check Provider Status:

Check `storage/logs/laravel.log` for:
```
AI Provider: Google Gemini initialized
```
or
```
AI Provider: OpenAI initialized
```

---

## ⚠️ Important Notes

### Rate Limits:

**Gemini (Free Tier)**:
- 60 requests per minute
- 1500 requests per day
- Rate limit resets every minute

**Groq (Free Tier)**:
- 30 requests per minute
- No daily limit
- Very fast response times

**What Happens When Limit Hit**:
1. System automatically tries next provider
2. Falls back to built-in knowledge
3. User sees helpful response (no error)

### Caching Prevents Limits:

- `/explain` responses cached 24h
- Same question = instant cached response
- No API call needed for cached content

### Built-in Fallbacks:

Even without ANY API key, these work:
- `/explain RSI` (and 20+ other terms)
- `/scan`, `/analyze`, `/charts` (no AI needed)
- `/whale`, `/heatmap`, `/supercharts` (no AI needed)

---

## 🛠️ Troubleshooting

### Issue: "AI not configured" message

**Solution 1**: Check `.env` file has valid API key
```env
GEMINI_API_KEY=AIzaSy...   # Must start with AIzaSy
```

**Solution 2**: Restart server after adding key
```bash
php artisan config:clear
php artisan serve
```

### Issue: Rate limit errors

**Solution**: System handles automatically, but you can:
1. Add Groq API key as additional fallback
2. Increase cache time (already 24h)
3. Use multiple Gemini accounts (free)

### Issue: Slow responses

**Causes**:
- Gemini: 2-5 seconds (normal)
- Groq: 0.5-2 seconds (very fast)
- OpenAI: 3-8 seconds (normal)

**Solution**: Groq is fastest, prioritize it if speed critical

---

## 📈 Monitoring

### Check Provider Usage:

```bash
# Check logs for provider used
Get-Content storage/logs/laravel.log | Select-String "AI Provider"
```

### Check API Calls:

```bash
# Count AI calls today
Get-Content storage/logs/laravel.log | Select-String "Gemini generation" | Measure-Object
```

### Check Cache Hit Rate:

```bash
# Count cached responses
Get-Content storage/logs/laravel.log | Select-String "cache hit"
```

---

## 🎓 API Key Best Practices

### Security:

1. ✅ Keep keys in `.env` (never commit to git)
2. ✅ `.env` is in `.gitignore` (already set)
3. ✅ Use different keys for dev/production
4. ✅ Rotate keys periodically

### Optimization:

1. ✅ Aggressive caching (24h for static content)
2. ✅ Built-in fallbacks (no API needed)
3. ✅ Multiple providers (redundancy)
4. ✅ Lazy provider initialization

---

## 📚 External Resources

### Get API Keys:
- **Gemini**: https://makersuite.google.com/app/apikey
- **Groq**: https://console.groq.com
- **OpenAI**: https://platform.openai.com/api-keys

### Documentation:
- **Gemini API**: https://ai.google.dev/docs
- **Groq API**: https://console.groq.com/docs
- **Gemini PHP Client**: https://github.com/google-gemini-php/client

### Community:
- **Gemini Discord**: https://discord.gg/google-ai
- **Groq Discord**: https://discord.gg/groq

---

## 🔄 Migration Notes

### From OpenAI-Only to Multi-Provider:

**No Breaking Changes!**
- Existing OpenAI key still works
- Just add Gemini/Groq keys to upgrade
- Fallback chain handles everything

**What Changed:**
1. Provider priority: Gemini → Groq → OpenAI
2. Better caching (24h vs none)
3. Built-in fallbacks for common queries
4. Automatic failover between providers

**Testing Migration:**
1. Add Gemini key to `.env`
2. Restart server
3. Test `/explain RSI`
4. Check logs confirm "Gemini initialized"
5. Done! 🎉

---

## 💰 Cost Comparison

### Monthly Cost Estimates (1000 users, 50k requests):

| Setup | Gemini | Groq | OpenAI | Total |
|-------|--------|------|--------|-------|
| **Gemini Only** | $0 | - | - | **$0** |
| **Gemini + Groq** | $0 | $0 | - | **$0** |
| **All Three** | $0 | $0 | $15-30 | **$15-30** |
| **OpenAI Only** | - | - | $200-400 | **$200-400** |

### Recommendation:
**Use Gemini + Groq = $0/month** 💰

---

## ✅ Post-Setup Checklist

- [ ] Gemini API key added to `.env`
- [ ] Groq API key added to `.env` (optional)
- [ ] Server restarted
- [ ] `/explain RSI` tested (works)
- [ ] Logs show "Gemini initialized"
- [ ] No rate limit errors in logs
- [ ] Responses are fast (<5 seconds)

---

**Last Updated**: January 7, 2026  
**Status**: Production Ready ✅
