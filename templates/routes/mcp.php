<?php

use App\Http\Controllers\Api\McpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP Adapter（ChatGPT / Claude / Gemini / Grok 等 MCP client）
|--------------------------------------------------------------------------
|
| 獨立路由檔，刻意不吃 routes/api.php 的 /api 前綴，維持 MCP 規格要求的
| 單一端點 /mcp（要在 RouteServiceProvider 掛上 api middleware group：
| 無 CSRF、無 session，見 references/kernel-and-routing.md）。
|
| /mcp 用 any 放行所有 HTTP method，讓 StreamableHttpTransport 自己判斷
| 合法的 method/protocol；不要在這裡自行擋掉，否則 MCP 規格要求的握手/
| 工具呼叫/DELETE 拆 session 會被卡住。
|
| /mcp-health 是給人與監控用的純健康檢查，跟 /mcp 分開，永遠不驗證。
|
*/

Route::any('mcp', [McpController::class, 'handle'])->middleware('mcp.key')->name('mcp.handle');
Route::get('mcp-health', [McpController::class, 'health'])->name('mcp.health');
