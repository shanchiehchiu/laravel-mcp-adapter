<?php

namespace App\Services\Mcp;

use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;

/**
 * 範例：組裝 MCP Server、宣告有哪些工具。這支只是骨架驗證用的最小範例
 * （一個 ping 工具），先確認協定握手沒問題，再依你的資料模型設計真正的
 * 工具、重新命名這支類別（例如 InventoryMcpServerFactory）。
 *
 * 手動用 addTool() 逐一註冊（而非 attribute discovery），避免在容器裡對
 * 整個 app 目錄做檔案掃描，也不會有同一個 tool 被註冊兩次的疑慮。
 *
 * 加新工具時的兩個常見坑，見 references/writing-tools.md：
 * - 唯讀查詢工具建議都標 ToolAnnotations(readOnlyHint: true)
 * - optional 參數不要用反射預設產生的 "type": ["null", "string"]，
 *   自己寫成 anyOf（見下面 nullable() 這個 helper）
 */
class ExampleMcpServerFactory
{
    public function __construct(private readonly ExampleMcpTools $tools) {}

    public function build(): Server
    {
        $readOnly = new ToolAnnotations(readOnlyHint: true, destructiveHint: false, openWorldHint: false);

        return Server::builder()
            ->setServerInfo('範例 MCP Adapter', '1.0.0', '骨架驗證用，接上真正資料前先確認協定握手沒問題。')
            ->addTool(
                handler: [$this->tools, 'ping'],
                name: 'ping',
                description: '骨架驗證用：回傳固定字串，確認 MCP client 能正確呼叫到這支伺服器。',
                annotations: $readOnly,
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'message' => $this->nullable('string', '要回傳的訊息，不帶就用預設值'),
                    ],
                ],
            )
            ->setSession(sessionStore: new FileSessionStore(storage_path('app/mcp-sessions')))
            ->build();
    }

    /**
     * @return array<string, mixed>
     */
    private function nullable(string $type, string $description): array
    {
        return [
            'anyOf' => [['type' => 'null'], ['type' => $type]],
            'description' => $description,
            'default' => null,
        ];
    }
}
