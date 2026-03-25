<?php
namespace Acromidia_Manager\Core;

/**
 * AcroLicense Client Updater Class - Premium Edition.
 * 
 * Integração base com updates e página de Licença moderna.
 * 
 * @package AcroLicenseClient
 * @version 1.2.0
 */

if (!class_exists('\Acromidia_Manager\Core\LicenseClient')) {

    class LicenseClient
    {
        public $current_version;
        public $server_url;
        public $plugin_slug;
        public $slug;
        public $safe_slug;
        public $item_name;
        public $option_name;
        public $license_key;
        public $sdk_version = '1.2.0';
        private static $instances = array();
        public $slug_key;

        public static function instance($slug = 'acromidia-manager')
        {
            $safe_slug = str_replace('-', '_', $slug);
            return isset(self::$instances[$safe_slug]) ? self::$instances[$safe_slug] : null;
        }

        public function __construct($current_version, $server_url, $plugin_slug, $item_name)
        {
            $this->current_version = $current_version;
            $this->server_url = rtrim($server_url, '/') . '/';
            $this->plugin_slug = $plugin_slug;
            $file_parts = explode('/', $plugin_slug);
            $this->slug = $file_parts[0];
            $this->safe_slug = str_replace('-', '_', $this->slug);
            $this->slug_key = $this->safe_slug;
            $this->item_name = $item_name;
            $this->option_name = 'acro_license_' . $this->safe_slug;
            $this->license_key = sanitize_text_field(get_option($this->option_name, ''));

            self::$instances[$this->safe_slug] = $this;

            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'check_info'], 10, 3);
            add_action('admin_menu', [$this, 'add_settings_page'], 99);
            add_action('admin_init', [$this, 'register_settings']);
            add_filter('plugin_action_links_' . $this->plugin_slug, [$this, 'add_action_links']);
            add_action('admin_post_acro_activate_' . $this->slug, [$this, 'handle_activation']);
            add_action('admin_post_acro_deactivate_' . $this->slug, [$this, 'handle_deactivation']);

            // Periodic license re-validation (cached via transient, API call only every 12h)
            add_action('admin_init', [$this, 'ensure_license_status']);
        }

        public static function init_from_json($plugin_file)
        {
            $path = plugin_dir_path($plugin_file) . 'acro-license.json';
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $data = json_decode($content, true);
                if ($data) {
                    return new self(
                        isset($data['version']) ? $data['version'] : '1.0.0',
                        isset($data['server_url']) ? $data['server_url'] : '',
                        isset($data['slug']) ? $data['slug'] . '/' . basename($plugin_file) : plugin_basename($plugin_file),
                        isset($data['name']) ? $data['name'] : 'Acro Plugin'
                    );
                }
            }
            return null;
        }

        public function add_action_links($links)
        {
            $settings_link = '<a href="admin.php?page=' . $this->slug . '-license" style="font-weight:600;color:#4f46e5;">' . __('Licença', 'acrolicense') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        public function add_settings_page()
        {
            add_submenu_page(
                'acromidia-dashboard',
                'Ativação VIP',
                'Licença',
                'manage_options',
                $this->slug . '-license',
                [$this, 'render_settings_page']
            );
        }

        public function register_settings()
        {
            register_setting($this->slug . '_license_group', $this->option_name);
        }

        /**
         * Render the License Form with Premium UI.
         */
        public function render_settings_page()
        {
            $status = $this->get_license_status();
            $msg = isset($_GET['msg']) ? sanitize_text_field(urldecode($_GET['msg'])) : '';

            $is_active = $status === 'active';
            $is_expired = $status === 'expired';

            $badge_bg = $is_active ? '#ecfdf5' : ($is_expired ? '#fffbeb' : '#fef2f2');
            $badge_text = $is_active ? '#059669' : ($is_expired ? '#d97706' : '#dc2626');
            $badge_border = $is_active ? '#a7f3d0' : ($is_expired ? '#fde68a' : '#fecaca');
            $status_label = $is_active ? '✅ Licença Ativada' : ($is_expired ? '⚠️ Licença Expirada' : '❌ Não Ativada/Inválida');
            ?>
            <div class="wrap"
                style="max-width: 760px; margin: 40px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">

                <div style="display:flex; align-items:center; margin-bottom: 24px;">
                    <svg style="width: 32px; height: 32px; color: #4f46e5; margin-right: 12px;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #1e293b; letter-spacing: -0.025em;">
                        <?php echo esc_html($this->item_name); ?>
                    </h1>
                </div>

                <?php if ($msg): ?>
                    <div
                        style="background-color: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; padding: 16px; border-radius: 6px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <p style="margin:0; font-weight: 500;">
                            <?php echo $msg; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div
                    style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; overflow: hidden;">

                    <div
                        style="padding: 24px 32px; border-bottom: 1px solid #e2e8f0; background-color: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #0f172a;">Gerenciamento de Licença</h2>
                            <p style="margin: 4px 0 0; font-size: 14px; color: #64748b;">Configure o acesso às atualizações
                                exclusivas do seu sistema.</p>
                        </div>
                        <span
                            style="display: inline-block; padding: 6px 14px; border-radius: 9999px; font-size: 13px; font-weight: 600; background-color: <?php echo $badge_bg; ?>; color: <?php echo $badge_text; ?>; border: 1px solid <?php echo $badge_border; ?>;">
                            <?php echo $status_label; ?>
                        </span>
                    </div>

                    <div style="padding: 32px;">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('acro_license_action', 'acro_license_nonce'); ?>

                            <?php if ($is_active || $is_expired): ?>
                                <div style="margin-bottom: 24px;">
                                    <label
                                        style="display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px;">Chave
                                        de Autenticação Ativa</label>
                                    <input type="text"
                                        value="<?php echo esc_attr(substr($this->license_key, 0, 15) . '•••••••••••••••••'); ?>"
                                        readonly disabled
                                        style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 15px; background-color: #f1f5f9; color: #64748b; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;" />
                                    <p style="margin: 8px 0 0; font-size: 13px; color: #64748b;">Sua chave está criptografada. Lembre-se
                                        de revogá-la caso precise transferir para outro domínio.</p>
                                </div>

                                <input type="hidden" name="action" value="acro_deactivate_<?php echo esc_attr($this->slug); ?>">
                                <div style="display: flex; gap: 12px; margin-top: 32px;">
                                    <button type="submit"
                                        style="background-color: #ffffff; border: 1px solid #ef4444; color: #ef4444; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                                        onmouseover="this.style.backgroundColor='#fef2f2';"
                                        onmouseout="this.style.backgroundColor='#ffffff';">Revogar Acesso Deste Domínio</button>
                                </div>
                            <?php else: ?>
                                <div style="margin-bottom: 24px;">
                                    <label
                                        style="display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px;">Insira
                                        sua Nova Chave de Licença</label>
                                    <input type="text" name="acro_license_key" placeholder="ACRO-PROD-XXXX-XXXX-XXXX" required
                                        style="width: 100%; box-sizing:border-box; border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 15px; background-color: #ffffff; color: #0f172a; transition: border-color 0.2s;"
                                        onfocus="this.style.borderColor='#6366f1'; this.style.outline='none'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                                        onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';" />
                                    <p style="margin: 8px 0 0; font-size: 13px; color: #64748b;">Cole a chave alfanumérica recebida por
                                        e-mail no ato da contratação do software.</p>
                                </div>

                                <input type="hidden" name="action" value="acro_activate_<?php echo esc_attr($this->slug); ?>">
                                <div style="margin-top: 32px;">
                                    <button type="submit"
                                        style="background-color: #4f46e5; border: 1px solid #4338ca; color: #ffffff; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);"
                                        onmouseover="this.style.backgroundColor='#4338ca';"
                                        onmouseout="this.style.backgroundColor='#4f46e5';">
                                        Autenticar Servidor e Ativar Produto
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 24px; font-size: 13px; color: #94a3b8;">
                    Acro Manager License &copy;
                    <?php echo date('Y'); ?> AcroMidia
                </div>

            </div>
            <?php
        }

        public function handle_activation()
        {
            if (!current_user_can('manage_options') || !isset($_POST['acro_license_nonce']) || !wp_verify_nonce($_POST['acro_license_nonce'], 'acro_license_action')) {
                wp_die('Acesso negado.');
            }

            $key = sanitize_text_field($_POST['acro_license_key']);
            $this->license_key = $key;

            $response = $this->api_request('license/activate');
            $this->clear_caches(); // Clear caches regardless of result to force re-validation

            if ($response && isset($response->success) && $response->success) {
                update_option($this->option_name, $key);
                set_transient('acro_status_' . $this->safe_slug, 'active', 12 * HOUR_IN_SECONDS);
                $msg = '🎉 Software VIP autenticado! Atualizações habilitadas com sucesso.';
            } else {
                $msg = isset($response->message) ? $response->message : 'Ocorreu um erro ao comunicar com os servidores AcroMidia. Verifique a chave inserida.';
            }

            wp_redirect(admin_url('admin.php?page=' . $this->slug . '-license&msg=' . urlencode($msg)));
            exit;
        }

        public function handle_deactivation()
        {
            if (!current_user_can('manage_options') || !isset($_POST['acro_license_nonce']) || !wp_verify_nonce($_POST['acro_license_nonce'], 'acro_license_action')) {
                wp_die('Acesso negado.');
            }

            $response = $this->api_request('license/deactivate');

            delete_option($this->option_name);
            $this->license_key = '';
            set_transient('acro_status_' . $this->safe_slug, 'inactive', 12 * HOUR_IN_SECONDS);
            $this->clear_caches();

            $msg = 'Sua chave foi desconectada deste servidor com segurança e está livre para ser reativada em outro domínio.';

            wp_redirect(admin_url('admin.php?page=' . $this->slug . '-license&msg=' . urlencode($msg)));
            exit;
        }

        public function check_update($transient)
        {
            if (empty($this->license_key)) {
                return $transient;
            }

            $remote_version = $this->get_cached_update();

            if ($remote_version && isset($remote_version->new_version)) {
                if (version_compare($this->current_version, $remote_version->new_version, '<')) {
                    $obj = new \stdClass();
                    $obj->id = $this->plugin_slug;
                    $obj->slug = $this->slug;
                    $obj->plugin = $this->plugin_slug;
                    $obj->new_version = $remote_version->new_version;
                    if (!empty($remote_version->url))
                        $obj->url = $remote_version->url;
                    if (!empty($remote_version->package))
                        $obj->package = $remote_version->package;

                    $transient->response[$this->plugin_slug] = $obj;
                } else {
                    // Logic to clear our cache if we are already on the latest version
                    // This prevents "Update Failed" errors that are just cache out-of-sync
                    delete_transient('acro_update_' . $this->safe_slug);
                }
            }

            return $transient;
        }

        public function check_info($false, $action, $arg)
        {
            if (empty($arg->slug) || $arg->slug !== $this->slug) {
                return $false;
            }

            if ('plugin_information' === $action) {
                $remote_info = $this->api_request('update/info');

                if ($remote_info && isset($remote_info->data)) {
                    $info = $remote_info->data;
                    $obj = new \stdClass();
                    $obj->id = $this->plugin_slug;
                    $obj->slug = $this->slug;
                    $obj->plugin = $this->plugin_slug;
                    $obj->name = $info->name;
                    $obj->plugin_name = $info->name;
                    $obj->new_version = $info->version;
                    $obj->requires = $info->requires;
                    $obj->tested = $info->tested;
                    $obj->downloaded = 0;
                    $obj->last_updated = $info->last_updated;
                    $obj->sections = (array) $info->sections;

                    $update_data = $this->get_cached_update();
                    if ($update_data && !empty($update_data->package)) {
                        $obj->download_link = $update_data->package;
                    }

                    return $obj;
                }
            }

            return $false;
        }

        private function get_cached_update()
        {
            $cache_key = 'acro_update_' . $this->safe_slug;
            $cached = get_transient($cache_key);

            if (false !== $cached) {
                return $cached;
            }

            $response = $this->api_request('update/check');

            if ($response && isset($response->success) && $response->success) {
                set_transient($cache_key, $response->data, 3 * HOUR_IN_SECONDS); // Longer cache for positive response
                return $response->data;
            }

            error_log('[ACRONEXUS SDK] Update check FAILED or no data. Response: ' . json_encode($response));
            set_transient($cache_key, 'failed', 30 * MINUTE_IN_SECONDS);
            return false;
        }

        private function get_license_status()
        {
            if (empty($this->license_key)) {
                return 'inactive';
            }

            $cache_key = 'acro_status_' . $this->safe_slug;
            $cached = get_transient($cache_key);

            if (false !== $cached) {
                return $cached;
            }

            $response = $this->api_request('license/check', 'GET');

            // Compatibility check: server might return status at root or inside data
            // We MUST ensure the status is one of the valid strings, not an HTTP code (e.g. 403)
            $status = null;
            $valid_statuses = ['active', 'expired', 'disabled', 'inactive'];

            if ($response && isset($response->status) && in_array($response->status, $valid_statuses, true)) {
                $status = $response->status;
            } elseif ($response && isset($response->data->status) && in_array($response->data->status, $valid_statuses, true)) {
                $status = $response->data->status;
            }

            if ($status) {
                error_log('[ACRONEXUS SDK] License status found: ' . $status);
                set_transient($cache_key, $status, 12 * HOUR_IN_SECONDS);
                return $status;
            }

            // If we have a response but no valid status, it's inactive (e.g. revoked or invalid key)
            if ($response) {
                error_log('[ACRONEXUS SDK] API responded but status invalid: ' . json_encode($response));
                set_transient($cache_key, 'inactive', 12 * HOUR_IN_SECONDS);
                return 'inactive';
            }

            error_log('[ACRONEXUS SDK] API request FAILED for domain: ' . $this->get_domain());
            return 'inactive';
        }

        /**
         * Public wrapper: ensure the license status transient is populated.
         * Called via admin_init hook. Only makes API call when transient has expired.
         */
        public function ensure_license_status()
        {
            return $this->get_license_status();
        }

        /**
         * Helper to check if license is active.
         */
        public function is_active()
        {
            return $this->get_license_status() === 'active';
        }

        private function clear_caches()
        {
            delete_transient('acro_update_' . $this->safe_slug);
            delete_transient('acro_status_' . $this->safe_slug);
        }

        private function get_domain()
        {
            $domain = parse_url(home_url(), PHP_URL_HOST) ?? $_SERVER['HTTP_HOST'];
            return preg_replace('/^www\./i', '', $domain);
        }

        private function api_request($endpoint, $method = 'POST')
        {
            $url = $this->server_url . ltrim($endpoint, '/');

            $body = [
                'slug' => $this->plugin_slug,
                'version' => $this->current_version,
                'license_key' => $this->license_key,
                'domain' => $this->get_domain()
            ];

            $args = [
                'method' => $method,
                'timeout' => 15,
                'sslverify' => false, // Bypass SSL issues for licensing calls
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->license_key,
                    'X-License-Key' => $this->license_key,
                    'X-Acro-WP-Version' => get_bloginfo('version'),
                    'X-Acro-PHP-Version' => PHP_VERSION,
                    'X-Acro-SDK-Version' => $this->sdk_version,
                ],
            ];

            if ($method === 'POST') {
                $args['body'] = json_encode($body);
            } else {
                $url = add_query_arg($body, $url);
            }

            $request = wp_remote_request($url, $args);

            if (is_wp_error($request)) {
                error_log('[ACRONEXUS SDK] API Request Error: ' . $request->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($request);
            $response_body_raw = wp_remote_retrieve_body($request);
            
            if ($response_code !== 200) {
                error_log('[ACRONEXUS SDK] API Server returned code ' . $response_code . '. Body: ' . substr($response_body_raw, 0, 100));
            }

            $response_body = json_decode($response_body_raw);
            return $response_body;
        }
    }
}
