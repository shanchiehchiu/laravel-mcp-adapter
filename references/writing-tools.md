# 寫你自己的 Tools

`templates/app/Services/Mcp/ExampleMcp{ServerFactory,Tools}.php` 只是骨架
驗證用的最小範例（一個 `ping` 工具）。接上真正資料時，這兩支要重寫成你
專案自己的版本，過程中有幾個容易漏掉的地方：

## 1. 命名

把 `ExampleMcpServerFactory`／`ExampleMcpTools` 換成你專案的名字（例如
`InventoryMcpServerFactory`／`InventoryMcpTools`），並同步把
`app/Http/Controllers/Api/McpController.php` 裡的型別提示換掉。

## 2. 兩種可能的架構

- **Tools 直接呼叫 Eloquent/Service**：多數新專案適用，工具方法裡直接
  `Model::query()->...`，不需要額外的 HTTP client 層。
- **Tools 轉呼叫既有 REST API**：如果專案已經有一組成熟的 REST API
  （例如給其他前端用的），可以讓工具方法把參數轉成 query string 呼叫
  過去，不重寫商業邏輯。這個模式需要多一層薄的 HTTP client 類別
  （建構子注入 base URL／token），工具方法本身只做參數轉換跟原樣回傳
  JSON，不做商業邏輯判斷。

兩種都一樣：**工具方法的回傳值直接是一個 array**，`addTool()` 底層的
`mcp/sdk` 會自動包成 MCP 規格要求的 `content` 格式，不用自己組
`{"content": [{"type": "text", "text": "..."}]}` 這種信封。

## 3. optional 參數的 inputSchema 陷阱

`addTool()` 沒有明確指定 `inputSchema` 時，SDK 會用反射從方法簽章自動
產生一份；但對 `?string $foo = null` 這種可為 null 的參數，反射預設會
產生 `"type": ["null", "string"]`（陣列型 type）。**這是合法 JSON Schema，
但不少 MCP client（含 ChatGPT 較嚴格的 schema 驗證）會拒絕整個工具，
或無聲丟掉這個限制。**

改成手寫 `inputSchema`，optional 參數用 `anyOf` 表示：

```php
'foo' => [
    'anyOf' => [['type' => 'null'], ['type' => 'string']],
    'description' => '...',
    'default' => null,
],
```

`ExampleMcpServerFactory::nullable()` 這個 private helper 就是做這件事，
沿用即可。

## 4. 唯讀 vs 有副作用的工具

`ToolAnnotations(readOnlyHint: true, destructiveHint: false, openWorldHint: false)`
是給查詢類工具用的提示（讓 MCP client 知道呼叫這個工具不會產生副作用，
某些 client 會因此放寬確認流程）。如果工具會寫入資料，**不要**標
`readOnlyHint: true`，也考慮要不要在工具內部再加一層業務層的權限檢查——
MCP 協定本身不管授權細節，`readOnlyHint` 只是提示，不是強制擋下寫入的
機制。

## 5. 參數命名

MCP 的工具輸入 schema 是直接從 PHP 方法簽章反射出來的，方法參數名就是
使用者/AI 看到的欄位名。如果你打算讓既有的 Custom GPT Actions（或其他
還在用同一組欄位命名的系統）跟這個 MCP Adapter 並存，兩邊的欄位命名
（例如 `date_from`/`date_to` 用 snake_case 還是 camelCase）建議保持一致，
減少維護時要記兩套命名的負擔。
