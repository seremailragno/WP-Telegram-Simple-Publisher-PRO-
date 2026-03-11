<?php
/*
Plugin Name: WP Telegram Simple Publisher PRO
Description: PRO version of Telegram Publisher. Includes advanced scheduling, category/tag filters and license control.
Version: 1.0
Author: Seremailragno Edizioni
*/

if (!defined('ABSPATH')) exit;

/* =====================================================
COSTANTI PLUGIN
===================================================== */
define('TPSP_PRO_VERSION','1.0');
define('TPSP_LICENSE_CHECK_URL','https://seremailragno.com/license-server.php');
define('TPSP_PRO_SERVER_URL','https://seremailragno.com/tpsp-pro-functions.php');
// Segreto condiviso per firmare le richieste al server PRO (deve essere identico in tpsp-pro-functions.php)
define('TPSP_PRO_HMAC_SECRET','k7!Pz2$qW9mXvL#nR4tY8sA@jE5uC0bF');

/* =====================================================
CONTROLLO LICENZA
===================================================== */
add_action('admin_init','tpsp_pro_check_license');
function tpsp_pro_check_license() {
    $license_key = get_option('tpsp_license_key');
    if(!$license_key){
        update_option('tpsp_license_status','invalid');
        return;
    }

    $domain = parse_url(get_site_url(), PHP_URL_HOST);
    $domain = str_replace('www.','', strtolower($domain));

    $response = wp_remote_get(TPSP_LICENSE_CHECK_URL.'?key='.urlencode($license_key).'&domain='.urlencode($domain));
    if(is_wp_error($response)){
        update_option('tpsp_license_status','invalid');
        add_action('admin_notices','tpsp_pro_license_notice');
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body,true);

    if(!isset($data['status']) || $data['status'] != 'valid'){
        update_option('tpsp_license_status','invalid');
        add_action('admin_notices','tpsp_pro_license_notice');
    } else {
        update_option('tpsp_license_status','valid');
        update_option('tpsp_license_expires', isset($data['expires']) ? $data['expires'] : 'lifetime');
    }
}

function tpsp_pro_license_notice(){
    echo '<div class="notice notice-error"><p><b>WP Telegram Simple Publisher PRO:</b> Invalid or expired license. Please enter a valid license key in the settings.</p></div>';
}

/* =====================================================
MENU ADMIN
===================================================== */
add_action('admin_menu', function() {
    add_options_page(
        'Telegram Publisher PRO',
        'Telegram Publisher PRO',
        'manage_options',
        'telegram-publisher-pro',
        'tpsp_pro_settings_page'
    );
});

// Aggiunge i link "Settings" e "Docs" nella pagina Plugin di WordPress
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tpsp_pro_plugin_action_links');
function tpsp_pro_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=telegram-publisher-pro') . '">&#9881;&#65039; Settings</a>';
    $docs_link     = '<a href="https://www.seremailragno.com/wp-telegram-simple-publisher/" target="_blank">&#128196; Docs</a>';
    array_unshift($links, $docs_link);
    array_unshift($links, $settings_link);
    return $links;
}

/* =====================================================
PAGINA IMPOSTAZIONI
===================================================== */
function tpsp_pro_settings_page() {
    $status = get_option('tpsp_license_status','invalid');
    $expires = get_option('tpsp_license_expires','-');
    ?>
    <div class="wrap">
        <h2>&#9992;&#65039; Telegram Publisher PRO Settings</h2>

        <?php
        // Show guide button + bilingual modal
        ?>
        <button type="button" id="tpsp-guide-btn"
            style="margin-bottom:18px; padding:7px 18px; background:#2271b1; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">
            &#128218; Quick Start Guide
        </button>

        <div id="tpsp-guide-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.55); z-index:99999; overflow-y:auto;">
          <div style="background:#fff; max-width:720px; margin:40px auto; border-radius:8px;
              padding:32px 36px; position:relative; font-size:14px; line-height:1.7;">
            <button id="tpsp-guide-close" style="position:absolute; top:12px; right:16px;
                background:none; border:none; font-size:22px; cursor:pointer; color:#555;">&times;</button>

            <!-- Language tabs -->
            <div style="display:flex; gap:10px; margin-bottom:24px;">
                <button class="tpsp-lang-btn" data-lang="en"
                    style="padding:6px 16px; border:2px solid #2271b1; border-radius:4px; background:#2271b1; color:#fff; cursor:pointer; font-size:15px;">
                    &#127468;&#127463; English
                </button>
                <button class="tpsp-lang-btn" data-lang="it"
                    style="padding:6px 16px; border:2px solid #ccc; border-radius:4px; background:#f6f7f7; color:#333; cursor:pointer; font-size:15px;">
                    &#127470;&#127481; Italiano
                </button>
            </div>

            <!-- English guide -->
            <div id="tpsp-guide-en">
                <div style="display:flex; align-items:center; gap:18px; margin-bottom:18px; padding:16px 18px; background:#f0f6fc; border-radius:8px; border:1px solid #c3d9ee;">
                    <img src="data:image/png;base64,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" alt="Seremailragno Edizioni" style="width:72px; height:72px; border-radius:50%; flex-shrink:0;">
                    <div>
                        <div style="font-size:16px; font-weight:700; color:#1a1a1a; margin-bottom:4px;">WP Telegram Simple Publisher PRO</div>
                        <div style="color:#444; margin-bottom:6px;">
                            Developed by the <strong>Seremailragno Edizioni</strong> team.<br>
                            License validation is handled securely by <a href="https://seremailragno.com" target="_blank" style="color:#2271b1;">seremailragno.com</a>.
                        </div>
                        <div style="font-size:12px; color:#777;">
                            &copy; <?php echo date('Y'); ?> Seremailragno Edizioni &mdash; All rights reserved.
                        </div>
                    </div>
                </div>
                <h3>&#128272; Creating a Telegram Bot</h3>
                <ol>
                    <li>Open Telegram and search for <strong>@BotFather</strong>.</li>
                    <li>Send <code>/newbot</code>, choose a name and username for your bot.</li>
                    <li>Copy the <strong>Bot Token</strong> provided and paste it in the <em>Bot Token</em> field.</li>
                    <li>Add the bot to your channel or group as an <strong>administrator</strong>.</li>
                    <li>Get your <strong>Chat ID</strong>: forward a message from the channel to <strong>@userinfobot</strong>, or use the Telegram API: <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code>.</li>
                    <li>Paste the Chat ID in the <em>Chat ID</em> field (channels use a negative ID like <code>-100xxxxxxxxxx</code>).</li>
                </ol>
                <h3>&#128274; PRO Features (require valid license)</h3>
                <ul>
                    <li><strong>Publish Time:</strong> delay sending until a specific time of day.</li>
                    <li><strong>Timezone:</strong> set the timezone for scheduled publishing.</li>
                    <li><strong>Resend on Update:</strong> automatically re-notify Telegram when a post is updated.</li>
                    <li><strong>Include Categories/Tags:</strong> only send posts that have at least one of these terms (comma-separated).</li>
                    <li><strong>Exclude Categories/Tags:</strong> never send posts that have any of these terms (comma-separated).</li>
                </ul>
                <h3>&#9989; Quick Test</h3>
                <p>Click <strong>Test Bot</strong> at the bottom of the page to verify that the bot token and chat ID are working correctly.</p>
            </div>

            <!-- Italian guide -->
            <div id="tpsp-guide-it" style="display:none;">
                <div style="display:flex; align-items:center; gap:18px; margin-bottom:18px; padding:16px 18px; background:#f0f6fc; border-radius:8px; border:1px solid #c3d9ee;">
                    <img src="data:image/png;base64,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" alt="Seremailragno Edizioni" style="width:72px; height:72px; border-radius:50%; flex-shrink:0;">
                    <div>
                        <div style="font-size:16px; font-weight:700; color:#1a1a1a; margin-bottom:4px;">WP Telegram Simple Publisher PRO</div>
                        <div style="color:#444; margin-bottom:6px;">
                            Sviluppato dal team di <strong>Seremailragno Edizioni</strong>.<br>
                            La validazione delle licenze è gestita in modo sicuro da <a href="https://seremailragno.com" target="_blank" style="color:#2271b1;">seremailragno.com</a>.
                        </div>
                        <div style="font-size:12px; color:#777;">
                            &copy; <?php echo date('Y'); ?> Seremailragno Edizioni &mdash; Tutti i diritti riservati.
                        </div>
                    </div>
                </div>
                <h3>&#128272; Creazione del Bot Telegram</h3>
                <ol>
                    <li>Apri Telegram e cerca <strong>@BotFather</strong>.</li>
                    <li>Invia <code>/newbot</code>, scegli nome e username per il bot.</li>
                    <li>Copia il <strong>Bot Token</strong> ricevuto e incollalo nel campo <em>Bot Token</em>.</li>
                    <li>Aggiungi il bot al tuo canale o gruppo come <strong>amministratore</strong>.</li>
                    <li>Ottieni il <strong>Chat ID</strong>: inoltra un messaggio del canale a <strong>@userinfobot</strong>, oppure usa l'API Telegram: <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code>.</li>
                    <li>Incolla il Chat ID nel campo <em>Chat ID</em> (i canali hanno un ID negativo tipo <code>-100xxxxxxxxxx</code>).</li>
                </ol>
                <h3>&#128274; Funzionalità PRO (richiedono licenza valida)</h3>
                <ul>
                    <li><strong>Publish Time:</strong> ritarda l'invio fino a un orario specifico del giorno.</li>
                    <li><strong>Timezone:</strong> imposta il fuso orario per la pubblicazione programmata.</li>
                    <li><strong>Resend on Update:</strong> notifica automaticamente Telegram quando un articolo viene aggiornato.</li>
                    <li><strong>Include Categories/Tags:</strong> invia solo i post che contengono almeno uno di questi termini (separati da virgola).</li>
                    <li><strong>Exclude Categories/Tags:</strong> non inviare mai i post che contengono uno di questi termini (separati da virgola).</li>
                </ul>
                <h3>&#9989; Test rapido</h3>
                <p>Clicca <strong>Test Bot</strong> in fondo alla pagina per verificare che il token e il Chat ID siano configurati correttamente.</p>
            </div>
          </div>
        </div>

        <script>
        (function(){
            var btn   = document.getElementById('tpsp-guide-btn');
            var modal = document.getElementById('tpsp-guide-modal');
            var close = document.getElementById('tpsp-guide-close');
            btn.addEventListener('click', function(){ modal.style.display = 'block'; });
            close.addEventListener('click', function(){ modal.style.display = 'none'; });
            modal.addEventListener('click', function(e){ if(e.target === modal) modal.style.display = 'none'; });
            document.querySelectorAll('.tpsp-lang-btn').forEach(function(b){
                b.addEventListener('click', function(){
                    var lang = this.getAttribute('data-lang');
                    document.getElementById('tpsp-guide-en').style.display = lang === 'en' ? 'block' : 'none';
                    document.getElementById('tpsp-guide-it').style.display = lang === 'it' ? 'block' : 'none';
                    document.querySelectorAll('.tpsp-lang-btn').forEach(function(x){
                        x.style.background = '#f6f7f7'; x.style.color = '#333'; x.style.borderColor = '#ccc';
                    });
                    this.style.background = '#2271b1'; this.style.color = '#fff'; this.style.borderColor = '#2271b1';
                });
            });
        })();
        </script>

        <form method="post" action="options.php">
        <?php
        settings_fields('tpsp_pro_settings_group');
        do_settings_sections('telegram-publisher-pro');
        submit_button('Save Settings');
        ?>
        </form>

        <?php if($status=='valid'): ?>
            <div style="margin-top:15px; padding:10px; border-left:4px solid green; background:#e6ffed;">
                <b>&#9989; License activated successfully</b>
                <?php if($expires!='lifetime') echo ' &mdash; Expires on: '.$expires; ?>
            </div>
        <?php else: ?>
            <div style="margin-top:15px; padding:10px; border-left:4px solid orange; background:#fff8e5;">
                <b>&#9888;&#65039; Free version active.</b><br>
                Enter a valid license key above to unlock all PRO features.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:10px;">
            <input type="hidden" name="action" value="tpsp_send_test">
            <?php submit_button('&#128172; Test Bot'); ?>
        </form>
    </div>
    <?php
}

/* =====================================================
REGISTRA IMPOSTAZIONI
===================================================== */
add_action('admin_init','tpsp_pro_register_settings');
function tpsp_pro_register_settings() {
    register_setting('tpsp_pro_settings_group','tpsp_license_key');
    register_setting('tpsp_pro_settings_group','tpsp_bot_token');
    register_setting('tpsp_pro_settings_group','tpsp_chat_id');
    register_setting('tpsp_pro_settings_group','tpsp_publish_time');
    register_setting('tpsp_pro_settings_group','tpsp_timezone');
    register_setting('tpsp_pro_settings_group','tpsp_resend_on_update');
    register_setting('tpsp_pro_settings_group','tpsp_include_terms');
    register_setting('tpsp_pro_settings_group','tpsp_exclude_terms');

    add_settings_section('tpsp_main_section','',null,'telegram-publisher-pro');

    $fields = [
        'tpsp_license_key' => 'License Key:',
        'tpsp_bot_token' => 'Bot Token:',
        'tpsp_chat_id' => 'Chat ID:',
        'tpsp_publish_time' => 'Publish Time (HH:MM):',
        'tpsp_timezone' => 'Timezone:',
        'tpsp_resend_on_update' => 'Resend on Update:',
        'tpsp_include_terms' => 'Include Categories/Tags:',
        'tpsp_exclude_terms' => 'Exclude Categories/Tags:'
    ];

    foreach($fields as $name => $label){
        add_settings_field($name,$label,function() use ($name){
            $value = get_option($name);
            $status = get_option('tpsp_license_status','invalid');

            $disabled = in_array($name,['tpsp_publish_time','tpsp_timezone','tpsp_resend_on_update','tpsp_include_terms','tpsp_exclude_terms']) && $status!='valid' ? 'disabled' : '';

            switch($name){
                case 'tpsp_resend_on_update':
                    echo '<input type="hidden" name="'.$name.'" value="0">';
                    echo '<input type="checkbox" name="'.$name.'" value="1" '.checked(1,$value,false).' '.$disabled.' />';
                    echo ' <span style="color:#555;">(enable to receive a notification on every post update)</span>';
                    if($disabled) echo '<p class="description" style="color:#b32d2e;">&#128274; PRO feature &mdash; enter a valid license key to enable this option.</p>';
                    break;
                case 'tpsp_publish_time':
                    echo '<input type="time" name="'.$name.'" value="'.esc_attr($value).'" '.$disabled.'>';
                    if($disabled) echo '<p class="description" style="color:#b32d2e;">&#128274; PRO feature &mdash; enter a valid license key to enable this option.</p>';
                    else echo '<p class="description">Leave empty to send immediately upon publishing.</p>';
                    break;
                case 'tpsp_timezone':
                    $current = get_option('tpsp_timezone', 'Europe/Rome');
                    if(!$current) $current = 'Europe/Rome';
                    echo '<select name="tpsp_timezone" '.$disabled.'>';
                    foreach(timezone_identifiers_list() as $tz){
                        try {
                            $dt     = new DateTime('now', new DateTimeZone($tz));
                            $offset = $dt->getOffset(); // secondi
                            $hours  = intdiv(abs($offset), 3600);
                            $mins   = (abs($offset) % 3600) / 60;
                            $sign   = $offset >= 0 ? '+' : '-';
                            $gmt    = $mins > 0
                                ? sprintf('GMT%s%d:%02d', $sign, $hours, $mins)
                                : sprintf('GMT%s%d', $sign, $hours);
                            $label  = $tz . ' (' . $gmt . ')';
                        } catch(Exception $e){
                            $label = $tz;
                        }
                        echo '<option value="'.esc_attr($tz).'" '.selected($current,$tz,false).'>'.esc_html($label).'</option>';
                    }
                    echo '</select>';
                    if($disabled) echo '<p class="description" style="color:#b32d2e;">&#128274; PRO feature &mdash; enter a valid license key to enable this option.</p>';
                    break;
                case 'tpsp_include_terms':
                case 'tpsp_exclude_terms':
                    echo '<input type="text" name="'.$name.'" value="'.esc_attr($value).'" size="50" '.$disabled.'>';
                    if($disabled) echo '<p class="description" style="color:#b32d2e;">&#128274; PRO feature &mdash; enter a valid license key to enable filters.</p>';
                    else echo '<p class="description">Separate multiple values with a comma. Example: economy, finance</p>';
                    break;
                default:
                    echo '<input type="text" name="'.$name.'" value="'.esc_attr($value).'" size="50">';
            }
        },'telegram-publisher-pro','tpsp_main_section');
    }
}

/* =====================================================
FUNZIONI TELEGRAM BASE
===================================================== */
function tpsp_pro_send_to_telegram($message){
    $token = get_option('tpsp_bot_token');
    $chat_id = get_option('tpsp_chat_id');
    if(!$token || !$chat_id) return;

    wp_remote_post("https://api.telegram.org/bot{$token}/sendMessage",[
        'timeout'=>15,
        'body'=>[
            'chat_id'=>$chat_id,
            'text'=>$message,
            'parse_mode'=>'HTML'
        ]
    ]);
}

/* =====================================================
GESTIONE PUBBLICAZIONE
===================================================== */
add_action('transition_post_status','tpsp_handle_post_publish',10,3);
function tpsp_handle_post_publish($new_status,$old_status,$post){
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if($post->post_type!='post') return;
    $post_id=$post->ID;
    if(wp_is_post_revision($post_id)) return;

    $status = get_option('tpsp_license_status','invalid');

    if($new_status=='publish' && $old_status!='publish'){
        // Lock anti-duplicati: se già inviato negli ultimi 10 secondi, salta
        if(get_transient('tpsp_sending_'.$post_id)) return;
        set_transient('tpsp_sending_'.$post_id, 1, 10);

        if($status=='valid'){
            tpsp_pro_schedule_or_send($post_id);
        } else {
            tpsp_process_post($post_id);
        }
    }

    if($new_status=='publish' && $old_status=='publish'){
        if($status=='valid' && get_option('tpsp_resend_on_update')) {
            // Lock anti-duplicati separato per gli aggiornamenti
            if(get_transient('tpsp_updating_'.$post_id)) return;
            set_transient('tpsp_updating_'.$post_id, 1, 10);

            delete_post_meta($post_id, '_tpsp_last_send');
            tpsp_pro_schedule_or_send($post_id);
        }
    }
}

/* =====================================================
PIANIFICAZIONE O INVIO IMMEDIATO (PRO)
===================================================== */
function tpsp_pro_schedule_or_send($post_id){
    $publish_time = get_option('tpsp_publish_time', '');
    $timezone_str = get_option('tpsp_timezone', 'Europe/Rome');

    if(!$publish_time){
        // Nessun orario impostato: invio immediato
        tpsp_pro_send_post_to_server($post_id);
        return;
    }

    try {
        $tz     = new DateTimeZone($timezone_str);
        $now    = new DateTime('now', $tz);
        $target = new DateTime($now->format('Y-m-d') . ' ' . $publish_time . ':00', $tz);

        // Regola: se l'orario di oggi è già passato (anche di soli secondi), programma per domani.
        // Esempio: pubblico alle 21:32 con orario 21:30 → notifica domani alle 21:30.
        // Esempio: pubblico alle 09:00 con orario 21:30 → notifica oggi alle 21:30.
        if($target->getTimestamp() <= $now->getTimestamp()){
            $target->modify('+1 day');
        }

        $delay = $target->getTimestamp() - time();

        // Rimuove eventuali invii già pianificati per questo post
        wp_clear_scheduled_hook('tpsp_scheduled_send', array($post_id));

        // Pianifica l'invio tramite WP-Cron
        // wp_schedule_single_event() restituisce true in caso di successo, false se già esiste,
        // oppure WP_Error in caso di errore reale (WP 5.1+)
        $scheduled = wp_schedule_single_event(time() + $delay, 'tpsp_scheduled_send', array($post_id));

        if(is_wp_error($scheduled)){
            error_log("TPSP PRO: scheduling error for post_id {$post_id}: " . $scheduled->get_error_message());
            tpsp_pro_send_post_to_server($post_id);
        } elseif($scheduled === false){
            // Evento già in coda: non è un errore, l'invio è già pianificato
            error_log("TPSP PRO: post_id {$post_id} already scheduled, skipping duplicate.");
        } else {
            error_log("TPSP PRO: post_id {$post_id} scheduled at " . $target->format('Y-m-d H:i:s T') . " (delay: {$delay}s)");
            update_post_meta($post_id, '_tpsp_scheduled_at', $target->format('Y-m-d H:i:s T'));
        }

    } catch(Exception $e){
        // Timezone non valida: invio immediato come fallback
        error_log("TPSP PRO: invalid timezone '{$timezone_str}', sending immediately. " . $e->getMessage());
        tpsp_pro_send_post_to_server($post_id);
    }
}

// Hook eseguito dal cron di WordPress all'orario pianificato
add_action('tpsp_scheduled_send', 'tpsp_pro_send_post_to_server');

/* =====================================================
FUNZIONE CHE CHIAMA IL SERVER PRO
===================================================== */
function tpsp_pro_send_post_to_server($post_id){
    $license_key   = get_option('tpsp_license_key');
    $token         = get_option('tpsp_bot_token');
    $chat_id       = get_option('tpsp_chat_id');
    $include_terms = get_option('tpsp_include_terms', '');
    $exclude_terms = get_option('tpsp_exclude_terms', '');

    $title      = get_the_title($post_id);
    $link       = get_permalink($post_id);
    $excerpt    = wp_trim_words(wp_strip_all_tags(get_the_excerpt($post_id)), 30);
    $image      = get_the_post_thumbnail_url($post_id, 'large');

    // Categorie e tag del post passati come JSON al server
    // La logica di filtraggio viene applicata server-side
    $post_cats  = wp_get_post_categories($post_id, array('fields' => 'names'));
    $post_tags  = wp_get_post_tags($post_id, array('fields' => 'names'));
    $post_terms = json_encode(array_values(array_merge($post_cats, $post_tags)));

    // Firma HMAC: il server rifiuta richieste non firmate con il segreto condiviso
    $payload   = $license_key . '|' . $link . '|' . $post_terms;
    $signature = hash_hmac('sha256', $payload, TPSP_PRO_HMAC_SECRET);

    $response = wp_remote_post(TPSP_PRO_SERVER_URL, array(
        'timeout' => 15,
        'body'    => array(
            'license_key'   => $license_key,
            'bot_token'     => $token,
            'chat_id'       => $chat_id,
            'title'         => $title,
            'link'          => $link,
            'excerpt'       => $excerpt,
            'image'         => $image ? $image : '',
            'post_terms'    => $post_terms,
            'include_terms' => $include_terms,
            'exclude_terms' => $exclude_terms,
            'signature'     => $signature,
        )
    ));

    if (is_wp_error($response)) {
        error_log("TPSP PRO SEND ERROR: post_id {$post_id}, " . $response->get_error_message());
    } else {
        error_log("TPSP PRO SEND: post_id {$post_id}, response: " . wp_remote_retrieve_body($response));
    }
}

/* =====================================================
INVIO BASE (senza server PRO)
===================================================== */
function tpsp_process_post($post_id){
    $last = get_post_meta($post_id,'_tpsp_last_send',true);
    if($last && (time()-$last)<60) return;
    update_post_meta($post_id,'_tpsp_last_send',time());

    $title = get_the_title($post_id);
    $link = get_permalink($post_id);
    $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($post_id)),30);
    $message = "<b>{$title}</b>\n\n{$excerpt}\n\nLeggi qui: {$link}";

    tpsp_pro_send_to_telegram($message);
}

/* =====================================================
TEST BOT (sempre visibile)
===================================================== */
add_action('admin_post_tpsp_send_test',function(){
    if(!current_user_can('manage_options')) return;
    tpsp_pro_send_to_telegram("✅ Test successful! Bot connected correctly.");
    wp_redirect(admin_url('options-general.php?page=telegram-publisher-pro&test=sent'));
    exit;
});