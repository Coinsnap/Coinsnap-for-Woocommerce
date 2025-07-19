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
            setcookie('coinsnap_notices['.hash('sha256',$content).']',serialize(['type' => $type,'message' => $content, 'dismissible' => $dismissible]),time()+5,'/');
        }
    }
    
    public function showNotices(){
        if(isset($_COOKIE['coinsnap_notices'])){ 
            $coinsnap_notices_array = array_map('sanitize_text_field', wp_unslash($_COOKIE['coinsnap_notices']));
        
            if(count($coinsnap_notices_array)>0){
                foreach($coinsnap_notices_array as $coinsnap_notice){
                    $notice = unserialize($coinsnap_notice);
                    $dismissible_add = ($notice['dismissible'])? ' is-dismissible' : '';
                    echo '<div class="notice notice-'.esc_html($notice['type'].$dismissible_add).'"><p>'.esc_html($notice['message']).'</p></div>';
                }
            }
        }
    }
}
