<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gerencia configurações do plugin com criptografia.
 * Tela de settings no admin + getters estáticos seguros.
 */
class Acromidia_Settings {

    /** Chave do wp_options onde tudo é armazenado */
    const OPTION_KEY = 'acromidia_settings';

    private static $fields = [
        'primary_gateway'     => [
            'label'       => 'Plataforma Ativa',
            'help'        => 'Motor financeiro que será usado para criar assinaturas e faturas.',
            'type'        => 'select',
            'options'     => [
                'asaas'       => 'Asaas Bank',
                'mercadopago' => 'Mercado Pago',
                'stripe'      => 'Stripe',
                'pagarme'     => 'Pagar.me',
                'pagbank'     => 'PagBank (UOL)'
            ]
        ],
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
        'asaas_mode'          => [
            'label'       => 'Ambiente Asaas',
            'type'        => 'select',
            'options'     => [
                'prod'    => 'Produção (Real)',
                'sandbox' => 'Sandbox (Testes)',
            ],
            'help'        => 'Sandbox usa contas de teste e faturas fictícias.',
        ],
        'mp_access_token'     => [
            'label'       => 'Access Token Mercado Pago',
            'placeholder' => 'APP_USR-...',
            'help'        => 'Mercado Pago → Suas Integrações → Credenciais de Produção',
        ],
        'mp_webhook_secret'   => [
            'label'       => 'Webhook Secret Mercado Pago',
            'placeholder' => 'Chave para validar webhooks (opcional)',
            'help'        => 'Mercado Pago → Notificações → Webhooks',
        ],
        'mp_mode'             => [
            'label'       => 'Ambiente Mercado Pago',
            'type'        => 'select',
            'options'     => [ 'prod' => 'Produção', 'sandbox' => 'Sandbox' ],
            'help'        => 'Sandbox usa contas de teste.',
        ],
        'stripe_api_key'      => [
            'label'       => 'Secret Key Stripe',
            'placeholder' => 'sk_live_...',
            'help'        => 'Stripe Dashboard → Developers → API Keys',
        ],
        'stripe_webhook_secret' => [
            'label'       => 'Signing Secret Stripe',
            'placeholder' => 'whsec_...',
            'help'        => 'Stripe → Developers → Webhooks → Signing secret',
        ],
        'stripe_mode'         => [
            'label'       => 'Ambiente Stripe',
            'type'        => 'select',
            'options'     => [ 'prod' => 'Produção', 'sandbox' => 'Test Mode' ],
            'help'        => 'Test Mode usa sk_test_...',
        ],
        'pagarme_api_key'     => [
            'label'       => 'Secret Key Pagar.me',
            'placeholder' => 'sk_...',
            'help'        => 'Pagar.me Dash → Configurações → Chaves de API',
        ],
        'pagarme_webhook_secret'=> [
            'label'       => 'Webhook Secret Pagar.me',
            'placeholder' => 'Secret configurado no Pagar.me',
            'help'        => 'Validação de segurança das notificações.',
        ],
        'pagarme_mode'        => [
            'label'       => 'Ambiente Pagar.me',
            'type'        => 'select',
            'options'     => [ 'prod' => 'Produção', 'sandbox' => 'Sandbox' ],
            'help'        => 'Pagar.me Dash → Configurações → Chaves',
        ],
        'pagbank_api_key'     => [
            'label'       => 'Token Sandbox / Produção PagBank',
            'placeholder' => 'FEFDF...',
            'help'        => 'PagBank → Suas Aplicações → Connect',
        ],
        'pagbank_webhook_secret'=> [
            'label'       => 'Webhook Secret PagBank',
            'placeholder' => 'Token de validação de notificação',
            'help'        => 'Configure no painel de Notificações.',
        ],
        'pagbank_mode'        => [
            'label'       => 'Ambiente PagBank',
            'type'        => 'select',
            'options'     => [ 'prod' => 'Produção', 'sandbox' => 'Sandbox' ],
            'help'        => 'Sandbox usa endpoint específico de testes.',
        ],
        'wa_token'            => [
            'label'       => 'Token WhatsApp (Meta)',
            'placeholder' => 'Bearer token da Cloud API',
            'help'        => 'Meta Business → WhatsApp → Configuração da API',
        ],
        'wa_phone_id'         => [
            'label'       => 'Phone ID WhatsApp',
            'type'        => 'text',
            'placeholder' => 'ID numérico do telefone',
            'help'        => 'Meta Business → WhatsApp → Configuração da API',
        ],
        'wa_contact_phone'    => [
            'label'       => 'WhatsApp de Suporte',
            'type'        => 'text',
            'placeholder' => '5511999999999',
            'help'        => 'Número que aparecerá no Portal do Cliente para suporte (DDI + DDD + Número).',
        ],
        'restrict_admin'      => [
            'label'       => 'Modo Cliente Restrito',
            'type'        => 'select',
            'options'     => [
                'no'  => 'Desativado (Padrão)',
                'yes' => 'Ativado (Ocultar menus do WordPress)',
            ],
            'help'        => 'Se ativado, usuários sem permissão de Administrador verão APENAS o Acro Manager no menu.',
        ],
        'dashboard_logo'      => [
            'label'       => 'Logo do Dashboard (WordPress)',
            'type'        => 'media',
            'placeholder' => 'Selecione uma imagem da biblioteca...',
            'help'        => 'Clique no botão para escolher um logo da sua galeria de mídia.',
        ],
        'primary_color'       => [
            'label'       => 'Cor Primária do Painel',
            'type'        => 'color',
            'help'        => 'Define a cor principal de botões, ícones e destaques do sistema.',
        ],
        'mrr_goal'            => [
            'label'       => 'Meta de MRR Mensal (R$)',
            'type'        => 'number',
            'placeholder' => '10000',
            'help'        => 'Sua meta de faturamento recorrente mensal. Aparecerá no Dashboard como barra de progresso.',
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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media_scripts' ] );
        add_action( 'admin_init', [ $this, 'handle_save' ] );
    }

    public function enqueue_media_scripts( $hook ) {
        if ( strpos( $hook, 'acromidia-settings' ) !== false ) {
            wp_enqueue_media();
        }
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
        // Se estiver em modo restrito, permite acesso básico ("read"), caso contrário, apenas administradores ("manage_options")
        $capability = ( self::get('restrict_admin') === 'yes' ) ? 'read' : 'manage_options';

        add_submenu_page(
            'acromidia-dashboard',
            'Configurações',
            'Configurações',
            $capability,
            'acromidia-settings',
            [ $this, 'render_page' ]
        );
    }

    // ───────────────────────────────────
    //  Salvar
    // ───────────────────────────────────

    public function handle_save() {
        $capability = ( self::get('restrict_admin') === 'yes' ) ? 'read' : 'manage_options';

        if ( isset( $_GET['disconnect'] ) && isset( $_GET['_wpnonce'] ) ) {
            $gateway = sanitize_text_field( $_GET['disconnect'] );
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'disconnect_' . $gateway ) && current_user_can( $capability ) ) {
                $options = get_option( self::OPTION_KEY, [] );
                
                $gateway_fields = [
                    'asaas'       => ['asaas_api_key', 'asaas_webhook_token'],
                    'mercadopago' => ['mp_access_token', 'mp_webhook_secret'],
                    'stripe'      => ['stripe_api_key', 'stripe_webhook_secret'],
                    'pagarme'     => ['pagarme_api_key', 'pagarme_webhook_secret'],
                    'pagbank'     => ['pagbank_api_key', 'pagbank_webhook_secret'],
                    'whatsapp'    => ['wa_token', 'wa_phone_id']
                ];

                if ( isset( $gateway_fields[$gateway] ) ) {
                    foreach ( $gateway_fields[$gateway] as $field ) {
                        unset( $options[$field] );
                    }
                    update_option( self::OPTION_KEY, $options );
                    wp_redirect( admin_url( 'admin.php?page=acromidia-settings&disconnected=1' ) );
                    exit;
                }
            }
        }

        if ( ! isset( $_POST['acromidia_settings_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['acromidia_settings_nonce'], 'acromidia_save_settings' ) ) {
            wp_die( 'Nonce inválido.' );
        }

        if ( ! current_user_can( $capability ) ) {
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
        $saved        = isset( $_GET['saved'] );
        $disconnected = isset( $_GET['disconnected'] );
        $dash_url     = admin_url( 'admin.php?page=acromidia-dashboard' );
        $custom_logo  = self::get( 'dashboard_logo' );
        $primary_color = self::get( 'primary_color' ) ?: '#4f46e5';
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://unpkg.com/lucide@latest"></script>

        <style>
            :root {
              --acro-primary: <?php echo esc_attr($primary_color); ?>;
              --acro-bg: #f8fafc;
              --acro-text: #0f172a;
              --acro-slate: #64748b;
              --acro-border: #e2e8f0;
            }
            #wpcontent, #wpbody { padding-left: 0 !important; background: var(--acro-bg) !important; }
            #wpbody-content { padding-bottom: 60px; }
            .update-nag, .notice { display: none !important; }

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
                    <div class="w-16 h-16 rounded-[22px] flex items-center justify-center shadow-2xl shadow-indigo-200 mb-8 overflow-hidden" style="background-color: var(--acro-primary);">
                        <?php if ( $custom_logo ) : ?>
                            <img src="<?php echo esc_url($custom_logo); ?>" class="w-full h-full object-contain p-2">
                        <?php else : ?>
                            <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                        <?php endif; ?>
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

            <?php if ( $disconnected ) : ?>
                <div class="mb-12 p-6 bg-amber-50 border-amber-500 border-l-4 rounded-xl flex items-center gap-5 animate-in fade-in slide-in-from-top-4 duration-500 shadow-sm">
                    <div class="w-12 h-12 bg-amber-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-amber-200"><i data-lucide="trash-2" class="w-6 h-6"></i></div>
                    <div>
                        <p class="font-black text-slate-900 text-lg tracking-tighter">Integração Removida</p>
                        <p class="font-bold text-slate-500 text-sm mt-0.5">As credenciais foram desconectadas e excluídas do banco de dados.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="relative">
                <?php wp_nonce_field( 'acromidia_save_settings', 'acromidia_settings_nonce' ); ?>

                <!-- TABS HEADER -->
                <div class="flex border-b border-slate-200 mb-8 gap-8">
                    <button type="button" class="tab-btn active pb-4 font-black uppercase tracking-wider text-[12px] border-b-4 border-slate-900 text-slate-900 transition-colors" data-target="tab-financeiro">Motor Financeiro</button>
                    <button type="button" class="tab-btn pb-4 font-black uppercase tracking-wider text-[12px] border-b-4 border-transparent text-slate-400 hover:text-slate-600 transition-colors" data-target="tab-whatsapp">WhatsApp (Régua)</button>
                    <button type="button" class="tab-btn pb-4 font-black uppercase tracking-wider text-[12px] border-b-4 border-transparent text-slate-400 hover:text-slate-600 transition-colors" data-target="tab-sistema">Sistema Interno</button>
                </div>

                <!-- TAB 1: Financeiro -->
                <div id="tab-financeiro" class="tab-pane space-y-12 block">
                    <!-- Motor Principal (Gateways) -->
                <div class="card-glass overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-slate-700 to-slate-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-slate-900/50">
                                <i data-lucide="cpu" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-white tracking-tighter">Motor Financeiro (Gateway)</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Selecione o provedor B2B Principal</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'primary_gateway', 'server' ); ?>
                    </div>
                </div>

                <!-- ASAAS -->
                <div class="card-glass overflow-hidden" id="box-asaas">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-sky-400 to-sky-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-sky-200">
                                <i data-lucide="credit-card" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Credenciais Asaas</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Faturamento e Assinaturas</p>
                            </div>
                        </div>
                        <?php if ( self::has('asaas_api_key') ) : ?>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Integrado</span>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=acromidia-settings&disconnect=asaas'), 'disconnect_asaas' ) ); ?>" onclick="return confirm('Tem certeza que deseja desconectar esta integração? As chaves serão apagadas do banco de dados.')" class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-[0.1em] flex items-center gap-1 transition-colors mt-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Desconectar</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'asaas_api_key', 'key' ); ?>
                        <?php self::render_field( 'asaas_webhook_token', 'link' ); ?>
                        <?php self::render_field( 'asaas_mode', 'monitor' ); ?>
                        
                        <div class="pt-6 border-t border-slate-100">
                            <label class="input-label mb-3">URL do Webhook (Copie para o painel Asaas)</label>
                            <div class="modern-input !bg-slate-50 flex items-center select-all !pl-0 cursor-copy !border-slate-200 overflow-hidden" style="padding:0">
                                <span class="font-mono text-[13px] text-sky-600 truncate w-full px-6 py-4 bg-transparent outline-none m-0"><?php echo esc_html( rest_url( 'acromidia/v1/webhook/asaas' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MERCADO PAGO -->
                <div class="card-glass overflow-hidden hidden" id="box-mercadopago">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-blue-500 to-cyan-400 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-blue-200">
                                <i data-lucide="shopping-bag" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Credenciais Mercado Pago</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Faturamento e Assinaturas</p>
                            </div>
                        </div>
                        <?php if ( self::has('mp_access_token') ) : ?>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Integrado</span>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=acromidia-settings&disconnect=mercadopago'), 'disconnect_mercadopago' ) ); ?>" onclick="return confirm('Tem certeza que deseja desconectar esta integração? As chaves serão apagadas do banco de dados.')" class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-[0.1em] flex items-center gap-1 transition-colors mt-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Desconectar</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'mp_access_token', 'key' ); ?>
                        <?php self::render_field( 'mp_webhook_secret', 'link' ); ?>
                        <?php self::render_field( 'mp_mode', 'monitor' ); ?>
                        <div class="pt-6 border-t border-slate-100">
                            <label class="input-label mb-3">URL do Webhook (Copie para o Mercado Pago)</label>
                            <div class="modern-input !bg-slate-50 flex items-center select-all !pl-0 cursor-copy !border-slate-200 overflow-hidden" style="padding:0">
                                <span class="font-mono text-[13px] text-sky-600 truncate w-full px-6 py-4 bg-transparent outline-none m-0"><?php echo esc_html( rest_url( 'acromidia/v1/webhook/mercadopago' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STRIPE -->
                <div class="card-glass overflow-hidden hidden" id="box-stripe">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-indigo-500 to-purple-500 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-indigo-200">
                                <i data-lucide="credit-card" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Credenciais Stripe</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Faturamento e Assinaturas</p>
                            </div>
                        </div>
                        <?php if ( self::has('stripe_api_key') ) : ?>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Integrado</span>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=acromidia-settings&disconnect=stripe'), 'disconnect_stripe' ) ); ?>" onclick="return confirm('Tem certeza que deseja desconectar esta integração? As chaves serão apagadas do banco de dados.')" class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-[0.1em] flex items-center gap-1 transition-colors mt-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Desconectar</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'stripe_api_key', 'key' ); ?>
                        <?php self::render_field( 'stripe_webhook_secret', 'link' ); ?>
                        <?php self::render_field( 'stripe_mode', 'monitor' ); ?>
                        <div class="pt-6 border-t border-slate-100">
                            <label class="input-label mb-3">URL do Webhook (Copie para o Stripe)</label>
                            <div class="modern-input !bg-slate-50 flex items-center select-all !pl-0 cursor-copy !border-slate-200 overflow-hidden" style="padding:0">
                                <span class="font-mono text-[13px] text-sky-600 truncate w-full px-6 py-4 bg-transparent outline-none m-0"><?php echo esc_html( rest_url( 'acromidia/v1/webhook/stripe' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAGARME -->
                <div class="card-glass overflow-hidden hidden" id="box-pagarme">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-emerald-500 to-teal-400 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-emerald-200">
                                <i data-lucide="briefcase" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Credenciais Pagar.me</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Faturamento e Assinaturas</p>
                            </div>
                        </div>
                        <?php if ( self::has('pagarme_api_key') ) : ?>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Integrado</span>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=acromidia-settings&disconnect=pagarme'), 'disconnect_pagarme' ) ); ?>" onclick="return confirm('Tem certeza que deseja desconectar esta integração? As chaves serão apagadas do banco de dados.')" class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-[0.1em] flex items-center gap-1 transition-colors mt-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Desconectar</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'pagarme_api_key', 'key' ); ?>
                        <?php self::render_field( 'pagarme_webhook_secret', 'link' ); ?>
                        <?php self::render_field( 'pagarme_mode', 'monitor' ); ?>
                        <div class="pt-6 border-t border-slate-100">
                            <label class="input-label mb-3">URL do Webhook (Copie para o Pagar.me)</label>
                            <div class="modern-input !bg-slate-50 flex items-center select-all !pl-0 cursor-copy !border-slate-200 overflow-hidden" style="padding:0">
                                <span class="font-mono text-[13px] text-sky-600 truncate w-full px-6 py-4 bg-transparent outline-none m-0"><?php echo esc_html( rest_url( 'acromidia/v1/webhook/pagarme' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAGBANK -->
                <div class="card-glass overflow-hidden hidden" id="box-pagbank">
                    <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-green-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-green-500 to-lime-400 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-green-200">
                                <i data-lucide="badge-dollar-sign" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Credenciais PagBank</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Faturamento e Assinaturas</p>
                            </div>
                        </div>
                        <?php if ( self::has('pagbank_api_key') ) : ?>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Integrado</span>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=acromidia-settings&disconnect=pagbank'), 'disconnect_pagbank' ) ); ?>" onclick="return confirm('Tem certeza que deseja desconectar esta integração? As chaves serão apagadas do banco de dados.')" class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-[0.1em] flex items-center gap-1 transition-colors mt-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Desconectar</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'pagbank_api_key', 'key' ); ?>
                        <?php self::render_field( 'pagbank_webhook_secret', 'link' ); ?>
                        <?php self::render_field( 'pagbank_mode', 'monitor' ); ?>
                        <div class="pt-6 border-t border-slate-100">
                            <label class="input-label mb-3">URL de Notificação (Copie para o PagBank)</label>
                            <div class="modern-input !bg-slate-50 flex items-center select-all !pl-0 cursor-copy !border-slate-200 overflow-hidden" style="padding:0">
                                <span class="font-mono text-[13px] text-sky-600 truncate w-full px-6 py-4 bg-transparent outline-none m-0"><?php echo esc_html( rest_url( 'acromidia/v1/webhook/pagbank' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fim do container de boxes => o JS injeta o box ativo logo ACIMA desta tag: -->
                <div id="gateway-anchor"></div>
                </div> <!-- /TAB 1 -->

                <!-- TAB 2: WhatsApp -->
                <div id="tab-whatsapp" class="tab-pane space-y-12 hidden">
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
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-[0.2em] flex items-center gap-2 shadow-sm"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Conectado</span>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=acromidia-settings&disconnect=whatsapp'), 'disconnect_whatsapp' ) ); ?>" onclick="return confirm('Tem certeza que deseja desconectar esta integração? O acesso a API será revogado.')" class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-[0.1em] flex items-center gap-1 transition-colors mt-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Desconectar</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-10 space-y-8 bg-white">
                        <?php self::render_field( 'wa_token', 'key' ); ?>
                        <?php self::render_field( 'wa_phone_id', 'hash' ); ?>
                        <?php self::render_field( 'wa_contact_phone', 'message-circle' ); ?>
                    </div>
                </div>
                </div> <!-- /TAB 2 -->

                <!-- TAB 3: Sistema -->
                <div id="tab-sistema" class="tab-pane space-y-12 hidden">
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
 */
add_action('template_redirect', 'acro_check_site_status');
function acro_check_site_status() {
    $manager_url    = '<?php echo home_url(); ?>'; 
    $cliente_domain = $_SERVER['HTTP_HOST'];
    $status = get_transient('acro_site_status');
    if ( false === $status ) {
        $api_url  = $manager_url . '/wp-json/acromidia/v1/client-site-check?domain=' . urlencode($cliente_domain);
        $response = wp_remote_get( $api_url, ['timeout' => 5] );
        if ( ! is_wp_error($response) ) {
            $body = json_decode( wp_remote_retrieve_body($response), true );
            $status = $body['status'] ?? 'active';
            set_transient('acro_site_status', $status, 3600);
        }
    }
    if ( $status === 'blocked' ) {
        wp_die('Sistema em Manutenção Técnica.', 'Aguarde...', ['response' => 503]);
    }
}</pre>
                        </div>
                    </div>

                    <!-- Controle de Restrição (Modo Cliente) -->
                    <div class="card-glass overflow-hidden mt-8 mb-12">
                        <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-gradient-to-tr from-indigo-500 to-indigo-700 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-indigo-900/50">
                                    <i data-lucide="shield-off" class="w-8 h-8"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-white tracking-tighter">Painel de Admin Restrito</h2>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Configure o acesso dos seus clientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-10 space-y-8 bg-white">
                            <?php self::render_field( 'restrict_admin', 'lock' ); ?>
                        </div>
                    </div>

                    <!-- Branding e Identidade Visual -->
                    <div class="card-glass overflow-hidden mt-8 mb-12">
                        <div class="px-10 py-8 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-gradient-to-tr from-indigo-400 to-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-indigo-200">
                                    <i data-lucide="palette" class="w-8 h-8"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Branding e Identidade</h2>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Personalize o visual do painel para seus clientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-10 space-y-8 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <?php self::render_field( 'dashboard_logo', 'image' ); ?>
                                <?php self::render_field( 'primary_color', 'palette' ); ?>
                            </div>
                            <div class="mt-8 pt-8 border-t border-slate-100">
                                <?php self::render_field( 'mrr_goal', 'target' ); ?>
                            </div>
                        </div>
                    </div>
                </div> <!-- /TAB 3 -->

                <div class="flex flex-col items-center gap-5 pt-8 mt-12 border-t border-slate-200">
                    <button type="submit" class="btn-primary max-w-md mx-auto">
                        <i data-lucide="lock" class="w-5 h-5"></i> Criptografar e Salvar Tudo
                    </button>
                    <div class="flex items-center gap-2 text-slate-400">
                        <i data-lucide="shield" class="w-3 h-3"></i>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em]">AES-256-CBC Protection Active</span>
                    </div>
                </div>
            </form>
            
            <div id="coming-soon-gateway" class="hidden card-glass p-12 overflow-hidden mt-8 border-dashed border-2 border-slate-200 bg-slate-50 flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-slate-200 rounded-3xl flex items-center justify-center text-slate-400 mb-6">
                    <i data-lucide="hammer" class="w-10 h-10"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tighter">Gateway Em Desenvolvimento</h3>
                <p class="text-slate-500 font-bold mt-2 text-sm max-w-md">Nossa equipe de engenharia está validando a API v3 desta plataforma. A integração nativa será liberada nas próximas atualizações automáticas.</p>
            </div>
        </div>

        <script>
            lucide.createIcons();
            
            document.addEventListener('DOMContentLoaded', () => {
                const gatewaySelect = document.getElementById('acro_primary_gateway');
                const form = document.querySelector('form');
                
                const boxes = {
                    'asaas': document.getElementById('box-asaas'),
                    'mercadopago': document.getElementById('box-mercadopago'),
                    'stripe': document.getElementById('box-stripe'),
                    'pagarme': document.getElementById('box-pagarme'),
                    'pagbank': document.getElementById('box-pagbank')
                };
                
                const anchor = document.getElementById('gateway-anchor');
                
                function updateGatewayUI() {
                    const val = gatewaySelect.value;
                    
                    Object.values(boxes).forEach(box => {
                        if(box) {
                            box.style.display = 'none';
                            box.classList.remove('block');
                        }
                    });
                    
                    if(boxes[val]) {
                        boxes[val].style.display = 'block';
                        boxes[val].classList.add('block');
                        anchor.parentNode.insertBefore(boxes[val], anchor);
                    }
                }
                
                if (gatewaySelect) {
                    gatewaySelect.addEventListener('change', updateGatewayUI);
                    updateGatewayUI();
                }

                // TAB LOGIC
                const tabBtns = document.querySelectorAll('.tab-btn');
                const tabPanes = document.querySelectorAll('.tab-pane');

                tabBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        // Reset all
                        tabBtns.forEach(b => {
                            b.classList.remove('active', 'border-slate-900', 'text-slate-900');
                            b.classList.add('border-transparent', 'text-slate-400');
                        });
                        tabPanes.forEach(p => {
                            p.classList.add('hidden');
                            p.classList.remove('block');
                        });

                        // Set active
                        btn.classList.add('active', 'border-slate-900', 'text-slate-900');
                        btn.classList.remove('border-transparent', 'text-slate-400');
                        
                        const targetId = btn.getAttribute('data-target');
                        const pane = document.getElementById(targetId);
                        if(pane) {
                            pane.classList.remove('hidden');
                            pane.classList.add('block');
                        }
                    });
                });

                // MEDIA SELECTOR LOGIC
                const mediaBtns = document.querySelectorAll('.acro-media-selector');
                mediaBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const targetId = btn.getAttribute('data-target');
                        const input = document.getElementById(targetId);
                        
                        const frame = wp.media({
                            title: 'Selecionar Logo do Dashboard',
                            button: { text: 'Usar esta Imagem' },
                            multiple: false
                        });

                        frame.on('select', () => {
                            const attachment = frame.state().get('selection').first().toJSON();
                            input.value = attachment.url;
                            
                            // Update preview if exists
                            let preview = document.getElementById('preview_' + targetId);
                            if (!preview) {
                                const container = btn.closest('.space-y-2');
                                const helpText = container.querySelector('p.text-[11px]');
                                const previewCont = document.createElement('div');
                                previewCont.className = 'p-4 border border-dashed border-slate-200 rounded-2xl bg-slate-50 flex items-center justify-center preview-container mb-4';
                                previewCont.innerHTML = `<img src="${attachment.url}" class="max-h-16 object-contain" id="preview_${targetId}">`;
                                container.insertBefore(previewCont, helpText);
                            } else {
                                preview.src = attachment.url;
                            }
                        });

                        frame.open();
                    });
                });
            });
        </script>
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
        <?php
        $type    = isset( $config['type'] ) ? $config['type'] : 'password';
        $current = self::get( $key );
        if ( $type === 'select' && empty( $current ) ) {
            $current = array_key_first( $config['options'] );
        }
        ?>
        <div class="space-y-2">
            <div class="flex justify-between items-center px-1">
                <label for="acro_<?php echo esc_attr( $key ); ?>" class="input-label">
                    <?php echo esc_html( $config['label'] ); ?>
                </label>
                <?php if ( $has_value && $type !== 'select' ) : ?>
                    <span class="text-[9px] font-black text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 uppercase tracking-widest flex items-center gap-1">
                        <i data-lucide="check" class="w-2.5 h-2.5"></i> <?php echo esc_html( $source ); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="relative">
                <i data-lucide="<?php echo esc_attr( $icon ); ?>" class="input-icon"></i>
                
                <?php if ( $type === 'select' ) : ?>
                    <select id="acro_<?php echo esc_attr( $key ); ?>"
                            name="acro_<?php echo esc_attr( $key ); ?>"
                            class="modern-input" style="padding-left: 54px; appearance: none; padding-right: 40px; cursor: pointer;">
                        <?php foreach ( $config['options'] as $v => $l ) : ?>
                            <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $current, $v ); ?>><?php echo esc_html( $l ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                        <i data-lucide="chevron-down" class="w-5 h-5"></i>
                    </div>
                <?php else : ?>
                    <input type="<?php echo esc_attr( $type ); ?>"
                           id="acro_<?php echo esc_attr( $key ); ?>"
                           name="acro_<?php echo esc_attr( $key ); ?>"
                           <?php if ( $type === 'password' ) : ?>
                               placeholder="<?php echo esc_attr( $has_value ? '••••••••••••••••••••' : $config['placeholder'] ); ?>"
                           <?php else : ?>
                               value="<?php echo esc_attr( $current ); ?>"
                               placeholder="<?php echo esc_attr( $config['placeholder'] ?? '' ); ?>"
                           <?php endif; ?>
                           class="modern-input"
                           autocomplete="off">
                <?php endif; ?>
                
                <?php if ( $type === 'media' ) : ?>
                    <button type="button" class="acro-media-selector absolute right-2 top-1/2 -translate-y-1/2 bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border border-slate-200 transition-all font-sans" data-target="acro_<?php echo esc_attr( $key ); ?>">Mídia</button>
                <?php endif; ?>
            </div>
            
            <?php if ( $type === 'media' && $current ) : ?>
                <div class="p-4 border border-dashed border-slate-200 rounded-2xl bg-slate-50 flex items-center justify-center preview-container">
                    <img src="<?php echo esc_url( $current ); ?>" class="max-h-16 object-contain" id="preview_acro_<?php echo esc_attr( $key ); ?>">
                </div>
            <?php endif; ?>
            
            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest pl-1 mt-2 flex items-center gap-2"><i data-lucide="info" class="w-3 h-3 text-indigo-400"></i> <?php echo esc_html( $config['help'] ); ?></p>
        </div>
        <?php
    }
}


