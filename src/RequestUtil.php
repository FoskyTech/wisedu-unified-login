<?php

// Copyright (C) 2025 FoskyM<i@fosky.top>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// The file created at 2025/01/08 22:57, copied from my private repo `cjlu-cp-v3`.

namespace FoskyTech\WiseduUnifiedLogin;

class RequestUtil
{
    /**
     * @param string $url 请求地址
     * @param string $method 请求方式
     * @param array $param 请求参数
     * @param array $headers 请求头
     * @param string $cookie 请求cookie
     * @param bool $follow 是否跟随重定向
     * @param bool $json 是否发送json数据
     * @return array|bool 请求结果
     */
    static public function request(string $url, string $method = 'POST', array $param = [], array $headers = [], string $cookie = '', $follow = true, $json = false)
    {
        $curl = curl_init();

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $json ? json_encode($param) : http_build_query($param),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array_merge([
                'x-requested-with: XMLHttpRequest',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.50'
            ], $headers),
        ];

        if (!empty($cookie))
            $opt[CURLOPT_COOKIE] = $cookie;

        if ($follow) {
            $opt[CURLOPT_FOLLOWLOCATION] = true;
            $opt[CURLOPT_MAXREDIRS] = 10;
        }

        curl_setopt_array($curl, $opt);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $response = substr($response, $header_size - 1, strlen($response));

        curl_close($curl);

        if ($err) {
            // echo "cURL Error #:" . $err;
            return false;
        }

        return [
            'response' => $response,
            'header' => $header
        ];
    }

    /**
     * @param string $url 请求地址
     * @param array $headers 请求头
     * @param string $cookie 请求cookie
     * @param bool $follow 是否跟随重定向
     * @param bool $json 是否发送json数据
     * @return array|bool 请求结果
     */
    static public function get($url, $headers = [], $cookie = '', $follow = true, $json = false)
    {
        return self::request($url, 'GET', [], $headers, $cookie, $follow, $json);
    }

    /**
     * @param string $url 请求地址
     * @param array $param 请求参数
     * @param array $headers 请求头
     * @param string $cookie 请求cookie
     * @param bool $follow 是否跟随重定向
     * @param bool $json 是否发送json数据
     * @return array|bool 请求结果
     */
    static public function post($url, $param = [], $headers = [], $cookie = '', $follow = true, $json = false)
    {
        return self::request($url, 'POST', $param, $headers, $cookie, $follow, $json);
    }

    /**
     * @param string $header 请求头
     * @return string|bool cookie
     */
    static public function get_cookie($header)
    {
        if ($header == '' || empty($header))
            return false;
        $preg = '/Set-Cookie:\ (.*?);/';
        preg_match_all($preg, $header, $result);
        $arr = $result[1];
        $cookie = '';
        for ($i = 0; $i < count($arr); $i++)
            $cookie .= $arr[$i] . ';';
        return $cookie;
    }

    /**
     * @param string $header 请求头
     * @return string|bool location
     */
    static public function get_location($header)
    {
        if ($header == '' || empty($header))
            return false;
        if (preg_match("/Location:([^\r\n]*)/i", $header, $matches)) {
            $location = $matches[1];
            $location = str_replace(PHP_EOL, '', $location);
            $location = str_replace("\r\n", '', $location);
            $location = str_replace(" ", '', $location);
            $location = str_replace("\r", '', $location);
            $location = str_replace("\n", '', $location);
            return $location;
        }
        return false;
    }
    
    /**
     * @param string $name cookie名
     * @param string $cookie cookie
     * @return string|bool session_id
     */
    static public function get_cookie_by_name($name, $cookie)
    {
        if ($cookie == '' || empty($cookie))
            return false;
        $preg = '/' . $name . '=(.*?);/';
        if (!preg_match($preg, $cookie, $result))
            return false;
        return $result[1];
    }
}