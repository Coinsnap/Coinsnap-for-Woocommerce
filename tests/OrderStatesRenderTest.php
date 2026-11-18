<?php
// Standalone test (no WordPress needed): php tests/OrderStatesRenderTest.php
// The order states field must render without PHP warnings before the merchant has ever saved
// the settings (option coinsnap_order_states missing, get_option() returns false).
declare(strict_types=1);
namespace {
    if (PHP_SAPI !== 'cli') { exit; }
    $GLOBALS['t_options'] = [];
    function _x($text, $context = '', $domain = '') { return $text; }
    function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
    function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
    function get_option($name, $default = false) { return $GLOBALS['t_options'][$name] ?? $default; }
    function wc_get_order_statuses() {
        return ['wc-pending' => 'Pending payment', 'wc-on-hold' => 'On hold', 'wc-completed' => 'Completed', 'wc-cancelled' => 'Cancelled'];
    }

    require __DIR__ . '/../includes/Helper/OrderStates.php';

    $failures = 0;
    $problems = [];
    set_error_handler(function ($no, $str, $file, $line) use (&$problems) {
        $problems[] = "$str ($file:$line)";
        return true;
    });

    $render = function (): string {
        ob_start();
        (new \Coinsnap\WC\Helper\OrderStates())->renderOrderStatesHtml(['id' => 'coinsnap_order_states']);
        return (string) ob_get_clean();
    };

    // 1. Option never saved: no warnings, defaults are selected.
    $html = $render();
    if ($problems !== []) {
        $failures++;
        echo "FAIL missing option produces PHP warnings:\n  " . implode("\n  ", array_unique($problems)) . "\n";
    } else {
        echo "PASS missing option renders without warnings\n";
    }
    $expect = ['Settled' => 'wc-completed', 'Expired' => 'wc-cancelled', 'Processing' => 'wc-on-hold', 'New' => 'wc-pending'];
    foreach ($expect as $state => $wc) {
        $ok = (bool) preg_match('#name="coinsnap_order_states\[' . $state . '\]">.*?<option value="' . $wc . '" selected>#s', $html);
        $failures += $ok ? 0 : 1;
        echo ($ok ? 'PASS' : 'FAIL') . " default for $state is $wc\n";
    }

    // 2. Saved mapping is shown; a state missing from the saved option falls back to its default.
    $problems = [];
    $GLOBALS['t_options']['coinsnap_order_states'] = ['Settled' => 'wc-on-hold'];
    $html = $render();
    $ok = $problems === [] && preg_match('#name="coinsnap_order_states\[Settled\]">.*?<option value="wc-on-hold" selected>#s', $html)
        && preg_match('#name="coinsnap_order_states\[Expired\]">.*?<option value="wc-cancelled" selected>#s', $html);
    $failures += $ok ? 0 : 1;
    echo ($ok ? 'PASS' : 'FAIL') . " saved mapping kept, partial option falls back to defaults" . ($problems ? ': ' . implode('; ', array_unique($problems)) : '') . "\n";

    echo $failures === 0 ? "OK\n" : "$failures failure(s)\n";
    exit($failures === 0 ? 0 : 1);
}
