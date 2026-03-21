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
*   `/admin`: Templates de UI do painel (ex: `ui-dashboard.php`).
*   `/includes`: Classes de lógica (Settings, API, Database).
*   `/assets`: Ícones de menu e scripts globais.

---
*Assinado: Acromidia Core Team*
