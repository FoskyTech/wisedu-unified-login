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

// The file created at 2025/01/08 22:54.

namespace FoskyTech\WiseduUnifiedLogin;

use voku\helper\HtmlDomParser;

use FoskyTech\WiseduUnifiedLogin\RequestUtil;
use FoskyTech\WiseduUnifiedLogin\HelperUtil;

class AuthServer {
    protected string $base_uri;
    public function __construct(
        string $base_uri
    ) {
        $this->base_uri = $base_uri;
    }

    /**
     * 直接登录。适用于脚本自动登录获取信息。
     * @param string $username 用户名
     * @param string $password 密码
     * @return array
     */
    public function login(string $username, string $password)
    {
        $res = RequestUtil::post($this->getUrl('/authserver/login'));
        $html = $res['response'];
        $cookie = RequestUtil::get_cookie($res['header']);
        $dom = HtmlDomParser::str_get_html($html);
        $form = $dom->findOne('#pwdFromId');
        $pwdEncryptSalt = $form->findOne('#pwdEncryptSalt')->getAttribute('value');
        $execution = $form->findOne('#execution')->getAttribute('value');
        $data = [
            'username' => $username,
            'password' => HelperUtil::encrypt($password, $pwdEncryptSalt),
            'captcha' => '',
            '_eventId' => 'submit',
            'cllt' => 'userNameLogin',
            'dllt' => 'generalLogin',
            'lt' => '',
            'execution' => $execution
        ];

        if ($this->isNeedCaptcha($username)) {
            // echo '需要验证码';
            return [
                'success' => false,
                'msg' => 'need_captcha'
            ];
        }

        $res = RequestUtil::post($this->getUrl('/authserver/login'), $data, [
            'origin: ' . $this->getUrl(),
            'referer: ' . $this->getUrl('/authserver/login')
        ], $cookie);

        $location = 'Location: ' . $this->getUrl('/authserver/index.do');

        if (strpos($res['header'], $location) > -1 || strpos($res['header'], str_replace('https', 'http', $location)) > -1) {
            $cookie = RequestUtil::get_cookie($res['header']);
            return [
                'success' => true,
                'cookie' => $cookie
            ];
        }

        return [
            'success' => false,
            'msg' => 'unknown'
        ];

    }

    /**
     * 用户手动输入信息登录，captcha 可空。
     * @param array $data [username => , password => , captcha => ]
     * @return array
     */
    public function loginByUser($data)
    {
        $post_data = [
            'username' => $data['username'],
            'password' => HelperUtil::encrypt($data['password'], $data['pwdEncryptSalt']),
            'captcha' => $data['captcha'],
            '_eventId' => 'submit',
            'cllt' => 'userNameLogin',
            'dllt' => 'generalLogin',
            'lt' => '',
            'execution' => $data['execution']
        ];

        $res = RequestUtil::post($this->getUrl('/authserver/login'), $post_data, [
            'origin: ' . $this->getUrl(),
            'referer: ' . $this->getUrl('/authserver/login')
        ], $data['cookie']);

        $location = 'Location: ' . $this->getUrl('/authserver/index.do');

        if (strpos($res['header'], $location) > -1 || strpos($res['header'], str_replace('https', 'http', $location)) > -1) {
            $cookie = RequestUtil::get_cookie($res['header']);
            return [
                'success' => true,
                'cookie' => $cookie
            ];
        }

        return [
            'success' => false,
            'msg' => 'unknown'
        ];

    }

    /**
     * 获取表单数据
     * @return array ['cookie' => , 'pwdEncryptSalt' => , 'execution' => ]
     */
    public function getFormData()
    {
        $res = RequestUtil::post($this->getUrl('/authserver/login'));
        $html = $res['response'];
        $cookie = RequestUtil::get_cookie($res['header']);
        $dom = HtmlDomParser::str_get_html($html);
        $form = $dom->findOne('#pwdFromId');
        $pwdEncryptSalt = $form->findOne('#pwdEncryptSalt')->getAttribute('value');
        $execution = $form->findOne('#execution')->getAttribute('value');
        $data = [
            'cookie' => $cookie,
            'pwdEncryptSalt' => $pwdEncryptSalt,
            'execution' => $execution
        ];

        return $data;
    }
    
    /**
     * 模拟登录第三方服务
     * @param string $service_url 第三方服务登录 url
     * @param string $cookie cookie
     * @return bool|string
     */
    public function serviceLogin(string $service_url, string $cookie)
    {
        $url = $this->getUrl('/authserver/login') . '?service=' . urlencode($service_url);
        $headers = [
            'Upgrade-Insecure-Requests: 1',
            'sec-ch-ua: "Chromium";v="116", "Not)A;Brand";v="24", "Microsoft Edge";v="116"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Pragma: no-cache',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1'
        ];
        $result = RequestUtil::get($url, $headers, $cookie);
        $header = $result['header'];

        $cookie = RequestUtil::get_cookie($header);
        return $cookie;
    }

    /**
     * 登录第三方服务获取 ticket，但不模拟登录到第三方服务
     * @param string $service_url 第三方服务登录 url
     * @param string $cookie cookie
     * @return array ['cookie' => , 'ticket' => ]
     */
    public function serviceLoginGetTicket($service_url, $cookie)
    {
        $url = $this->getUrl('/authserver/login') . '?service=' . urlencode($service_url);
        $headers = [
            'Upgrade-Insecure-Requests: 1'
        ];
        $result = RequestUtil::get($url, $headers, $cookie, false);

        $header = $result['header'];
        $response = $result['response'];

        preg_match("/Location:([^\r\n]*)\?ticket=(.*)/i", $header, $matches);
        $ticket = $matches[2];
        $ticket = str_replace(PHP_EOL, '', $ticket);
        $ticket = str_replace("\r\n", '', $ticket);
        $ticket = str_replace(" ", '', $ticket);
        $ticket = str_replace("\r", '', $ticket);
        $ticket = str_replace("\n", '', $ticket);

        $cookie = RequestUtil::get_cookie($header);

        return [
            'ticket' => $ticket,
            'cookie' => $cookie
        ];
    }

    /**
     * @param string $cookie 已经获取到的 Cookie
     * @return mixed 验证码图片字节流
     */
    public function getCaptcha(string $cookie)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getUrl('/authserver/getCaptcha.htl'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                'x-requested-with: XMLHttpRequest',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.50'
            ],
            CURLOPT_COOKIE => $cookie
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        return $response;
    }

    /**
     * @param string $username 用户名
     * @return bool 是否需要验证码
     */
    public function isNeedCaptcha(string $username): bool
    {
        $res = RequestUtil::post($this->getUrl('/authserver/checkNeedCaptcha.htl') . '?username=' . $username)['response'];
        $data = json_decode($res);
        return $data->isNeed;
    }

    private function getUrl($path = '')
    {
        return $this->base_uri. $path;
    }
}