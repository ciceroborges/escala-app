# Escala — contexto para Claude

App (PWA) para ministérios de louvor: escala mensal gerada automaticamente, repertório com tom por cantor
e mensagens prontas para o WhatsApp. Escopo e decisões de produto: `docs/SCOPE.md`.

## Stack

- Laravel 13 + PHP 8.5 (Sail/Docker) · MySQL 8.4 (banco próprio, local)
- Inertia v3 + Vue 3 (JS, `<script setup>`) · Tailwind 4 · Ziggy (`route()` no Vue)
- PWA: `public/manifest.webmanifest` + `public/sw.js` (escrito à mão, sem plugin)
- Visual baseado no projeto `alro` (TailAdmin): cor `brand` #465fff, cards brancos, dark mode por classe

## Comandos

```bash
./vendor/bin/sail up -d                      # app em http://localhost:8088
./vendor/bin/sail artisan migrate:fresh --seed   # recria o banco local com o ministério de exemplo
./vendor/bin/sail artisan test --compact
./vendor/bin/sail npm run dev                # Vite em :5174 (ou npm run build)
./vendor/bin/sail pint
```

Login local sem Google: tela de login → "Entrar sem Google" (`/dev/login`, só com `APP_ENV=local`).
Admin do seed: `cicero@exemplo.com`; membros: `nome@exemplo.com` (ex.: `lucimar@exemplo.com`).

## Convenções

- **Código e banco em inglês**; textos de tela, mensagens de validação e URLs em português.
- Camadas: `Controller → FormRequest → Service (IXxxService) → Repository (IXxxRepository) → Model`
  - Bindings de service em `AppServiceProvider`, de repository em `RepositoryServiceProvider`.
  - Interfaces sem construtor; implementações com constructor promotion `private readonly`.
  - Controller só HTTP. Montagem de props complexas fica em `app/Http/Presenters`.
- Ministério ativo: `App\Support\CurrentMinistry` (preenchido pelo middleware `ministry`). Todo acesso a dados
  passa pelo `ministry_id` atual — nunca buscar por id sem escopo de ministério.
- Permissões: Gates `admin`, `manage-schedule`, `manage-repertoire`, `view-full-schedule` (ver `Member::hasPermission`).
- **Activity log**: nenhuma ação sem autor. Models principais usam o trait `LogsActivity`; ações de negócio
  chamam `IActivityLogService::log()`. Sem usuário logado (console, seed, job) o autor é o usuário 1, Webmaster
  (`User::WEBMASTER_ID`, criado na migration de users).
- Gerador de escala (`app/Services/Scheduling`) é PHP puro, sem Eloquent — testar em `tests/Unit/Scheduling`.
- Mobile first: alvo de toque ≥ 44px (`min-h-11`), botões `w-full sm:w-auto`, formulários em `UiSheet`
  (bottom sheet no celular). Toda tela vazia ensina o próximo passo (`EmptyState`), dicas em `HelpTip`.
