# laravel-mcp-adapter

[English](./README.en.md) | [繁體中文](./README.md)

A [Claude Code Skill](https://docs.claude.com/en/docs/claude-code/skills) that
wraps a Laravel application as an MCP (Model Context Protocol) Streamable
HTTP server, so AI assistants (ChatGPT, Claude, Gemini, Grok, ...) can call
your custom tools through a single `/mcp` endpoint.

This skill provides the protocol adapter layer plus static-API-key auth only
— no OAuth. If the target MCP client only offers a "Login with OAuth" button
with no field to paste a static key, layer the companion `laravel-mcp-oauth-adapter`
skill on top of this one.

## Install

Clone (or copy) this repo into your Laravel project's `.claude/skills/`
directory:

```bash
git clone <this-repo-url> .claude/skills/laravel-mcp-adapter
```

Then open Claude Code in that project and ask it to build the MCP adapter —
it will read `SKILL.md` and follow the steps.

## What's inside

- `SKILL.md` — the orchestration doc: install steps, file-copy table, build
  order, deployment checklist.
- `templates/` — complete, executable Laravel files (config, middleware,
  controller, an example tool factory) to copy into your app and adapt.
- `references/` — deeper detail on wiring routes/middleware, writing your
  own tools (including a schema gotcha for optional parameters), and testing.

## Status

Validated end-to-end by scaffolding a brand-new Laravel 10 app from scratch,
following only this repo's instructions, and confirming real database-backed
tools work over `initialize` → `tools/list` → `tools/call`.

## License

MIT — see [LICENSE](./LICENSE).
