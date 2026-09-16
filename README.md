# laravel-mcp-adapter

[English](./README.en.md) | [繁體中文](./README.md)

這是一個 [Claude Code Skill](https://docs.claude.com/en/docs/claude-code/skills)，
把 Laravel 應用包裝成 MCP（Model Context Protocol）Streamable HTTP
伺服器，讓 AI 助理（ChatGPT、Claude、Gemini、Grok……）透過單一 `/mcp`
端點呼叫你自訂的工具。

本 skill 只提供「協定轉接層 + 靜態 API 金鑰驗證」，不含 OAuth。如果目標
MCP client 的連接器介面**只有「Login with OAuth」按鈕、沒有能貼靜態金鑰
的欄位**，請在這個 skill 之上疊加使用搭配的 `laravel-mcp-oauth-adapter`
skill。

## 安裝

把這個 repo clone（或複製）進你 Laravel 專案的 `.claude/skills/` 目錄：

```bash
git clone <this-repo-url> .claude/skills/laravel-mcp-adapter
```

接著在該專案打開 Claude Code，請它幫你建立 MCP adapter——它會讀取
`SKILL.md` 並照步驟執行。

## 內容物

- `SKILL.md` — 主要的操作文件：安裝步驟、檔案複製對照表、建置順序、
  部署前檢查清單。
- `templates/` — 完整、可直接執行的 Laravel 檔案（config、middleware、
  controller、一組範例工具），複製進你的專案後依需求調整。
- `references/` — 更深入的細節：接線路由/middleware、如何寫自己的工具
  （包含 optional 參數的 schema 陷阱），以及測試方式。

## 驗證狀態

已透過從零開始建立一個全新 Laravel 10 專案、只依照本 repo 的說明操作，
完整驗證過一輪——確認真的接上資料庫的工具能透過
`initialize` → `tools/list` → `tools/call` 正常運作。

## 授權

MIT — 詳見 [LICENSE](./LICENSE)。
