<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * /mcp 端點本身的用戶端驗證（靜態金鑰版本，沒有 OAuth）。
 *
 * 沒有這層的話，只要知道網址、Host 又通過 DnsRebindingProtectionMiddleware
 * 的允許清單，任何人都能呼叫這裡註冊的工具。
 *
 * /mcp-health 不掛這支 middleware，健康檢查要保持公開可測。
 *
 * 要加 OAuth 2.1 access token 驗證（給只支援「Login with OAuth」的連接器
 * 用），套用 laravel-mcp-oauth-adapter skill，那個 skill 會把這支檔案換成含 OAuth
 * 分支的版本，其餘邏輯不變。
 *
 * MCP_REQUIRE_AUTH=false 可以整段跳過（配合部分 MCP client 的連接器設定
 * 完全沒有可用的驗證欄位的情況）。關掉之後 /mcp 對外完全不驗證，任何知道
 * 網址（且 Host 通過 DnsRebindingProtectionMiddleware 允許清單）的人都能
 * 呼叫查詢工具，預設一定要是 true，只有明確知道風險才手動關閉。
 */
class McpApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('mcp.require_auth', true)) {
            return $next($request);
        }

        $given = (string) $request->bearerToken();

        if ($given !== '' && $this->matchesStaticKey($given)) {
            return $next($request);
        }

        if ($this->staticKeys() === []) {
            Log::warning('MCP：尚未設定任何驗證方式', ['host' => $request->getHost()]);

            return $this->error(503, 'MCP 尚未啟用：伺服器 .env 需設定 MCP_API_KEY。');
        }

        // 不記 $given 本身：不管金鑰對不對，都是機密值，記了等於把憑證明碼
        // 寫進 log 檔；bearer_token_present 已經足夠判斷是「完全沒帶」還是
        // 「帶了但不對」。
        Log::warning('MCP：驗證失敗', [
            'host' => $request->getHost(),
            'bearer_token_present' => $given !== '',
        ]);

        return $this->error(401, 'API 金鑰錯誤。');
    }

    private function matchesStaticKey(string $given): bool
    {
        foreach ($this->staticKeys() as $key) {
            if (hash_equals($key, $given)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function staticKeys(): array
    {
        $minLength = (int) config('mcp.min_static_key_length', 32);

        return array_values(array_filter(
            array_map('trim', explode(',', (string) config('mcp.inbound_key', ''))),
            fn (string $key) => strlen($key) >= $minLength
        ));
    }

    private function error(int $status, string $message): Response
    {
        return response()->json(['error' => $message], $status, [], JSON_UNESCAPED_UNICODE);
    }
}
