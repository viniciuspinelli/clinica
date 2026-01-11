<!-- Copilot instructions for contributors and AI agents -->
# Instruções rápidas para agentes de código (Perplexity)

Objetivo: ajudar um agente a trabalhar imediatamente com a integração Perplexity neste repositório.

- **Arquitetura (big picture):** este é um projeto de extensão do VS Code que integra a API de chat da Perplexity.
  - A extensão monta uma UI Webview (`src/ui/webviewContent.ts`, `src/ui/webviewSidebar.ts`) e comandos registrados em `src/extension.ts`.
  - Comunicação com a API Perplexity é centralizada em `src/util/perplexity.ts` (há um duplicado em `perplexity-ext/src/util/perplexity.ts`).
  - Modelos disponíveis vêm de `src/util/models.ts` e são exibidos no sidebar.

- **Padrões de integração Perplexity (essenciais):**
  - Endpoint usado: `POST https://api.perplexity.ai/chat/completions` (veja `src/util/perplexity.ts`).
  - Corpo da requisição: objeto `PerplexityRequest` com campos `model`, `messages` (array de `{role, content}`), `stream: true` para streaming.
  - Streaming: a função lê `response.body.getReader()` e processa linhas `data: ...` em loop; os eventos enviados à webview são:
    - `stream`: pedaços de texto em tempo real
    - `source`: itens de citação/fonte (campo `citations` no response)
    - `complete`: stream finalizado
    - `error`: erros formatados para a webview
  - Mensagens e formato de contexto: `PerplexityMessage` = `{ role: string, content: string }`.

- **Chaves/segredos e comandos (onde mexer):**
  - A chave de API é salva em `context.secrets` com a chave `perplexity-ext.apiKey` (veja `src/extension.ts`).
  - Comandos registrados (package.json):
    - `perplexity-ext.setAPIToken` — abrir prompt para armazenar a chave
    - `perplexity-ext.openChatWindow` — abrir a UI de chat

- **Fluxos de trabalho úteis (build / testes / debug):**
  - Compilar (build): `npm run compile` — executa checagem de tipos, lint e `esbuild`.
  - Dev/watch: `npm run watch` ou `npm run watch:esbuild` + `npm run watch:tsc`.
  - Testes: `npm test` (usa `vscode-test`); ver scripts em `package.json`.
  - Empacotar/publicar: `npm run package` e `npm run deploy` (usam `vsce`).

- **Convensões e observações específicas do projeto:**
  - Há duas cópias muito semelhantes da extensão: uma raiz (em `[/src/...` no workspace) e outra em `perplexity-ext/`. Verifique qual é usada pelo CI/publish antes de alterar apenas um local.
  - A UI comunica-se com a extensão via `postMessage` e com o util Perplexity através do handler em `src/extension.ts`. Quando alterar a webview, atualizar também as rotas de mensagem no `extension.ts`.
  - Streaming é tratado manualmente com `ReadableStream` — não esperar um objeto JSON único; parse linha a linha (prefixo `data: `).

- **Exemplos práticos (copiados do código):**
  - Request body (exemplo):

```json
{
  "model": "perplexity-xxx",
  "messages": [{"role":"system","content":"..."}, {"role":"user","content":"..."}],
  "stream": true
}
```

  - Mensagens enviadas à webview (padrões): `{ command: 'stream'|'source'|'complete'|'error', content: ... }`.

- **Onde procurar para mudanças relacionadas:**
  - Lógica de envio/stream: `src/util/perplexity.ts` and `perplexity-ext/src/util/perplexity.ts`.
  - Registro de comandos / secret storage: `src/extension.ts`.
  - UI e eventos: `src/ui/webviewContent.ts`, `src/ui/webviewSidebar.ts`.
  - Modelos disponíveis: `src/util/models.ts`.

Se algo estiver incompleto ou você quiser que eu inclua exemplos de mensagens mais detalhados, diga qual parte prefere que eu expanda.
