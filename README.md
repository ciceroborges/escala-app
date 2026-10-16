# Escala

Escala automática, repertório e mensagens do WhatsApp para ministérios de louvor. Instalável no celular (PWA).

## Rodando local

Precisa só de Docker.

```bash
cp .env.example .env
docker run --rm -v "$PWD":/var/www/html -w /var/www/html laravelsail/php84-composer composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

Abra http://localhost:8088 e use **Entrar sem Google** com `cicero@exemplo.com`.

## Login com Google

Crie um OAuth Client (tipo *Web*) no Google Cloud Console com o redirect
`http://localhost:8088/auth/google/callback` e preencha `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` no `.env`.

Mais detalhes em `docs/SCOPE.md` e `CLAUDE.md`.
