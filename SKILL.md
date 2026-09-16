---
name: laravel-mcp-adapter
description: 在 Laravel 專案建立一個薄的 MCP（Model Context Protocol）Streamable HTTP 轉接層，讓 ChatGPT／Claude／Gemini／Grok 等 AI 助理透過單一 /mcp 端點呼叫自訂工具。只做骨架＋靜態金鑰驗證；需要「跳出瀏覽器登入頁」那種 OAuth 2.1 連接器時，另外套用 laravel-mcp-oauth-adapter skill。
---

# Laravel MCP Adapter（骨架）

把 Laravel 應用包成一個 MCP server，讓 AI 助理能呼叫你定義的工具、讀取資料。
本 skill 只做「協定轉接層 + 靜態金鑰驗證」，不含 OAuth；OAuth 是獨立的
`laravel-mcp-oauth-adapter` skill，兩者可以疊加。

## 背景知識（不熟 MCP 的話先看這段）

MCP（Model Context Protocol）是 Anthropic 開源的協議規格，底層是
JSON-RPC 2.0：client 送 `method`+`params`，server 回 `result`/`error`。
伺服器可以宣告支援三種「原語」——**Tools**（可呼叫的函式，本 skill 只做
這個）、Resources（唯讀資料）、Prompts（範本）。連線走 **Streamable HTTP**
（單一端點處理所有呼叫，適合遠端雲端服務連線，跟本機 stdio 不同），基本
流程是 `initialize`（交換版本/能力）→ `tools/list`（列出工具）→
`tools/call`（實際呼叫）。**MCP 協定本身不規範身分驗證**，這是這個 skill
（靜態金鑰）跟 `laravel-mcp-oauth-adapter`（OAuth 2.1）在補的部分。

## 步驟

### 1. 安裝

```bash
composer require mcp/sdk:^0.8.1 symfony/psr-http-message-bridge:^7.4 php-http/discovery guzzlehttp/psr7
```

`McpController` 用 `symfony/psr-http-message-bridge` 的 `PsrHttpFactory`
把 Laravel Request 轉成 PSR-7 物件。**版本一定要 `^7.4` 以上**：這個版本的
`PsrHttpFactory` 建構子參數全部有預設值 `null`，缺參數時會自動用
`php-http/discovery` 探測專案裡裝了哪個 PSR-17 實作（`guzzlehttp/psr7`
或 `nyholm/psr7`）來補上；舊版（例如自動解析出來的 `^2.0`）建構子**沒有
預設值**，`new PsrHttpFactory` 不帶參數會直接丟
`ArgumentCountError`，而且錯誤訊息完全看不出跟這個套件版本有關，很難
排查。`php-http/discovery` 和 `guzzlehttp/psr7` 兩個套件是讓自動探測
「找得到東西可以用」的必要條件，即使專案本身還沒透過其他套件（例如
Guzzle）間接裝進來，也要明確裝上。

### 2. 複製骨架檔案

把 `templates/` 底下的檔案複製到專案對應路徑（去掉 `templates/` 這層）：

| 來源 | 目的地 |
|---|---|
| `templates/config/mcp.php` | `config/mcp.php` |
| `templates/app/Http/Middleware/McpApiKey.php` | `app/Http/Middleware/McpApiKey.php` |
| `templates/app/Http/Controllers/Api/McpController.php` | `app/Http/Controllers/Api/McpController.php` |
| `templates/app/Services/Mcp/ExampleMcpServerFactory.php` | `app/Services/Mcp/ExampleMcpServerFactory.php`（之後重新命名，見步驟 4） |
| `templates/app/Services/Mcp/ExampleMcpTools.php` | `app/Services/Mcp/ExampleMcpTools.php`（之後重新命名） |
| `templates/routes/mcp.php` | `routes/mcp.php` |

這些檔案都是可以直接執行的完整版本，`.env` 補上 `MCP_API_KEY=<32字元以上
隨機字串>` 之後就能跑。

### 3. 接線（middleware alias + 路由註冊）

照 [references/kernel-and-routing.md](./references/kernel-and-routing.md)，
改 `app/Http/Kernel.php` 加一行 middleware alias、改
`app/Providers/RouteServiceProvider.php` 掛一個獨立的路由 group。

**不要**把 `routes/mcp.php` 掛進既有 `routes/api.php` 的 `/api` 前綴群組——
MCP 規格要求單一端點 `/mcp`，掛法要獨立。

### 4. 確認骨架能動

```bash
php artisan route:list --path=mcp
curl http://your-app.test/mcp-health
```

再照 [references/testing.md](./references/testing.md) 走一次
`initialize`→`tools/list`，確認能看到範例的 `ping` 工具。**先確認這一步
沒問題再進到下一步**，握手都跑不動的話，接上真資料只會更難除錯。

### 5. 寫你自己的 Tools

`ExampleMcpServerFactory`/`ExampleMcpTools` 只是骨架驗證用的最小範例。
接上真正資料前，讀 [references/writing-tools.md](./references/writing-tools.md)——
裡面有兩個容易漏掉的坑（optional 參數的 inputSchema 陷阱、兩種可能的
架構選擇），照著改完，把類別重新命名成你專案的名字並更新
`McpController` 裡的型別提示。

### 6. 補測試

照 [references/testing.md](./references/testing.md) 的骨架寫 feature
test，涵蓋：正常呼叫、缺金鑰、金鑰錯誤、`MCP_REQUIRE_AUTH=false` 放行、
金鑰完全沒設定回 503。

### 7. 需要 OAuth 登入頁的話

如果目標 MCP client（例如 ChatGPT／Claude／Gemini／Grok 的連接器）介面
**只有「Login with OAuth」按鈕、沒有能貼靜態金鑰的欄位**，套用
`laravel-mcp-oauth-adapter` skill——它會在這個骨架之上疊加 OAuth 2.1 授權碼＋
PKCE 流程，`config/mcp.php`、`McpApiKey.php`、`routes/mcp.php` 這三支會
被換成含 OAuth 分支的完整版本（取代，不是合併）。

### 8. 部署前檢查清單

- [ ] `MCP_ALLOWED_HOSTS` 填正式網域（逗號分隔，只填主機名稱）
- [ ] `MCP_API_KEY` 已設定且 ≥ 32 字元
- [ ] `php artisan config:clear`（如果曾經跑過 `config:cache`，改 `.env`
      不會馬上生效）
