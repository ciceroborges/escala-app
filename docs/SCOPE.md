# Escala de Louvor — Escopo do MVP

App para ministérios de louvor montarem a escala mensal **automaticamente**, gerenciarem o
repertório (com tom por cantor) e gerarem as mensagens de WhatsApp que hoje são feitas à mão.

Referência real: ministério de louvor da igreja local do Cícero (escala e repertório de
outubro/2026 enviados no grupo do WhatsApp + planilha do repertório).

**Stack:** Laravel + Blade + Tailwind + Alpine, em Docker. Mobile first (320px+).
**Banco:** próprio do projeto (não tem relação com o SmartEvento).

---

## 1. Acesso, ministérios e permissões

- Login **somente com Google**.
- Multi-ministério desde o início (modelo tipo LouveApp):
  - Ao logar, o usuário vê os ministérios em que o **e-mail dele foi pré-cadastrado**.
  - Sem vínculo → pode **criar o próprio ministério** (e pode criar mesmo tendo vínculo).
  - Só entra num ministério quem foi **pré-cadastrado/convidado** por e-mail.
- O líder **pré-cadastra** membros (nome, e-mail, funções). O membro já pode ser escalado
  antes de ter logado alguma vez; ao logar com aquele e-mail, o vínculo é feito.
- Papéis:
  - **Administrador** — tudo, inclusive gerenciar membros e permissões. Quem cria o
    ministério é administrador e pode promover outros.
  - **Membro** — por padrão vê as próprias escalas, a escala do mês e o repertório, e marca
    indisponibilidade. O administrador pode liberar permissões extras:
    - montar/editar escala
    - editar repertório e setlists
    - (ver escala completa x só as suas — padrão: ver completa)

No caso real: Lu (líder do louvor) e Giandro (DM/líder técnico) montam escala e repertório.

## 2. Funções

Por ministério, com padrão inicial:

| Função | Usa microfone |
|---|---|
| Vocal | sim |
| DM (direção musical — guia os músicos no ouvido) | sim |
| Teclado, Violão, Guitarra, Baixo, Bateria | não |
| Mesa | não |

- Uma pessoa pode ter várias funções e **acumular funções no mesmo culto**
  (ex.: João Lucas teclado + vocal; Giandro DM + baixo; Léo vocal + guitarra).
- Não há limite de microfones separado: ele fica implícito no máximo de vagas de vocal e DM
  (ex.: vocal máx. 4 + DM máx. 1 = 5 microfones). "Usa microfone" serve só para o total
  exibido na mensagem do WhatsApp. Quem acumula DM + vocal ocupa uma vaga de cada.

## 3. Configuração do culto

- MVP: só **domingo**, escala **mensal**. (Outros dias e período configurável: fase 2.)
- **Vagas por função** (mín/máx). Ex.: vocal 3–4, teclado 1, violão 0–1, guitarra 0–1,
  baixo 0–1, bateria 1, mesa 1, DM 0–1.

## 4. Membros

- Nome, e-mail, telefone (para fase 2), funções.
- **Limite de escalas por mês** (padrão 2; "sem limite" para quem vai em todos, como
  líder e o único tecladista).

## 5. Disponibilidade

- Líder abre o mês com prazo de resposta.
- Membro marca os domingos em que **não pode**.
- Líder vê quem respondeu / não respondeu.

## 6. Geração automática da escala (diferencial)

Preenche os domingos do mês respeitando, nesta ordem de força:

1. **Duras:** disponibilidade, função, vagas mín/máx, limite mensal.
2. **Preferências:** espaçamento (semana sim, semana não), rodízio justo (prioriza quem está
   menos escalado).

Depois:
- Líder **ajusta manualmente** livremente.
- Alertas: vaga vazia ou acima do máximo, acima do limite mensal, escalado em dia
  indisponível. Quando não consegue fechar uma vaga, explica o motivo.
- **Observação** livre por culto (ex.: "Coral canta 1 música", "Santa Ceia", "Ensaio sábado 16h").
- Status do mês: rascunho → publicado.

## 7. Repertório

- **Catálogo de músicas** do ministério: nome, artista, link YouTube, link Cifra Club.
- **Duas classificações opcionais** por ministério, cada uma com nome e opções (nome + emoji/cor)
  definidos pelo próprio ministério:
  - **Primária** — ex.: "Ritmo" (🔴 rápida, 🟡 média, 🔵 lenta). Aparece na mensagem do
    WhatsApp e no resumo do setlist.
  - **Secundária** — ex.: "Tipo" (louvor, adoração, ceia…). Pode ficar desativada.
  - Cada música recebe no máximo uma opção de cada.
  - Padrão criado com o ministério: primária "Ritmo"; secundária desativada. No caso real só
    a primária é usada (na planilha: rosa/amarelo/azul).
- **Tom por cantor**: tom + "começa em" opcional
  (planilha: "G, mas C" = acordes do tom de G, começa em C).
- **Importação** da planilha atual (ler o .xlsx/Google Sheets, incluindo a cor da célula = ritmo).

## 8. Setlist por culto

- Escolher músicas, **ordem manual**.
- **Voz principal** de cada música, escolhida entre os vocais escalados (pode ser mais de uma).
  Os demais vocais escalados fazem apoio/segunda voz — não é alerta estar sem música.
- Tom e "começa em" vêm do catálogo para aquele cantor; editáveis.
- Observação por música.
- Alerta de **tom pendente**.
- Resumo pela classificação primária (ex.: "2 rápidas, 1 média, 2 lentas").
- Coral/participações especiais ficam fora (no máximo, citados na observação do culto).

## 9. Mensagens de WhatsApp

Gera as **duas mensagens no formato atual**, com botões **copiar** e **compartilhar**:

- **Escala do mês:** por domingo — data, com/sem DM, vocais, instrumentos (pessoa + função),
  mesa, total de microfones, observação; lembretes no final.
- **Repertório do mês:** por domingo — vocais, músicas com o emoji da classificação primária, cantor e tom
  ("Patrick: A, mas começa em D"), "[tom?]" quando pendente.

Sem API do WhatsApp no MVP.

## 10. Telas

| Administrador / líder | Membro |
|---|---|
| Ministério (config, funções, vagas) | Meus ministérios |
| Membros, funções, limites, permissões | Minhas escalas |
| Músicas, tons e classificações | Escala do mês |
| Disponibilidade do mês | Marcar indisponibilidade |
| Gerar/editar escala | Repertório do culto (YouTube + cifra) |
| Setlists | |
| Mensagens WhatsApp | |

---

## Modelo de dados (rascunho)

```
users                (id, google_id, email, nome, avatar)
ministerios          (id, nome, igreja, criado_por)
membros              (id, ministerio_id, user_id?, nome, email, telefone,
                      papel[admin|membro], permissoes json, limite_mensal?,
                      unique(ministerio_id, email))
funcoes              (id, ministerio_id, nome, usa_microfone, ordem)
membro_funcao        (membro_id, funcao_id)
vagas_funcao         (ministerio_id, funcao_id, minimo, maximo)

periodos             (id, ministerio_id, ano, mes, status, prazo_disponibilidade)
cultos               (id, periodo_id, data, observacao)
indisponibilidades   (membro_id, culto_id)
escalacoes           (culto_id, membro_id, funcao_id)

classificacoes       (id, ministerio_id, nivel[primaria|secundaria], nome, ativa,
                      unique(ministerio_id, nivel))
classificacao_opcoes (id, classificacao_id, nome, emoji, cor, ordem)
musicas              (id, ministerio_id, nome, artista, youtube_url, cifra_url,
                      opcao_primaria_id?, opcao_secundaria_id?)
musica_tons          (musica_id, membro_id, tom, comeca_em?)
setlist_itens        (id, culto_id, musica_id, ordem, observacao)
setlist_vozes        (setlist_item_id, membro_id, tom, comeca_em?)
```

---

## Fases seguintes

**Fase 2**
- Lembrete no privado do escalado via WhatsApp Cloud API (mensagem modelo aprovada, com
  variáveis). Envio automático para grupo provavelmente não é suportado — confirmar.
- Importar a playlist do YouTube do repertório e casar com as músicas (fallback: busca).
- Página de **normas e macetes** (cabos, cuidados com equipamento etc.).
- Outros dias de culto (terça, quinta…) e período configurável.
- Pedido de troca com sugestão de substituto disponível.

**Fase 3**
- Cifra em **graus** no setlist, no tom do dia (reaproveitar o motor do app `graus`).
  Uso interno do ministério; não republicar letras publicamente.
- Abrir para outras igrejas como produto (planos/cobrança). A estrutura multi-ministério já
  existe desde o MVP.

---

## Pendências

- Planilha do repertório (.xlsx ou Google Sheets) para importar.
- Lista de membros com funções, para testar a geração com os dados reais de outubro/2026.
