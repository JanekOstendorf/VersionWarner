<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner;


abstract class HttpVersionProvider extends VersionProvider
{

    const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';

    /**
     * Query an HTTP endpoint and return the result as string
     *
     * @param string $url The URL to query
     * @param string $userAgent The user agent string to use for the query. Defaults to Mozilla/5.0 [...]
     * @return string The HTTP request result
     */
    protected static function queryHttp(string $url, string $userAgent = self::DEFAULT_USER_AGENT): string
    {
        // see https://stackoverflow.com/a/11680776
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        // return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // $output contains the output string
        $output = curl_exec($ch);

        if ($output === false) {
            throw new \RuntimeException("Error fetching HTTP response: " . curl_error($ch), curl_errno($ch));
        }

        // close curl resource to free up system resources
        curl_close($ch);

        return $output;
    }

}