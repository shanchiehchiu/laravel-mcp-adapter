<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mcp\ExampleMcpServerFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * MCP client（ChatGPT、Claude、Gemini、Grok 等）→ /mcp 的薄轉接層。
 *
 * 只負責把 Laravel Request 轉成 PSR-7、交給官方 mcp/sdk 處理協定，再把
 * PSR-7 Response 轉回 Laravel 能回傳的型別；實際查詢邏輯全部在
 * App\Services\Mcp\*ServerFactory／*Tools。把建構子型別、下面的
 * ExampleMcpServerFactory 換成你自己寫的 Factory 類別即可，這支不用改。
 */
class McpController extends Controller
{
    /** ANY /mcp —— 交給 mcp/sdk 的 StreamableHttpTransport 處理 MCP 協定的握手、工具呼叫與 session 生命週期 */
    public function handle(Request $request, ExampleMcpServerFactory $factory): Response
    {
        $psrRequest = (new PsrHttpFactory)->createRequest($request);

        // mcp/sdk 預設的 DNS rebinding 防護只放行 localhost 系列 Host/Origin，
        // 部署在真實網域（config('mcp.allowed_hosts')，見 config/mcp.php）時
        // 要換掉這個 middleware，否則正式網域打進來的請求全部會被拒絕。
        $transport = new StreamableHttpTransport($psrRequest, middleware: [
            new CorsMiddleware,
            new DnsRebindingProtectionMiddleware((array) config('mcp.allowed_hosts')),
        ]);

        $psrResponse = $factory->build()->run($transport);

        return (new HttpFoundationFactory)->createResponse($psrResponse);
    }

    /** GET /mcp-health —— 給人與監控看的健康檢查，不連 DB、不洩漏任何設定 */
    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'laravel-mcp-adapter',
            'version' => '1.0.0',
        ]);
    }
}
