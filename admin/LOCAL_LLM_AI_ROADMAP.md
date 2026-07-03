# PAICAFE Admin LocalLLM AI Roadmap

## Goal

Build AI features for `public/admin/` that work with a local model first, protect cafe/customer data, and help staff make faster decisions without depending on paid external APIs.

The existing `prompt_generator.php` should become the first UI surface of a larger Admin AI layer.

## LocalLLM Method

Use a local inference service, preferably Ollama or another OpenAI-compatible local server.

Recommended local endpoints:

- Ollama: `http://127.0.0.1:11434/api/chat`
- OpenAI-compatible local server: `http://127.0.0.1:8080/v1/chat/completions`

Recommended models:

- `llama3.1:8b` for general admin writing and summaries.
- `qwen2.5:7b-instruct` for structured analysis and multilingual work.
- `mistral:7b` for fast lightweight summaries.
- A larger model can be added later for offline batch jobs.

Keep model calls server-side only. The browser should call Paicafe PHP endpoints, and PHP should call the local LLM.

## Core Architecture

Add a small local AI service layer:

```text
public/admin/ai.php
  UI dashboard for AI tools

public/admin/api/ai_chat.php
  Receives admin request, checks permission, validates CSRF, calls LocalLLM

public/includes/ai_local.php
  Shared LocalLLM client, prompt templates, JSON helpers, safety limits

database table: ai_activity_logs
  Stores admin_id, tool_name, input_summary, output_summary, created_at
```

Suggested PHP helper functions:

```php
local_ai_chat($messages, $options = [])
local_ai_json($messages, $schema_hint, $options = [])
local_ai_available()
log_ai_activity($pdo, $tool, $input_summary, $output_summary)
```

## Admin AI Features

### 1. Product Content Assistant

Use product name, price, category, description, discount, image URL, and video URL.

Outputs:

- Public menu description.
- Social media caption.
- Short POS display name.
- SEO keywords.
- Myanmar and English translation.
- Allergy/ingredient warning draft, if recipe data exists.

Best location:

- Extend `prompt_generator.php`.
- Add “Generate Draft” button next to “Copy Prompt”.

### 2. Review Reply Assistant

Existing reviews already link into `prompt_generator.php?review_id=...`.

Outputs:

- Warm reply to good review.
- Apology and recovery reply to bad review.
- Internal action summary for staff.
- Suggested coupon/reward only when rating is low and admin confirms.

Rules:

- Never promise refunds automatically.
- Never reveal internal notes.
- Always keep a human approval step.

### 3. Daily Manager Summary

Generate a daily briefing from:

- Orders by status.
- Revenue and expenses.
- Top products.
- Low-stock ingredients.
- Pending reservations.
- Recent reviews.

Output:

- “What happened today”
- “Needs attention”
- “Suggested actions”
- “Tomorrow prep checklist”

Best location:

- Add card to `index.php` dashboard.
- Add detail page `ai_daily_summary.php`.

### 4. Inventory Advisor

Use inventory quantities, thresholds, recipes, and product sales.

Outputs:

- Low-stock explanation.
- Restock priorities.
- Estimated days remaining.
- Suggested purchase list.
- Waste risk warnings.

Best location:

- `inventory.php`
- `daily_use_stock.php`
- `inventory_logs.php`

Important:

- AI should advise only. It must not update stock automatically.

### 5. Sales Insight Assistant

Use `reports.php` data and product profitability.

Outputs:

- Revenue summary.
- Expense explanation.
- Profit risk notes.
- Product winners and weak sellers.
- Promotion ideas for high-margin items.

Best location:

- Add AI insight panel in `reports.php`.

### 6. Reservation And Table Assistant

Use reservations, table status, and floor plan.

Outputs:

- Upcoming table pressure summary.
- Suggested table preparation order.
- Delay-risk notes.
- Staff-facing guest summary.

Best location:

- `reservations.php`
- `floor_plan.php`

### 7. Admin Support Chat

A local admin helper that answers questions like:

- “Which products are low margin?”
- “What should I restock today?”
- “Write a Facebook post for today’s specials.”
- “Summarize bad reviews this week.”

Implementation:

- Start with read-only SQL-backed context blocks.
- Do not let AI run arbitrary SQL.
- Use predefined data fetchers only.

## Prompt Pattern

Use a strict system prompt:

```text
You are PAICAFE Admin Assistant.
Use only the provided context.
If data is missing, say what is missing.
Do not invent prices, stock, revenue, policies, refunds, or customer details.
Return concise, staff-friendly output.
For risky actions, recommend human confirmation.
```

Use structured context:

```text
CONTEXT:
- Date range:
- Products:
- Orders:
- Inventory:
- Reviews:
- Reservations:

TASK:
...

OUTPUT FORMAT:
1. Summary
2. Risks
3. Suggested Actions
```

## Security And Privacy Rules

- Admin login required for every AI endpoint.
- Permission required, for example `use_ai_tools`.
- CSRF required for all POST AI requests.
- Never expose DB credentials or server paths.
- Do not send customer phone/address to external APIs.
- For LocalLLM, still minimize customer data in prompts.
- Log AI usage, but store summaries instead of full sensitive prompts.
- Human approval required before changing products, stock, coupons, orders, rewards, or replies.

## Suggested Database Table

```sql
CREATE TABLE ai_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    tool_name VARCHAR(100) NOT NULL,
    input_summary TEXT NULL,
    output_summary TEXT NULL,
    model_name VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (admin_id),
    INDEX (tool_name),
    INDEX (created_at)
);
```

## Implementation Phases

### Phase 1: Foundation

- Add `public/includes/ai_local.php`.
- Add LocalLLM config in `settings`.
- Add `local_ai_available()` health check.
- Add `public/admin/api/ai_chat.php`.
- Add permission `use_ai_tools`.

### Phase 2: Upgrade Prompt Engine

- Keep the existing prompt-only workflow.
- Add optional LocalLLM generation.
- Add “Copy”, “Download”, and “Save Draft”.
- Add review reply assistant.

### Phase 3: Dashboard AI Summaries

- Daily manager summary.
- Low-stock summary.
- Review sentiment summary.
- Sales highlights.

### Phase 4: AI Panels Across Admin

- `reports.php`: financial insights.
- `inventory.php`: restock advice.
- `reservations.php`: table pressure summary.
- `products.php`: content and pricing suggestions.

### Phase 5: Human-Approved Actions

Add draft actions, never direct execution:

- Draft product description update.
- Draft review reply.
- Draft coupon idea.
- Draft restock list.

Admin must click “Apply” or “Send”.

## Best First Feature To Build

Build “AI Daily Manager Summary” first.

Why:

- High value for admin.
- Read-only.
- Low risk.
- Uses data already available.
- Easy to verify manually.

Minimum version:

```text
Button: Generate Today Summary
Inputs: orders, revenue, expenses, low stock, reservations, reviews
Output: summary, risks, next actions
```

## UI Direction

Use the existing Liquid Glass V2 admin style:

- AI panels should be glass cards.
- Use clear status chips: Local Model Online, Offline, Draft Only.
- Use icon buttons for copy, regenerate, download, save.
- Show input data sources before generation.
- Show “Human approval required” on any generated action.
- Keep generated text in editable textareas, not static blocks.

## LocalLLM Failure Handling

When local AI is unavailable:

- Show “Local AI is offline”.
- Keep prompt generation working.
- Offer copy/download prompt for manual use.
- Never break the admin page.

## Future Ideas

- Voice-style daily briefing text.
- Auto-generate Myanmar translations for menu items.
- Detect duplicate product descriptions.
- Suggest better category grouping.
- Summarize customer review sentiment by product.
- Recommend bundles based on products often ordered together.
- Detect suspicious coupon usage patterns.
- Forecast ingredients needed for tomorrow.
- Staff training assistant using internal workflows.

