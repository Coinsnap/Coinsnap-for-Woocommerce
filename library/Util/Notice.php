<?php
declare(strict_types=1);
namespace Coinsnap\Util;

if (!defined('ABSPATH')) {
    exit;
}

class Notice {
   
    public function addNotice(string $type, string $content, $dismissible = 1){
        $notice_types_array = ['error','warning','success','info'];
        if(in_array($type,$notice_types_array)){ $type = 'info'; }
        if(!empty($content)){
            $_SESSION['notices'][] = ['type' => $type,'message' => $content, 'dismissible' => $dismissible];
        }
    }
    
    public function showNotices(){
        if(isset($_SESSION['notices']) && count($_SESSION['notices'])>0){
            foreach($_SESSION['notices'] as $notice){
                $dismissible_add = ($notice['dismissible'])? ' is-dismissible' : '';
                echo '<div class="notice notice-'.esc_html($notice['type'].$dismissible_add).'"><p>'.esc_html($notice['message']).'</p></div>';
            }
            unset($_SESSION['notices']);
        }
    }
}
