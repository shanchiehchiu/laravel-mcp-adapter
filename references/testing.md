# 測試方式

## 自動化測試（優先寫這個）

用 `Http::fake()` 隔離對外呼叫（如果你的 Tools 轉呼叫既有 REST API），
走一次 `initialize` → `tools/list` → `tools/call`，斷言回應內容跟
`Authorization` 檢查都對。範例骨架：

```php
class McpAdapterTest extends TestCase
{
    use DatabaseTransactions;

    private const TOKEN = 'test-token-0123456789abcdefghijklmnopqrstuv';

    protected function setUp(): void
    {
        parent::setUp();
        config(['mcp.inbound_key' => self::TOKEN]);
    }

    public function test_tools_list_discovers_registered_tools(): void
    {
        $sessionId = $this->initializeSession();

        $response = $this->callMcp($sessionId, 'tools/list', new \stdClass);

        $response->assertOk();
        $this->assertContains('ping', array_column($response->json('result.tools'), 'name'));
    }

    public function test_mcp_endpoint_rejects_wrong_inbound_key(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 0, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => new \stdClass, 'clientInfo' => ['name' => 'phpunit', 'version' => '1.0.0']],
        ], ['Authorization' => 'Bearer wrong-key']);

        $response->assertUnauthorized();
    }

    private function initializeSession(): string
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 0, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => new \stdClass, 'clientInfo' => ['name' => 'phpunit', 'version' => '1.0.0']],
        ], ['Authorization' => 'Bearer '.self::TOKEN]);

        $response->assertOk();

        return (string) $response->headers->get('Mcp-Session-Id');
    }

    private function callMcp(string $sessionId, string $method, mixed $params): TestResponse
    {
        return $this->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => random_int(1, 1_000_000), 'method' => $method, 'params' => $params,
        ], ['Mcp-Session-Id' => $sessionId, 'Authorization' => 'Bearer '.self::TOKEN]);
    }
}
```

務必也測「拒絕」情境：缺 Authorization header、金鑰錯誤、`MCP_REQUIRE_AUTH=false`
時允許放行、金鑰完全沒設定時回 503——這些都是 `McpApiKey` 的分支，改動它
時容易漏測某一支。

## 快速手動健檢（不用先接真資料）

```bash
curl http://your-app.test/mcp-health

curl -X POST http://your-app.test/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $MCP_API_KEY" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"curl-test","version":"1.0.0"}}}'
```

`initialize` 的回應 header 會帶一個 `Mcp-Session-Id`，後續 `tools/list`／
`tools/call` 要帶著這個值繼續呼叫。

## 除錯

`McpApiKey` 驗證失敗時會寫 `Log::warning`（訊息開頭固定 `MCP：`），不含
金鑰本身，只記 host／是否有帶 token；真的有 client 連不上時
`grep 'MCP' storage/logs/laravel.log` 或用 Boost 的 `read-log-entries`
查失敗原因。
