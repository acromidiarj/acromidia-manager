<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gerencia configurações do plugin com criptografia.
 * Tela de settings no admin + getters estáticos seguros.
 */
class Acromidia_Settings {

    /** Chave do wp_options onde tudo é armazenado */
    const OPTION_KEY = 'acromidia_settings';

    /** Mapa dos campos de configuração */
    private static $fields = [
        'asaas_api_key'       => [
            'label'       => 'Chave API Asaas',
            'placeholder' => '$aact_prod_...',
            'help'        => 'Encontre em: Asaas → Integrações → API',
        ],
        'asaas_webhook_token' => [
            'label'       => 'Token do Webhook Asaas',
            'placeholder' => 'Token para validação dos webhooks',
            'help'        => 'Configure ao criar o webhook no painel Asaas',
        ],
        'wa_token'            => [
            'label'       => 'Token WhatsApp (Meta)',
            'placeholder' => 'Bearer token da Cloud API',
            'help'        => 'Meta Business → WhatsApp → Configuração da API',
        ],
        'wa_phone_id'         => [
            'label'       => 'Phone ID WhatsApp',
            'placeholder' => 'ID numérico do telefone',
            'help'        => 'Meta Business → WhatsApp → Configuração da API',
        ],
    ];

    /** Mapa de fallback para constantes do wp-config.php */
    private static $constant_map = [
        'asaas_api_key'       => 'ASAAS_API_KEY',
        'asaas_webhook_token' => 'ASAAS_WEBHOOK_TOKEN',
        'wa_token'            => 'WA_TOKEN',
        'wa_phone_id'         => 'WA_PHONE_ID',
    ];

    public function __construct() {
        // Prioridade 20: garante que o menu pai (registrado com prioridade 10) já exista
        add_action( 'admin_menu', [ $this, 'register_submenu' ], 20 );
        add_action( 'admin_init', [ $this, 'handle_save' ] );
    }

    /**
     * Getter estático — ponto central para obter qualquer configuração.
     * Prioridade: constante wp-config.php > valor criptografado no banco.
     */
    public static function get( $key ) {
        // 1. Prioridade: constante definida no wp-config.php
        if ( isset( self::$constant_map[ $key ] ) ) {
            $const = self::$constant_map[ $key ];
            if ( defined( $const ) && constant( $const ) !== '' ) {
                return constant( $const );
            }
        }

        // 2. Valor criptografado do banco
        $options = get_option( self::OPTION_KEY, [] );
        if ( ! empty( $options[ $key ] ) ) {
            return Acromidia_Encryption::decrypt( $options[ $key ] );
        }

        return '';
    }

    /**
     * Verifica se uma chave está configurada (por qualquer método).
     */
    public static function has( $key ) {
        return ! empty( self::get( $key ) );
    }

    // ───────────────────────────────────
    //  Submenu
    // ───────────────────────────────────

    public function register_submenu() {
        add_submenu_page(
            'acromidia-dashboard',
            'Configurações',
            'Configurações',
            'manage_options',
            'acromidia-settings',
            [ $this, 'render_page' ]
        );
    }

    // ───────────────────────────────────
    //  Salvar
    // ───────────────────────────────────

    public function handle_save() {
        if ( ! isset( $_POST['acromidia_settings_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['acromidia_settings_nonce'], 'acromidia_save_settings' ) ) {
            wp_die( 'Nonce inválido.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permissão insuficiente.' );
        }

        $current_options = get_option( self::OPTION_KEY, [] );
        $new_options     = [];

        foreach ( self::$fields as $key => $config ) {
            $raw_value = isset( $_POST[ 'acro_' . $key ] ) ? sanitize_text_field( $_POST[ 'acro_' . $key ] ) : '';

            // Se o campo está vazio no form, mas já existe no banco, manter
            // (campos de senha enviam vazio quando não alterados)
            if ( $raw_value === '' && ! empty( $current_options[ $key ] ) ) {
                $new_options[ $key ] = $current_options[ $key ];
            } elseif ( $raw_value !== '' ) {
                $new_options[ $key ] = Acromidia_Encryption::encrypt( $raw_value );
            }
        }

        update_option( self::OPTION_KEY, $new_options );

        // Redirect com mensagem de sucesso
        add_settings_error( 'acromidia_settings', 'settings_updated', 'Configurações salvas com sucesso!', 'updated' );
        set_transient( 'acromidia_settings_saved', true, 30 );

        wp_redirect( admin_url( 'admin.php?page=acromidia-settings&saved=1' ) );
        exit;
    }

    // ───────────────────────────────────
    //  Render
    // ───────────────────────────────────

    public function render_page() {
        $saved     = isset( $_GET['saved'] );
        $dash_url  = admin_url( 'admin.php?page=acromidia-dashboard' );
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://unpkg.com/lucide@latest"></script>

        <style>
            :root {
              --acro-primary: #4f46e5;
              --acro-bg: #f8fafc;
              --acro-text: #0f172a;
              --acro-slate: #64748b;
              --acro-border: #e2e8f0;
            }
            #wpcontent, #wpbody { padding-left: 0 !important; background: var(--acro-bg) !important; }
            #wpbody-content { padding-bottom: 60px; }
            .update-nag, .notice { display: none !important; }
            #wpadminbar { background: #ffffff !important; border-bottom: 1px solid rgba(0,0,0,0.06) !important; }

            .card-glass { background: #ffffff; border: 1px solid var(--acro-border); border-radius: 24px; box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.05); }

            .btn-primary { 
                background: var(--acro-primary) !important; color: white !important; border-radius: 16px !important; padding: 20px 40px !important; font-weight: 800 !important; font-size: 14px !important; text-transform: uppercase; letter-spacing: 0.1em;
                display:flex; align-items:center; justify-content:center; gap:12px; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border:none!important; cursor:pointer; width:100%;
                box-shadow: 0 10px 15px -3px rgba(79,70,229,0.3);
            }
            .btn-primary:hover { background: #4338ca !important; transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(79,70,229,0.4); }

            .input-label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--acro-slate); margin-bottom: 8px; margin-left: 4px; }
            .modern-input {
                width: 100% !important; height: 56px !important; background: #fff !important; 
                border: 2px solid var(--acro-border) !important; border-radius: 16px !important; 
                padding: 0 20px 0 54px !important;
                font-size: 15px !important; font-weight: 600 !important; color: var(--acro-text) !important; 
                transition: all 0.2s !important; outline: none !important; box-sizing: border-box !important;
            }
            .modern-input:focus { border-color: var(--acro-primary) !important; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important; }
            
            .input-icon { 
                position: absolute !important; left: 20px !important; top: 50% !important; transform: translateY(-50%) !important; 
                width: 20px !important; height: 20px !important; color: #94a3b8 !important; 
                pointer-events: none !important; z-index: 10 !important;
            }
        </style>

        <div class="min-h-screen font-sans p-8 md:p-12 max-w-4xl mx-auto" style="color: #0f172a; font-family: 'Inter', sans-serif;">
            
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <div class="w-16 h-16 bg-slate-900 rounded-[22px] flex items-center justify-center shadow-2xl shadow-slate-300 mb-8 border border-slate-700">
                        <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Cofre de Integrações</h1>
                    <p class="text-slate-500 font-bold mt-2 text-sm leading-relaxed">Gerencie chaves de API e tokens criptografados com padrão militar (AES-256).</p>
                </div>
            </header>

            <?php if ( $saved ) : ?>
                <div class="mb-12 p-6 bg-emerald-50 border-emerald-500 border-l-4 rounded-xl flex items-center gap-5 animate-in fade-in slide-in-from-top-4 duration-500 shadow-sm">
                    <div class="w-12 h-12 bg-emerald-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-emerald-200"><i data-lucide="check" class="w-6 h-6"></i></div>
                    <div>
                        <p class="font-black text-slate-900 text-lg tracking-tighter">Sucesso Absoluto</p>
                        <p class="font-bold text-slate-500 text-sm mt-0.5">As credenciais foram validadas, encriptadas e seladas com segurança.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-12">
                <?php wp_nonce_field( 'acromidia_save_settings', 'acromidia_settings_nonce' ); ?>

                <!-- ASAAS -->
                <div class="card-glass overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-sky-400 to-sky-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-sky-200">
                                <i data-lucide="credit-card" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Gateway Asaas</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Faturamento e Assinaturas</p>
                            </div>
                        </div>
                        <?php if ( self::has('asaas_api_key') ) : ?>
                            <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Integrado</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'asaas_api_key', 'key' ); ?>
                        <?php self::render_field( 'asaas_webhook_token', 'link' ); ?>
                        
                        <div class="pt-6 border-t border-slate-100">
                            <label class="input-label mb-3">URL do Webhook (Copie para o painel Asaas)</label>
                            <div class="modern-input !bg-slate-50 flex items-center select-all !pl-0 cursor-copy !border-slate-200 overflow-hidden" style="padding:0">
                                <span class="font-mono text-[13px] text-sky-600 truncate w-full px-6 py-4 bg-transparent outline-none m-0"><?php echo esc_html( rest_url( 'acromidia/v1/webhook/asaas' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="card-glass overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-emerald-400 to-emerald-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-emerald-200">
                                <i data-lucide="message-circle" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">WhatsApp Cloud (Meta)</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Automação de Régua e Lembretes</p>
                            </div>
                        </div>
                        <?php if ( self::has('wa_token') ) : ?>
                            <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Conectado</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'wa_token', 'key' ); ?>
                        <?php self::render_field( 'wa_phone_id', 'hash' ); ?>
                    </div>
                </div>

                <!-- Snippet Manager -->
                <div class="card-glass overflow-hidden mt-12 mb-12">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-indigo-400 to-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-indigo-200">
                                <i data-lucide="code" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Snippet de Gerenciamento</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Cole no final do functions.php interno dos sites clientes</p>
                            </div>
                        </div>
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('client-snippet').innerText); alert('Copiado para a área de transferência!');" class="p-4 bg-white hover:bg-indigo-50 transition-colors rounded-xl text-indigo-600 flex items-center gap-3 text-[11px] font-black uppercase tracking-widest border border-indigo-200 shadow-sm"><i data-lucide="copy" class="w-4 h-4"></i> Copiar Código</button>
                    </div>
                    <div class="p-10 bg-[#0f172a] relative">
                        <pre id="client-snippet" class="text-[13px] text-emerald-400 font-mono overflow-x-auto m-0 leading-relaxed selection:bg-indigo-500/30">/**
 * Acro Manager - Verificação Universal de Licença e Manutenção
 * Basta colar no fim do functions.php do Tema do cliente.
 */
add_action('template_redirect', 'acro_check_site_status');
function acro_check_site_status() {
    $manager_url    = 'https://acromidia.com'; 
    $cliente_domain = $_SERVER['HTTP_HOST'];
    
    // Deleta do Cache se revalidado manualmente com ?revalidar-site=sim na URL
    if ( isset($_GET['revalidar-site']) ) delete_transient('acro_site_status');
    
    $status = get_transient('acro_site_status');
    
    if ( false === $status ) {
        $api_url  = $manager_url . '/wp-json/acromidia/v1/client-site-check?domain=' . urlencode($cliente_domain) . '&z=' . time();
        $response = wp_remote_get( $api_url, ['timeout' => 5] );
        
        if ( ! is_wp_error($response) ) {
            $body = json_decode( wp_remote_retrieve_body($response), true );
            $status = isset($body['status']) ? $body['status'] : 'active';
            set_transient('acro_site_status', $status, 3600); // Salva por 1h
        } else {
            $status = 'active'; 
        }
    }
    
    if ( $status === 'blocked' ) {
        wp_die(
            '&lt;div style=&quot;max-width: 600px; margin: 80px auto; text-align: center; font-family: system-ui, sans-serif; color: #1e293b;&quot;&gt;
                &lt;svg width=&quot;64&quot; height=&quot;64&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;#64748b&quot; stroke-width=&quot;2&quot; style=&quot;margin:0 auto 20px auto;&quot;&gt;&lt;circle cx=&quot;12&quot; cy=&quot;12&quot; r=&quot;10&quot;/&gt;&lt;line x1=&quot;12&quot; y1=&quot;8&quot; x2=&quot;12&quot; y2=&quot;12&quot;/&gt;&lt;line x1=&quot;12&quot; y1=&quot;16&quot; x2=&quot;12.01&quot; y2=&quot;16&quot;/&gt;&lt;/svg&gt;
                &lt;h1 style=&quot;font-size:32px; font-weight:900; margin-bottom: 16px;&quot;&gt;Problemas Técnicos&lt;/h1&gt;
                &lt;p style=&quot;font-size:16px; color:#64748b; line-height: 1.5;&quot;&gt;Nossos servidores de nuvem estão passando por uma manutenção técnica ou atualização inesperada em uma de nossas centrais. &lt;br&gt;&lt;br&gt;Aguarde, por favor, retornaremos em breve.&lt;/p&gt;
                &lt;hr style=&quot;margin:40px 0; border:0; border-top:1px solid #e2e8f0;&quot;&gt;
                &lt;p style=&quot;font-size:11px; font-weight: bold; color:#94a3b8; text-transform:uppercase; letter-spacing:2px;&quot;&gt;Infraestrutura Técnica: &lt;b&gt;AcroMidia&lt;/b&gt;&lt;/p&gt;
             &lt;/div&gt;',
            'Manutenção Técnica',
            ['response' => 503]
        );
    }
}
</pre>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-5 pt-8">
                    <button type="submit" class="btn-primary max-w-md mx-auto">
                        <i data-lucide="lock" class="w-5 h-5"></i> Criptografar e Salvar Tudo
                    </button>
                    <div class="flex items-center gap-2 text-slate-400">
                        <i data-lucide="shield" class="w-3 h-3"></i>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em]">AES-256-CBC Protection Active</span>
                    </div>
                </div>
            </form>
        </div>

        <script>lucide.createIcons();</script>
        <?php
    }

    private static function render_field( $key, $icon = 'key' ) {
        $config    = self::$fields[ $key ];
        $has_value = self::has( $key );
        $source    = '';
        if ( $has_value && isset( self::$constant_map[ $key ] ) ) {
            $const  = self::$constant_map[ $key ];
            $source = ( defined( $const ) && constant( $const ) !== '' ) ? 'wp-config.php' : 'Armazenamento Criptografado';
        }
        ?>
        <div class="space-y-2">
            <div class="flex justify-between items-center px-1">
                <label for="acro_<?php echo esc_attr( $key ); ?>" class="input-label">
                    <?php echo esc_html( $config['label'] ); ?>
                </label>
                <?php if ( $has_value ) : ?>
                    <span class="text-[9px] font-black text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 uppercase tracking-widest flex items-center gap-1">
                        <i data-lucide="check" class="w-2.5 h-2.5"></i> <?php echo esc_html( $source ); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="relative">
                <i data-lucide="<?php echo esc_attr( $icon ); ?>" class="input-icon"></i>
                <input type="password"
                       id="acro_<?php echo esc_attr( $key ); ?>"
                       name="acro_<?php echo esc_attr( $key ); ?>"
                       placeholder="<?php echo esc_attr( $has_value ? '••••••••••••••••••••' : $config['placeholder'] ); ?>"
                       class="modern-input"
                       autocomplete="off">
            </div>
            
            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest pl-1 mt-2 flex items-center gap-2"><i data-lucide="info" class="w-3 h-3 text-indigo-400"></i> <?php echo esc_html( $config['help'] ); ?></p>
        </div>
        <?php
    }
}


