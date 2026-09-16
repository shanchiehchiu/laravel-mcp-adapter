<?php

namespace App\Services\Mcp;

use App\Models\ExampleItem;

/**
 * 範例工具的實際邏輯，示範兩種情境：
 *
 * - `ping()`：只回傳固定資料，證明 MCP 協定握手／工具呼叫沒有問題，跟
 *   資料庫無關，最先拿來確認骨架能動（見 SKILL.md 步驟 4）。
 * - `listExampleItems()`：真的查 `App\Models\ExampleItem` 這個 Eloquent
 *   Model，示範「Tools 直接呼叫 Eloquent」這個架構選擇（見
 *   references/writing-tools.md 架構選擇 1）——工具方法直接
 *   `Model::query()->...`，回傳一個 array，mcp/sdk 會自動包成 MCP 規格
 *   要求的 content 格式，不用自己組 {"content": [...]} 這種信封。接上真正
 *   資料時，把這支方法裡的 Model 換成你自己的即可，其餘寫法照抄。
 *
 * 如果你剛好已經有一組現成的 REST API 想直接轉呼叫（而不是重寫商業邏輯），
 * 可以在這一層改呼叫既有 API 的 HTTP client，模式一樣：方法簽章就是
 * 工具的輸入參數，回傳值就是工具的輸出（見 writing-tools.md 架構選擇 2）。
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listExampleItems(?string $status = null): array
    {
        $query = ExampleItem::query();

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('id')->get(['id', 'title', 'status'])->toArray();
    }
}
