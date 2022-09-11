<?php
# File: {any-path-you-like}/includes/s3-signed-urls.php
 
# Get the AWS access keys from a non-public server location.

if(!function_exists('el_crypto_hmacSHA1')){
    /**
    * Calculate the HMAC SHA1 hash of a string.
    *
    * @param string $key The key to hash against
    * @param string $data The data to hash
    * @param int $blocksize Optional blocksize
    * @return string HMAC SHA1
    */
    function el_crypto_hmacSHA1($key, $data, $blocksize = 64) {
        if (strlen($key) > $blocksize) $key = pack('H*', sha1($key));
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack( 'H*', sha1(
            ($key ^ $opad) . pack( 'H*', sha1(
                ($key ^ $ipad) . $data
            ))
        ));
        return base64_encode($hmac);
    }
}
 
if(!function_exists('getSignedUrl')){
    /**
    * Create signed URLs to your protected Amazon S3 files.
    *
    * @param string $awsAccessKey Your Amazon S3 access key
    * @param string $secretKey Your Amazon S3 secret key
    * @param string $bucket The bucket (mybucket.s3.amazonaws.com)
    * @param string $objectPath The target file path
    * @param int $expires In minutes
    * @param array $customParams Key value pairs of custom parameters
    * @return string Temporary signed Amazon S3 URL
    * @see http://awsdocs.s3.amazonaws.com/S3/20060301/s3-dg-20060301.pdf
    */
    function getSignedUrl($url, $awsAccessKey, $secretKey, $bucket, $objectPath, $expires = 0, $customParams = array()) {
         
        # Calculate the expire time.
        $expires = time() + intval(floatval($expires) + 1);
         
        # Clean and url-encode the object path.
        $objectPath = str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode( ltrim($objectPath, '/') ) );
         
        # Create the object path for use in the signature.
        $objectPathForSignature = '/'. $bucket .'/'. $objectPath;
         
        # Create the S3 friendly string to sign.
        $stringToSign = implode("\n", $pieces = array('GET', null, null, $expires, $objectPathForSignature));
         
        # Create the URL frindly string to use.
        $url = "$url/$bucket/$objectPath";
         
        # Custom parameters.
        $appendCharacter = '?'; // Default append character.
         
        # Loop through the custom query paramaters (if any) and append them to the string-to-sign, and to the URL strings.
        if(!empty( $customParams )){
                foreach ($customParams as $paramKey => $paramValue) {
                        $stringToSign .= $appendCharacter . $paramKey . '=' . $paramValue;
                        $url .= $appendCharacter . $paramKey . '=' . str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode( ltrim($paramValue, '/') ) );
                        $appendCharacter = '&';
                }
        }
         
        # Hash the string-to-sign to create the signature.
        $signature = el_crypto_hmacSHA1($secretKey, $stringToSign);
         
        # Append generated AWS parameters to the URL.
        $queries = http_build_query($pieces = array(
            'AWSAccessKeyId' => $awsAccessKey,
            'Expires' => $expires,
            'Signature' => $signature,
        ));
        $url .= $appendCharacter .$queries;
         
        # Return the URL.
        return $url;
         
    }
}
 
if(!function_exists('upload_s3')){
    function upload_s3($url, $awsAccessKey, $secretKey, $bucket, $objectPath, $fPath) {
        // AWS region and Host Name (Host names are different for each AWS region)
        $host_name = 'us-east-1.wasabisys.com';


        $url = "$url/$bucket/$objectPath";

        // Server path where content is present. This is just an example

        // AWS file permissions
        $content_acl = 'authenticated-read';

        $content = file_get_contents($fPath);

        // Name of content on S3
        $content_title = $objectPath;

        // Service name for S3
        $aws_service_name = 's3';

        // UTC timestamp and date
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        // HTTP request headers as key & value
        $request_headers = array();
        $request_headers['Date'] = $timestamp;
        $request_headers['Host'] = $host_name;
        $request_headers['x-amz-acl'] = $content_acl;
        $request_headers['x-amz-content-sha256'] = hash('sha256', $content);
        // Sort it in ascending order
        ksort($request_headers);

        // Canonical headers
        $canonical_headers = [];
        foreach($request_headers as $key => $value) {
            $canonical_headers[] = strtolower($key) . ":" . $value;
        }
        $canonical_headers = implode("\n", $canonical_headers);

        // Signed headers
        $signed_headers = [];
        foreach($request_headers as $key => $value) {
            $signed_headers[] = strtolower($key);
        }
        $signed_headers = implode(";", $signed_headers);

        // Cannonical request 
        $canonical_request = [];
        $canonical_request[] = "PUT";
        $canonical_request[] = "/" . $content_title;
        $canonical_request[] = "";
        $canonical_request[] = $canonical_headers;
        $canonical_request[] = "";
        $canonical_request[] = $signed_headers;
        $canonical_request[] = hash('sha256', $content);
        $canonical_request = implode("\n", $canonical_request);
        $hashed_canonical_request = hash('sha256', $canonical_request);

        // AWS Scope
        $scope = [];
        $scope[] = $date;
        $scope[] = $aws_region;
        $scope[] = $aws_service_name;
        $scope[] = "aws4_request";

        // String to sign
        $string_to_sign = [];
        $string_to_sign[] = "AWS4-HMAC-SHA256"; 
        $string_to_sign[] = $timestamp; 
        $string_to_sign[] = implode('/', $scope);
        $string_to_sign[] = $hashed_canonical_request;
        $string_to_sign = implode("\n", $string_to_sign);

        // Signing key
        $kSecret = 'AWS4' . $secretKey;
        $kDate = hash_hmac('sha256', $date, $kSecret, true);
        $kRegion = hash_hmac('sha256', $aws_region, $kDate, true);
        $kService = hash_hmac('sha256', $aws_service_name, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        // Signature
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);

        // Authorization
        $authorization = [
            'Credential=' . $awsAccessKey . '/' . implode('/', $scope),
            'SignedHeaders=' . $signed_headers,
            'Signature=' . $signature
        ];
        $authorization = 'AWS4-HMAC-SHA256' . ' ' . implode( ',', $authorization);

        // Curl headers
        $curl_headers = [ 'Authorization: ' . $authorization ];
        foreach($request_headers as $key => $value) {
            $curl_headers[] = $key . ": " . $value;
        }

        echo "$url";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200) 
            exit('Error : Failed to upload');
        return "";
    }
}







?>
