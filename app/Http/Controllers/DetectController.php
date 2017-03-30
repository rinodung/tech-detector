<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Novutec\DomainParser\Parser as DomainParser;
use Novutec\WhoisParser\Parser as WhoisParser;
use Sunra\PhpSimple\HtmlDomParser;

class DetectController extends Controller
{
    public function index(Request $request){
    	$this->validate($request, [
        	'domain' => 'required|max:255|url',        
    	]);
    	$raw_domain = $request->input('domain');
		$domain = preg_replace('#^https?://#', '', $raw_domain);
    	$Parser = new WhoisParser('array'); 
    	$result = $Parser->lookup($domain);
    	$ipv4=gethostbynamel($domain);
        
        $technologies=[];
        $meta_tags=get_meta_tags($raw_domain);

        //dd($meta_tags);
        function url_exists($url) {
            $handle = curl_init($url);
            curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

            $response = curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if($httpCode >= 200 && $httpCode <= 400) {
                return true;
            } else {
                return false;
            }

            curl_close($handle);
        }
        $url=$domain;
        $url=$url."/wp-admin";
        //cms detection
        // ==================
        function has_wp_content($raw_domain){
            //finding an image url to see wp-content exists
            $dom = HtmlDomParser::file_get_html($raw_domain);
            $img_url = $dom->find('img',1)->src;
            $img_url=(array)explode('/', $img_url); 
            foreach ($img_url as $urls) {
                if ($urls=="wp-content") {
                    return true;
                }
            }
            return false;   
        }
        if(url_exists($url) && has_wp_content($raw_domain)){
            $cms="WordPress";
            if (isset($meta_tags['generator'])) {
                $cms = $meta_tags['generator'];
            }

            $technologies['cms']=$cms;
        }else{
            $technologies['cms']="Unable to detect";
        }
        
        //server information
        // =====================
        $headers = get_headers($raw_domain,1);
       
        
        if (isset($headers['Server'])) {
             $server_info['server']=$headers['Server'];
        }else{
            $server_info['server']='';
        }

        if (isset($headers['X-Powered-By'])) {
            $server_info['poweredby']=$headers['X-Powered-By'];
        }else{
            $server_info['poweredby']='';
        }
        

        //programming language
        if ($technologies['cms']=="Wordpress") {
            $technologies['programming_language']="PHP";
        }else{
            $technologies['programming_language']="HTML";
        }

        //$dom = HtmlDomParser::file_get_html($raw_domain);
       // foreach($dom->find('img') as $element) 
         //   echo $element->src . '<br>';       
        
        return view('result',compact('domain','result','server_info', 'ipv4','technologies'));

    	
    }
}
