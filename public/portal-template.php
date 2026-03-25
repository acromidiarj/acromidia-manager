<?php
/**
 * Template para o Portal do Cliente Acro Manager
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$uuid = sanitize_text_field( get_query_var( 'acro_id' ) ?: ( $_GET['acro_id'] ?? '' ) );
$rest_url = trailingslashit( rest_url( 'acromidia/v1' ) );
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Cliente | Acro Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; }
        .card-glass { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05); }
        .badge { padding: 4px 12px; border-radius: 10px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .badge-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-warning { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
        .badge-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
        [v-cloak] { display: none; }
    </style>
</head>
<body class="antialiased">
    <div id="portal-app" v-cloak class="min-h-screen pb-20">
        
        <!-- Loading State -->
        <div v-if="loading" class="fixed inset-0 flex items-center justify-center bg-white z-50">
            <div class="text-center">
                <div class="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <p class="font-black text-xs uppercase tracking-widest text-slate-400">Carregando Portal Seguro...</p>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="max-w-md mx-auto mt-32 px-8 text-center">
            <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-lg shadow-rose-100">
                <i data-lucide="shield-alert" class="w-10 h-10"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tighter mb-4">Acesso Negado</h1>
            <p class="text-slate-500 font-medium leading-relaxed mb-10">O link de acesso que você utilizou é inválido ou expirou. Por favor, solicite um novo link ao suporte.</p>
            <div class="h-px bg-slate-100 w-full mb-10"></div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">Acro Manager Security Protocol</p>
        </div>

        <!-- Portal View -->
        <template v-else>
            <!-- Header -->
            <header class="bg-white/80 backdrop-blur-md border-b sticky top-0 z-40 px-6 py-4">
                <div class="max-w-5xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl overflow-hidden shadow-md flex items-center justify-center bg-slate-900">
                            <img v-if="branding.logo" :src="branding.logo" class="w-full h-full object-contain p-1">
                            <i v-else data-lucide="aperture" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 class="font-black text-slate-900 tracking-tighter text-lg leading-none">Área do Cliente</h2>
                            <p class="text-[9px] font-black uppercase text-indigo-600 tracking-widest mt-1">Sua conta digital</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="max-w-5xl mx-auto px-6 pt-10">
                <!-- Welcome Section -->
                <div class="mb-12">
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Olá, {{ client.name }}</h1>
                    <p class="text-slate-500 font-medium mt-1">Acompanhe seus serviços e mantenha seus pagamentos em dia.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                    <!-- Status Card -->
                    <div class="card-glass p-8 border-none bg-gradient-to-br from-slate-900 to-slate-800 text-white relative overflow-hidden" style="background: linear-gradient(to bottom right, #0f172a, #1e293b) !important;">
                        <i data-lucide="activity" class="absolute -right-10 -bottom-10 w-48 h-48 text-white/5 rotate-12"></i>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4 block">Situação Atual</span>
                            <div class="flex items-center gap-3 mb-6">
                                <span v-if="client.status === 'ativo'" class="bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full shadow-[0_0_20px_rgba(16,185,129,0.3)]">Conta Ativa</span>
                                <span v-else class="bg-rose-500 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full shadow-[0_0_20px_rgba(244,63,94,0.3)]">Pendência Financeira</span>
                            </div>
                            <div class="h-px bg-white/10 w-full mb-6"></div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Recorrência Mensal</p>
                            <p class="text-4xl font-black tracking-tighter">R$ {{ formatMoney(client.mrr) }}</p>
                        </div>
                    </div>

                    <!-- Service Card -->
                    <div class="card-glass p-8 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4 block">Pacote Contratado</span>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ client.product || 'Gestão de Serviços Digitais' }}</h3>
                            <div class="flex items-center gap-2 mt-4 text-slate-400 font-bold text-sm">
                                <i data-lucide="globe" class="w-4 h-4"></i>
                                <span class="truncate">{{ client.site_url || 'Nenhum site vinculado' }}</span>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Início do Ciclo</p>
                                <p class="text-xs font-black text-slate-900">Assinatura Ativa</p>
                            </div>
                            <i data-lucide="check-circle" class="w-6 h-6 text-emerald-500"></i>
                        </div>
                    </div>

                    <!-- Rapid Help Card -->
                    <div class="card-glass p-8 bg-indigo-50 border-indigo-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-black uppercase text-indigo-600 tracking-widest mb-4 block">Precisa de Ajuda?</span>
                            <p class="text-sm font-medium text-slate-600 leading-relaxed mb-6">Nossa equipe está disponível via WhatsApp para suporte técnico ou financeiro.</p>
                        </div>
                        <a :href="'https://wa.me/<?php echo preg_replace('/\D/', '', Acromidia_Settings::get('wa_contact_phone')); ?>?text=' + encodeURIComponent('Olá! Sou ' + client.name + ' e estou no Portal do Cliente acompanhando meus serviços. Preciso de suporte.')" target="_blank" class="w-full py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                            <i data-lucide="message-circle" class="w-4 h-4"></i> Abrir Ticket
                        </a>
                    </div>
                </div>

                <!-- Invoices -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Histórico Financeiro</h2>
                        <p class="text-slate-500 font-medium text-sm">Segunda via de boletos, comprovantes e faturas em aberto.</p>
                    </div>
                </div>

                <div class="card-glass overflow-hidden">
                    <div v-if="!invoices.length" class="p-20 text-center flex flex-col items-center">
                        <div class="w-16 h-16 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mb-4"><i data-lucide="receipt" class="w-8 h-8"></i></div>
                        <p class="text-slate-400 font-black text-xs uppercase tracking-widest">Nenhuma fatura encontrada no histórico.</p>
                    </div>
                    <div v-else class="divide-y divide-slate-100">
                        <div v-for="inv in invoices" :key="inv.id" class="p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between hover:bg-slate-50/50 transition-all gap-4">
                            <div class="flex items-center gap-6">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm" :class="inv.status==='RECEIVED' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                                    <i :data-lucide="inv.status==='RECEIVED' ? 'check' : 'clock'" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 text-xl tracking-tighter">R$ {{ formatMoney(inv.value) }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Vencimento: <span class="text-slate-600 font-black">{{ formatDate(inv.dueDate) }}</span></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="inv.status==='RECEIVED'" class="badge badge-success px-4 py-2">PAGO</span>
                                <span v-else-if="inv.status==='OVERDUE'" class="badge badge-danger px-4 py-2">VENCIDO</span>
                                <span v-else class="badge badge-warning px-4 py-2">PENDENTE</span>
                                
                                <a v-if="inv.invoiceUrl" :href="inv.invoiceUrl" target="_blank" class="px-6 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-slate-200 hover:bg-indigo-600 transition-all flex items-center gap-2">
                                   <i data-lucide="external-link" class="w-4 h-4"></i> {{ inv.status==='RECEIVED' ? 'Recibo' : 'Pagar Agora' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="mt-20 border-t border-slate-100 py-10 text-center">
                <p class="text-[10px] font-black uppercase text-slate-300 tracking-[0.3em]">Ambiente Seguro & Criptografado</p>
                <p class="text-[9px] font-black uppercase text-slate-300 tracking-widest mt-2">Powered by Acro Manager Elite v4.1.0</p>
            </footer>
        </template>
    </div>

    <script>
        const { createApp, ref, onMounted } = Vue;

        createApp({
            setup() {
                const loading = ref(true);
                const error = ref(false);
                const client = ref({});
                const invoices = ref([]);
                const branding = ref({});
                const uuid = '<?php echo esc_js( $uuid ); ?>';

                const fetchData = async () => {
                    if (!uuid) {
                        error.value = true;
                        loading.value = false;
                        return;
                    }
                    try {
                        const res = await fetch(`<?php echo $rest_url; ?>portal/${uuid}`);
                        if (!res.ok) throw new Error();
                        const data = await res.json();
                        client.value = data.client;
                        invoices.value = data.invoices;
                        branding.value = data.branding;
                    } catch (err) {
                        console.error('Portal Fetch Error:', err);
                        error.value = true;
                    } finally {
                        loading.value = false;
                        setTimeout(() => lucide.createIcons(), 100);
                    }
                };

                const formatMoney = (val) => {
                    return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                };

                const formatDate = (dateStr) => {
                    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR');
                };

                onMounted(fetchData);

                return { loading, error, client, invoices, branding, formatMoney, formatDate };
            }
        }).mount('#portal-app');
    </script>
</body>
</html>
