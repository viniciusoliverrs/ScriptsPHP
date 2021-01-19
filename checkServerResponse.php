<?php
function checkServerResponse($url)
{
    // create curl resource
    $ch = curl_init();
    // set url
    curl_setopt($ch, CURLOPT_URL, $url);
    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //enable headers
    curl_setopt($ch, CURLOPT_HEADER, 1);
    //get only headers
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    // $output contains the output string
    $output = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // close curl resource to free up system resources
    curl_close($ch);

    return $status == 200 ? "[ON]" : "[OFF]";
}

echo checkServerResponse("https://www.google.com.br");