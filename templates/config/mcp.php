<?php

/*
|--------------------------------------------------------------------------
| MCP Adapter 設定
|--------------------------------------------------------------------------
|
| 這裡只放跟「/mcp 進站驗證」與「DNS rebinding 防護」有關的鍵。
|
| 如果你的架構選擇「MCP 轉呼叫既有 REST API」（而不是讓 Tools 直接呼叫
| Eloquent/Service），自己另外加 api_base_url／api_token 這類鍵，跟這份
| 骨架無關，見 references/writing-tools.md。
|
*/

return [

    // MCP client（例如 ChatGPT 連接器）打 /mcp 本身要帶的靜態金鑰，見
    // App\Http\Middleware\McpApiKey。逗號分隔可以放多把（例如新舊金鑰輪替期間）。
    'inbound_key' => env('MCP_API_KEY', ''),

    // 靜態金鑰最短長度，太短的字串不會被當成合法金鑰接受，避免不小心把空
    // 字串或誤植的短字串當成「已設定」。
    'min_static_key_length' => 32,

    // 預設一定要驗證；只有連接器的設定畫面完全沒有可用的驗證欄位時，才手動
    // 關閉成 false。關掉後 /mcp 對外完全不驗證身分，請自行評估風險。
    'require_auth' => (bool) env('MCP_REQUIRE_AUTH', true),

    // mcp/sdk 內建 DNS rebinding 防護，只允許 Host/Origin 落在這份清單裡的
    // 請求進來，預設只有 localhost 系列。正式網域一定要填進
    // MCP_ALLOWED_HOSTS（逗號分隔，只填主機名稱，不含 scheme/port），
    // 否則所有外部請求都會被 403。
    'allowed_hosts' => array_values(array_unique(array_filter(array_merge(
        ['localhost', '127.0.0.1', '[::1]'],
        array_map('strtolower', array_filter(array_map('trim', explode(',', (string) env('MCP_ALLOWED_HOSTS', ''))))),
    )))),
];
