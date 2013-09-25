<?php
class SecTools {
    
    public static function sanitizeUserFields($value) {
        $baddies = array("PHPSESSID","&","<",">","'",'"',"`");
	$replacers = array("P_H_P_S_E_S_S_I_D","&amp;","&lt;","&gt;","&apos;","&quot;","&#96;");
        return str_ireplace($baddies,$replacers,$value);
    }
    
    public static function _escape($str) {
	$search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
	$replace = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');		
	return str_replace($search, $replace, $str);
    }
    
    public static function sanitizeEmail($email){
        $pattern = '/[^\pL\pN!#$%&\'\*\+\-\/\=\?\^\_`\{\|\}\~\@\.\[\]]/u';
        return preg_replace($pattern, '', $email);
    }
    
    public static function sanitizeInt($val) {
        return (int) $val;
    }
    
    public static function sanitizeNumber($val) {
        $number = preg_replace('/^[^\d+]$/', '', $val);
        return $number;
    }
    
    public static function sanitizeUrl($url) {
        $pattern = '/[^\pL\pN$-_.+!*\'\(\)\,\{\}\|\\\\\^\~\[\]`\<\>\#\%\"\;\/\?\:\@\&\=\.]/u';
        return preg_replace($pattern, '', $url);
    }
    
    public static function emailIsValid($email) {
    if (!strstr($email, '@')) {
        return false;
    }
 
    list($user, $domain) = explode('@', $email);
 
    if ((!strstr($domain, '.'))) {
        return false;
    }
 
    if (preg_match('/[^\x00-\x7F]/', $email)) {
        $user = preg_replace('/[^\x00-\x7F]/', 'X', $user);
 
        if (function_exists('idn_to_ascii')) {
            $domain = idn_to_ascii($domain);
        } else {
            $domain = preg_replace('/[^\x00-\x7F]/', 'X', $domain);
        }
 
        $email = join('@', array($user, $domain));
    }
 
        return (boolean) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /*
     * Random password generator
     * @param string $hashType Hashing method
     * @param string $pass password to hash
     * @param integer $length desired length (optional) 
     * @return string Hashed password for database storage      
     */
    
    public static function generatePass($hashType, $keyFile, $pass, $length = false) {
        $hmac = hash_hmac($hashType, $pass, file_get_contents($keyFile));
        
        if(!$length) {
            $length = 16;
        }else{
            $length = (int) $length;
        }
    
        if(function_exists('openssl_random_pseudo_bytes')){
            $bytes = openssl_random_pseudo_bytes($length);
        }else{
            $bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
        }
    
    $salt = strtr(base64_encode($bytes), '+', '.');
    $salt = substr($salt, 0, 22);
    
    return crypt($hmac, '$2y$12$' . $salt);
    }
    
}

