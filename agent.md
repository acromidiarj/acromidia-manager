# 🧠 Acromidia Manager — Agent Instructions

Este documento serve como a **Fonte Única de Verdade (SoT)** para agentes de IA que interagem com este repositório. O objetivo é manter a consistência técnica, visual e funcional, evitando alucinações.

## 🛠️ Stack Tecnológica
1.  **Backend**: PHP (WordPress Plugin API, REST API customizada).
2.  **Frontend**: Vue.js 3 (Progressivo, sem build step, via CDN).
3.  **Estilização**: TailwindCSS (via CDN) + Vanilla CSS para overrides finos.
4.  **Ícones**: Lucide Icons.
5.  **Criptografia**: AES-256-CBC via PHP (Openssl) para chaves de API.

## 🎨 Identidade Visual (Design System)
*   **Tema Único**: Light Mode Premium (SaaS Style).
*   **Paleta Principal**:
    *   Primary: Indigo `#4f46e5`
    *   BG: Slate `#f8fafc`
    *   Text: Slate `#0f172a` (Títulos) e `#64748b` (Corpo).
*   **Componentes**:
    *   Cards: Cantos arredondados `20px`, bordas sutis `1px solid #e2e8f0`.
    *   Inputs: Altura `48px`, border-radius `14px`, background branco.
    *   Botões: Peso `font-black` ou `font-bold`, sombras suaves coloridas.

## 🚫 Regras de Ouro (Anti-Alucinação)
1.  **WP Admin Bar**: NUNCA force a cor branca nela. Ela deve seguir o padrão do WordPress (Preta/Grafite).
2.  **Variáveis Vue**: SEMPRE utilize o padrão `{{ ... }}` e garanta que a tag pai possua o atributo `v-cloak` para evitar flash de código bruto.
3.  **Segurança**: Campos de API nas configurações devem SEMPRE ser do tipo `password` para ocultar segredos salvos. Nunca exiba o valor real vindo do banco.
4.  **REST API**: Todos os endpoints devem usar Nonces do WordPress (`_wpnonce`) via header `X-WP-Nonce`.
5.  **Clean Code**: NUNCA utilize inline CSS hardcoded para cores. Refira-se sempre às classes do Tailwind ou às variáveis definidas no `:root`.

## 📁 Estrutura de Pastas
*   `/admin`:- [x] Implementar endpoint de DRE e Analytics no Backend <!-- id: 6 -->
- [x] Desenvolver Visão DRE e Métricas de Retenção no Dashboard <!-- id: 7 -->
- [x] Refinar Gráficos de Performance e consolidar Suite <!-- id: 8 -->
*Assinado: Acromidia Core Team*

## 🚀 Repositório e Deploy
*   **Git URL**: `https://github.com/acromidiarj/acro-manager.git`
*   **Release Atual**: `v3.0.1` (Mega Plataforma Multi-Gateway, Pipeline CRM Avançado e Limpador UI).

## 🌟 Features / Update v3.0.1+
*   **Motor Financeiro Multi-Gateway**: Interfaces de PagBank, Mercado Pago, Pagar.me, Stripe e Asaas instanciados via `Acromidia_Gateway_Factory`.
*   **Migração de Tokens**: Logica em `Acromidia_Encryption::decrypt(true)` com tolerância legada (fallback raw text) para chaves de API cruas antigas.
*   **Pipeline CRM (Tabs v2.0)**: O Funil é agrupado utilizando abas dinâmicas (`Aquisição e Setup` vs `Retenção e Risco`) pelo `crmTab`.
*   **Automação Ativa**: Contas que recebem sincronização automática de faturamento saudável ativam-se direto em `ativo`, saltando `onboarding`.
*   **UI Monitoramento**: Utilização de `overflow-hidden` seguro nas tabelas (resolvendo botões via CSS direções contrárias ou absolutas) e reset watch de estados de busca entre `views`.
*   **Paginação**: Tabela Carteira utilizando contadores dinâmicos no computed properties (`currentPage`, `totalPages`).
