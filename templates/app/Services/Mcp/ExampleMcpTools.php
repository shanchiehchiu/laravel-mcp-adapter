<?php

namespace App\Services\Mcp;

/**
 * 範例工具的實際邏輯。這支只回傳固定資料，證明 MCP 協定握手／工具呼叫
 * 沒有問題；接上真正資料時，工具方法直接呼叫你自己的 Eloquent Model／
 * Service 就好——回傳一個 array，mcp/sdk 會自動包成 MCP 規格要求的
 * content 格式，不用自己組 {"content": [...]} 這種信封。
 *
 * 如果你剛好已經有一組現成的 REST API 想直接轉呼叫（而不是重寫商業邏輯），
 * 可以在這一層改呼叫既有 API 的 HTTP client，模式一樣：方法簽章就是
 * 工具的輸入參數，回傳值就是工具的輸出。
 */
class ExampleMcpTools
{
    /**
     * @return array<string, mixed>
     */
    public function ping(?string $message = null): array
    {
        return ['pong' => $message ?? 'pong'];
    }
}
