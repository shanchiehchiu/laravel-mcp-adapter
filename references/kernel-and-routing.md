# 接線：middleware alias + 路由註冊

這兩處是既有檔案的局部修改，不是整支覆蓋，所以放參考片段而不是 templates。

## 1. `app/Http/Kernel.php`

在 middleware 別名陣列裡加一行。這個陣列在不同版本的 Laravel 10 骨架
裡名字不一樣：較新的 `composer create-project laravel/laravel` 產生的是
`$middlewareAliases`，較舊的專案（或手動升級上來的）可能還是
`$routeMiddleware`——兩個效果相同，看你的 `Kernel.php` 裡實際有哪一個
就加在那個裡面，不用兩個都加：

```php
protected $middlewareAliases = [
    // ...既有的都不動...
    'mcp.key' => \App\Http\Middleware\McpApiKey::class,
];
```

## 2. `app/Providers/RouteServiceProvider.php`

在 `boot()` 裡 `$this->routes(function () { ... })` 內加一個獨立的 group：

```php
// MCP Adapter：刻意不吃 /api 前綴，維持規格要求的單一端點 /mcp
Route::middleware('api')
    ->group(base_path('routes/mcp.php'));
```

**不要**掛在既有 `Route::middleware('api')->prefix('api')->group(...)` 那個
`/api` 前綴群組裡——MCP 規格要求單一端點 `/mcp`，掛法要獨立成自己的
group，即使一樣套用 `api` middleware。

## 3. 確認路由生效

```bash
php artisan route:list --path=mcp
```

應該看到 `/mcp`（任意 method）跟 `/mcp-health`（GET）兩條。
