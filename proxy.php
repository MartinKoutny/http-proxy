<?php
  define('PROXY_SOURCE', 'maxiakce.cz');
  define('PROXY_TARGET', 'cochy.cz');

  define('CACHE_FILE', 'cache/'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
  define('CACHE_TYPE', 'jpg,png,gif,ico,css,js');

  // create a new cURL resource
  $ch = curl_init(str_ireplace(PROXY_TARGET, PROXY_SOURCE, $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));

  // set appropriate options
  curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  if($_SERVER['REQUEST_METHOD'] == 'POST')
  {
    foreach($_FILES as $name => $file) if($file['error'] == UPLOAD_ERR_OK) $files[$name] = '@'.$file['tmp_name'];
    if(count($files)) foreach($_POST as $key => $value) if(strncmp('@', $value, 1) == 0) unset($_POST[$key]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, count($files) ? array_merge($files, $_POST) : http_build_query($_POST, '', '&'));
  }
  curl_setopt($ch, CURLOPT_COOKIE, http_build_query($_COOKIE, '', '; '));
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_NOBODY, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);

  // grab URL and close resource
  $response = str_ireplace(PROXY_SOURCE, PROXY_TARGET, curl_exec($ch)) or die(curl_error($ch));
  curl_close($ch);

  // parse response
  if(count($files)) list($continue, $head, $body) = explode("\r\n\r\n", $response, 3);
  else list($head, $body) = explode("\r\n\r\n", $response, 2);

  // pass head and body to the browser
  foreach(explode("\r\n", $head) as $header) if(strncasecmp('Transfer-Encoding', $header, 17)) header($header, false);
  echo $body;

  // Save cache
  if(in_array(strtolower(pathinfo(CACHE_FILE, PATHINFO_EXTENSION)), explode(',', CACHE_TYPE)))
  {
    mkdir(pathinfo(urldecode(CACHE_FILE), PATHINFO_DIRNAME), 0777, true);
    file_put_contents(urldecode(CACHE_FILE), $body);
  }

  // Write log
  if($fp = fopen('access.html', 'a'))
  {
    fwrite($fp, date('r').' - <b>'.$_SERVER['REMOTE_ADDR'].'</b> - '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."<br />\n");
    fclose($fp);
  }
?>