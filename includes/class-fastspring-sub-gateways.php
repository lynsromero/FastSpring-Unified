<?php
/**
 * FastSpring Split Gateways Logic Module
 * Part of the FastSpring Unified Plugin
 */

if (!defined('ABSPATH')) {
  exit;
}
define('FS_SPLIT_GATEWAY_URL', plugin_dir_url(dirname(__FILE__)));

add_action('plugins_loaded', function () {
  if (!class_exists('WC_Gateway_FastSpring')) {
    return;
  }
  class WC_FS_Manual_Gateway extends WC_Gateway_FastSpring
  {
    public $default_title = '';
    public $default_icon;

    public function __construct()
    {
      $stored_id = $this->id;
      $stored_method_title = $this->method_title;
      $stored_default_title = isset($this->default_title) ? $this->default_title : '';
      $stored_method_desc = isset($this->method_description) ? $this->method_description : '';


      $this->has_fields = true;
      parent::__construct();

      if (!empty($stored_id)) {
        $this->id = $stored_id;
      }
      if (!empty($stored_method_title)) {
        $this->method_title = $stored_method_title;
      }
      if (!empty($stored_default_title)) {
        $this->default_title = $stored_default_title;
      }
      $this->method_description = $stored_method_desc;

      $this->init_form_fields();
      $this->init_settings();

      // 1. Load the saved status from the database
      $saved_settings = get_option('woocommerce_' . $this->id . '_settings', array());
      $is_already_enabled = (isset($saved_settings['enabled']) && $saved_settings['enabled'] === 'yes');

      // 2. Check if we are currently SAVING this specific gateway
      $is_saving_this_gateway = isset($_POST['woocommerce_' . $this->id . '_enabled']);
      $current_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

      if ($current_section === $this->id || $is_saving_this_gateway) {
        // We are actually editing/saving this specific gateway, so load the new value
        $this->enabled = $this->get_option('enabled', 'no');
      } else {
        // We are saving the MAIN settings or something else. 
        // DO NOT let it disable; keep the status from the database.
        $this->enabled = $is_already_enabled ? 'yes' : 'no';
      }

      // Fallback for title
      $this->title = $this->get_option('title');
      if (empty($this->title)) {
        $this->title = !empty($stored_default_title) ? $stored_default_title : $this->default_title;
      }

      $this->description = $this->get_option('description', '');
      $this->icon = $this->get_option('icon', $this->default_icon);

      // 3. ONLY hook the save action if we are actually in this section
      if ($current_section === $this->id) {
        add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));
      }
    }

    public function process_admin_options()
    {
      $current_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

      // Only save if the URL section matches this gateway ID
      if ($current_section === $this->id) {
        parent::process_admin_options();
      }
    }
    // Required for the WooCommerce generic admin UI to output fields
    public function admin_options()
    {
      echo '<h2>' . esc_html($this->method_title) . '</h2>';
      echo '<table class="form-table">';
      $this->generate_settings_html();
      echo '</table>';
    }

    public function init_form_fields()
    {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __('Enable/Disable', 'woocommerce'),
          'label' => __('Enable this gateway', 'woocommerce'),
          'type' => 'checkbox',
          'default' => 'no'
        ),
        'title' => array(
          'title' => __('Title', 'woocommerce'),
          'type' => 'text',
          'default' => $this->default_title,
        ),
        'description' => array(
          'title' => __('Description', 'woocommerce'),
          'type' => 'textarea',
          'default' => '',
        ),
        'icon' => array(
          'title' => __('Icon', 'woocommerce'),
          'type' => 'icon_upload',
          'description' => __('Upload a custom icon for this gateway.', 'woocommerce'),
          'default' => $this->default_icon,
        ),
      );
    }

    /**
     * Custom field type for WooCommerce settings to render an image uploader
     */
    public function generate_icon_upload_html($key, $data)
    {
      $field_key = $this->get_field_key($key);
      $value = $this->get_option($key);
      wp_enqueue_media();

      ob_start();
      ?>
      <tr valign="top">
        <th scope="row" class="titledesc">
          <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
        </th>
        <td class="forminp">
          <input class="input-text regular-input" type="text" name="<?php echo esc_attr($field_key); ?>"
            id="<?php echo esc_attr($field_key); ?>" style="width: 300px; margin-right: 10px;"
            value="<?php echo esc_attr(is_array($value) ? '' : $value); ?>" />
          <button type="button" class="button fs_upload_btn" data-target="#<?php echo esc_attr($field_key); ?>">Upload
            Icon</button>
          <script type="text/javascript">
            jQuery(document).ready(function ($) {
              $('.fs_upload_btn').click(function (e) {
                e.preventDefault();
                var btn = $(this);
                var custom_uploader = wp.media({ title: 'Select Icon', button: { text: 'Use this image' }, multiple: false })
                  .on('select', function () {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $(btn.data('target')).val(attachment.url);
                  }).open();
              });
            });
          </script>
        </td>
      </tr>
      <?php
      return ob_get_clean();
    }

    public function is_available()
    {
      $parent_settings = get_option('woocommerce_fastspring_settings', array());
      return $this->enabled === 'yes' && !empty($parent_settings['access_key']);
    }

    public function get_icon()
    {
      $icon_html = '';
      $icons_folder_url = FS_SPLIT_GATEWAY_URL . 'assets/icons/';

      if (is_array($this->icon)) {
        $icon_html .= '<span class="fs-card-inline-icons-container">';
        foreach ($this->icon as $card) {
          $icon_url = $icons_folder_url . strtolower($card) . '.svg';
          // Wrap each image in a 'box' class
          $icon_html .= '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($card) . '" class="fs-inline-icon" />';
        }
        $icon_html .= '</span>';
      } elseif (!empty($this->icon) && is_string($this->icon)) {
        // For PayPal/Amazon, we usually just need the icon itself
        $icon_html = '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr($this->title) . '" class="fastspring-icon" />';
      }

      return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }


    public function payment_fields()
    {
      if ($this->description)
        echo wpautop(wptexturize($this->description));
    }

    public function process_payment($order_id)
    {
      // The parent fastspring gateway uses a static method builder or looks at $_POST.
      // But FastSpring needs to know this was a 'fastspring' method payment for webhooks.
      $order = wc_get_order($order_id);

      // Temporarily set the payment method back to the main fastspring class
      // so when the webhook fires it knows how to handle it
      $order->set_payment_method('fastspring');
      $order->save();

      // Call the parent process_payment to generate the secure payload
      return parent::process_payment($order_id);
    }

    /**
     * Override    so we forcefully enqueue the core 'fastspring' library
     * even if the parent gateway is explicitly disabled.
     */
    public function payment_scripts()
    {
      // 1. Basic check
      if (!is_checkout() && !is_add_payment_method_page() && !isset($_GET['pay_for_order'])) {
        return;
      }

      // 2. Enqueue Style (Use filemtime to force refresh cache)
      $style_path = plugin_dir_path(dirname(__FILE__)) . 'assets/css/style.css';
      $style_url = plugin_dir_url(dirname(__FILE__)) . 'assets/css/style.css';

      wp_enqueue_style(
        'fs-split-gateways-style',
        $style_url,
        array(),
        file_exists($style_path) ? filemtime($style_path) : '1.0.0'
      );

      // 3. FIX THE FATAL ERROR: 
      // Check if the constant exists; if not, use the direct URL to FastSpring
      $fs_script_url = defined('WC_FASTSPRING_SCRIPT') ? WC_FASTSPRING_SCRIPT : 'https://d1f8f9xcsvx3ha.cloudfront.net/sbl/0.0.7/fastspring-builder.min.js';

      wp_enqueue_script('fastspring', $fs_script_url, array('jquery'), null, true);
    }
  }

  class WC_Gateway_FastSpring_PayPal extends WC_FS_Manual_Gateway
  {
    public function __construct()
    {
      $this->id = 'fastspring_paypal';
      $this->method_title = 'FS: PayPal'; // This is what shows in the Admin List
      $this->default_title = 'PayPal';    // Default for the User
      $this->default_icon = FS_SPLIT_GATEWAY_URL . 'assets/icons/paypal.svg';

      parent::__construct();

      // After parent loads settings, if the user hasn't saved a custom title, use default
      if (empty($this->title)) {
        $this->title = $this->default_title;
      }
    }
  }
  class WC_Gateway_FastSpring_CreditCard extends WC_FS_Manual_Gateway
  {
    public function __construct()
    {
      $this->id = 'fastspring_card';
      $this->method_title = 'FS: Credit Card';
      $this->default_title = 'Credit Card';
      $this->default_icon = array('visa', 'mastercard');

      parent::__construct();

      if (empty($this->title)) {
        $this->title = $this->default_title;
      }
    }
    public function init_form_fields()
    {
      parent::init_form_fields();

      $this->form_fields['icon'] = array(
        'title' => __('Credit Card Icons', 'woocommerce'),
        'type' => 'multiselect',
        'class' => 'wc-enhanced-select',
        'description' => __('Select which card logos to display.', 'woocommerce'),
        'options' => array(
          'amex' => 'Amex',
          'discover' => 'Discover',
          'visa' => 'Visa',
          'mastercard' => 'MasterCard',
          'unionpay' => 'Union Pay',
          'maestro' => 'Maestro',
          'jcb' => 'JCB',
          'dinersclub' => 'Diners Club',
        ),
        'default' => array('visa', 'mastercard'),
      );
    }
  }

  class WC_Gateway_FastSpring_Amazon extends WC_FS_Manual_Gateway
  {
    public function __construct()
    {
      $this->id = 'fastspring_amazon';
      $this->method_title = 'FS: Amazon Pay';
      $this->default_title = 'Amazon Pay';
      $this->default_icon = FS_SPLIT_GATEWAY_URL . 'assets/icons/amazon-pay.svg';

      parent::__construct();

      if (empty($this->title)) {
        $this->title = $this->default_title;
      }
    }
  }

  class WC_Gateway_FastSpring_Wire extends WC_FS_Manual_Gateway
  {
    public function __construct()
    {
      $this->id = 'fastspring_wire';
      $this->method_title = 'FS: Wire Transfer';
      $this->default_title = 'Wire Transfer';
      $this->default_icon = FS_SPLIT_GATEWAY_URL . 'assets/icons/wire-transfer.svg';

      parent::__construct();

      if (empty($this->title)) {
        $this->title = $this->default_title;
      }
    }
  }

  class WC_Gateway_FastSpring_GooglePay extends WC_FS_Manual_Gateway
  {
    public function __construct()
    {
      $this->id = 'fastspring_googlepay';
      $this->method_title = 'FS: Google Pay';
      $this->default_title = 'Google Pay';
      $this->default_icon = FS_SPLIT_GATEWAY_URL . 'assets/icons/google-pay.svg';

      parent::__construct();

      if (empty($this->title)) {
        $this->title = $this->default_title;
      }
    }
  }

  // --- WOOCOMMERCE BLOCKS INTEGRATION ---
  // Now that the classes are defined, we can define the blocks integration
  if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {

    class FS_Blocks_Integration extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
    {
      private $gateway;

      public function __construct($gateway)
      {
        $this->gateway = $gateway;
      }

      public function initialize()
      {
        $this->settings = get_option("woocommerce_{$this->gateway->id}_settings", []);
      }

      public function get_name()
      {
        return $this->gateway->id;
      }

      public function is_active()
      {
        return $this->gateway->is_available();
      }

      public function get_payment_method_script_handles()
      {
        wp_register_script(
          "fs-blocks-{$this->gateway->id}",
          '',
          ['wc-blocks-registry', 'wp-element', 'wp-html-entities', 'wp-i18n'],
          '1.0',
          true
        );

        $script = "
                const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
                const { decodeEntities } = window.wp.htmlEntities;
                
                const Label = (props) => {
                    const { PaymentMethodLabel } = props.components;
                    return window.wp.element.createElement(PaymentMethodLabel, { text: '" . esc_js($this->gateway->title) . "' });
                };

                const Content = () => {
                    return window.wp.element.createElement('div', null, decodeEntities('" . esc_js($this->gateway->description) . "'));
                };

                registerPaymentMethod({
                    name: '" . esc_js($this->gateway->id) . "',
                    label: window.wp.element.createElement(Label, null),
                    content: window.wp.element.createElement(Content, null),
                    edit: window.wp.element.createElement(Content, null),
                    canMakePayment: () => true,
                    ariaLabel: '" . esc_js($this->gateway->title) . "',
                    supports: {
                        features: ['products']
                    }
                });
            ";
        wp_add_inline_script("fs-blocks-{$this->gateway->id}", $script);
        return ["fs-blocks-{$this->gateway->id}"];
      }

      public function get_payment_method_data()
      {
        return [
          'title' => $this->gateway->title,
          'description' => $this->gateway->description,
        ];
      }
    }

    // Hook into the block registration
    add_action('woocommerce_blocks_payment_method_type_registration', function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
      $payment_method_registry->register(new FS_Blocks_Integration(new WC_Gateway_FastSpring_PayPal()));
      $payment_method_registry->register(new FS_Blocks_Integration(new WC_Gateway_FastSpring_CreditCard()));
      $payment_method_registry->register(new FS_Blocks_Integration(new WC_Gateway_FastSpring_Amazon()));
      $payment_method_registry->register(new FS_Blocks_Integration(new WC_Gateway_FastSpring_Wire()));
      $payment_method_registry->register(new FS_Blocks_Integration(new WC_Gateway_FastSpring_GooglePay()));
    });
  }

}, 11);

// 2. REGISTER THE GATEWAYS (with Sorting Hook)
add_filter('woocommerce_payment_gateways', 'woocommerce_payment_gateways_order');
function woocommerce_payment_gateways_order($gateways)
{
  $fastspring_gateways = array(
    'fastspring_paypal' => 'WC_Gateway_FastSpring_PayPal',
    'fastspring_card' => 'WC_Gateway_FastSpring_CreditCard',
    'fastspring_amazon' => 'WC_Gateway_FastSpring_Amazon',
    'fastspring_wire' => 'WC_Gateway_FastSpring_Wire',
    'fastspring_googlepay' => 'WC_Gateway_FastSpring_GooglePay',
  );

  $ordering = (array) get_option('woocommerce_gateway_order');

  uksort($fastspring_gateways, function ($a, $b) use ($ordering) {
    $pos_a = isset($ordering[$a]) && is_numeric($ordering[$a]) ? (int) $ordering[$a] : 999;
    $pos_b = isset($ordering[$b]) && is_numeric($ordering[$b]) ? (int) $ordering[$b] : 999;
    return $pos_a - $pos_b;
  });

  foreach ($fastspring_gateways as $class) {
    $gateways[] = $class;
  }

  return $gateways;
}

// 3. HIDE THE MAIN "OPTION 1" FROM CHECKOUT
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
  if (isset($gateways['fastspring'])) {
    unset($gateways['fastspring']);
  }
  return $gateways;
}, 99);

// 4. ADMIN TABS VISIBILITY
add_filter('woocommerce_get_sections_checkout', function ($sections) {
  $sections['fastspring_paypal'] = __('FS: PayPal', 'woocommerce');
  $sections['fastspring_card'] = __('FS: Credit Card', 'woocommerce');
  $sections['fastspring_amazon'] = __('FS: Amazon Pay', 'woocommerce');
  $sections['fastspring_wire'] = __('FS: Wire Transfer', 'woocommerce');
  $sections['fastspring_googlepay'] = __('FS: Google Pay', 'woocommerce');
  return $sections;
}, 999);

// 5. JS OVERRIDE FOR SHORTCODE CHECKOUT
add_action('wp_enqueue_scripts', function () {
  // Wait until the parent script is enqueued, then override it to bypass its minified functions
  if (wp_script_is('woocommerce_fastspring', 'enqueued') || wp_script_is('woocommerce_fastspring', 'registered')) {

    // Remove the parent's enqueue completely
    wp_dequeue_script('woocommerce_fastspring');
    wp_deregister_script('woocommerce_fastspring');

    // Re-add our customized version under a new handle so we can cleanly localize it
    wp_register_script('woocommerce_fastspring_custom', '', ['jquery', 'fastspring'], '1.2.2', true);

    // Re-inject the localized parameters exactly as the parent plugin normally does it
    $fastspring_params = array(
      'ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
      'nonce' => array(
        'receipt' => wp_create_nonce('wc-fastspring-receipt'),
      ),
    );
    wp_localize_script('woocommerce_fastspring_custom', 'woocommerce_fastspring_params', apply_filters('woocommerce_fastspring_params', $fastspring_params));

    // Re-add our customized Javascript codebase
    $script = "
          /* Customized FastSpring Checkout JS for Split Gateways */
          var checkoutForm = jQuery('form.checkout'); 
          function setLoadingDone() { checkoutForm.removeClass('processing').unblock(); } 
          function setLoadingOn() { checkoutForm.addClass('processing').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}}); } 
          function getAjaxURL(e) { return woocommerce_fastspring_params.ajax_url.toString().replace('%%endpoint%%', 'wc_fastspring_' + e); } 
          
          window.fastspringBeforeRequestHandler = function() { setLoadingDone(); };
          window.dataCallbackFunction = function(data) { console.log('FastSpring Data:', data); };
          window.errorCallback = function(code, string) { 
              console.error('FastSpring Error: ', code, string);
              submitError('FastSpring API Error: ' + string + ' (' + code + ')');
          };
          window.fastspringPopupCloseHandler = function(e) { 
              if (e && e.reference) {
                  window.requestPaymentCompletionUrl(e || {}, function(err, o) { if (!err) { window.location = o.redirect_url; } }); 
              }
          }; 
          window.requestPaymentCompletionUrl = function(e, o) { 
              e.security = woocommerce_fastspring_params.nonce.receipt;
              jQuery.ajax({type: 'POST', dataType: 'json', data: JSON.stringify(e), url: getAjaxURL('get_receipt'), success: function(e) { o(null, e); }, error: function(e, r, t) { o(e.responseText); }}); 
          }; 
          function launchFastSpring(e) { 
              console.log('Pushing secure payload to FastSpring: ', e);
              
              var methodVal = jQuery('.woocommerce-checkout input[name=\"payment_method\"]:checked').val();
              if (methodVal && methodVal.indexOf('fastspring_') === 0) {
                  var mappedMethod = '';
                  switch (methodVal) {
                      case 'fastspring_paypal': mappedMethod = 'paypal'; break;
                      case 'fastspring_card': mappedMethod = 'card'; break;
                      case 'fastspring_amazon': mappedMethod = 'amazonpay'; break;
                      case 'fastspring_wire': mappedMethod = 'wire'; break;
                      case 'fastspring_googlepay': mappedMethod = 'googlepay'; break;
                  }
                  if (mappedMethod) {
                      fastspring.builder.push({
                          'paymentMethod': mappedMethod
                      });
                  }
              }

              fastspring.builder.secure(e.payload, e.key); 
              fastspring.builder.checkout(); 
          } 
          function setOrder(e) { 
              jQuery.ajax({type: 'POST', url: wc_checkout_params.checkout_url, data: checkoutForm.serialize(), dataType: 'json', success: function(o) { 
                  try { 
                      if (o.result !== 'success') throw o.result === 'failure' ? new Error('Result failure') : new Error('Invalid response'); 
                      e(null, o); 
                  } catch (err) { 
                      if (o.reload === true) return window.location.reload(); 
                      if (o.refresh === true) jQuery(document.body).trigger('update_checkout');
                      o.messages ? submitError(o.messages) : submitError('<div class=\"woocommerce-error\">' + wc_checkout_params.i18n_checkout_error + '</div>'); 
                  } 
              }, error: function(jqXHR, textStatus, errorThrown) { 
                  submitError('<div class=\"woocommerce-error\">' + errorThrown + '</div>'); 
              }}); 
          } 
          function doSubmit() { setLoadingOn(); setOrder(function(err, result) { if (!err) { launchFastSpring(result.session); } }); } 
          function submitError(e) { 
              setLoadingDone(); 
              jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove(); 
              checkoutForm.prepend('<div class=\"woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout\">' + e + '</div>'); 
              checkoutForm.removeClass('processing'); 
              checkoutForm.find('.input-text, select, input:checkbox').trigger('validate').blur(); 
              jQuery('html, body').animate({scrollTop: jQuery('form.checkout').offset().top - 100}, 1e3); 
              jQuery(document.body).trigger('checkout_error'); 
          } 
          function isFastSpringSelected() { 
              var method = jQuery('.woocommerce-checkout input[name=\"payment_method\"]:checked').val();
              return method && method.indexOf('fastspring') === 0;
          }
          checkoutForm.on('checkout_place_order', function() { if (isFastSpringSelected()) { doSubmit(); return false; } });
          
          checkoutForm.on('change', 'input[name=\"payment_method\"]', function() {
              if (isFastSpringSelected()) {
                 checkoutForm.submit();
              }
          });
      ";

    wp_add_inline_script('woocommerce_fastspring_custom', $script);
    wp_enqueue_script('woocommerce_fastspring_custom');
  }
}, 99);