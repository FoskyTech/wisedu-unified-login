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

// The file created at 2025/01/08 23:03.

namespace FoskyTech\WiseduUnifiedLogin;

class HelperUtil {
    static public function encrypt(string $pass, string $key): string
    {
        $encrypted = openssl_encrypt(self::random_string(64) . $pass, 'AES-128-CBC', $key, 0, self::random_string(16));

        return $encrypted;
    }
    static public function random_string($length = 64, $chars = null): string
    {
        $s = '';
        if (empty($chars))
            $chars = "ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678";
        while (strlen($s) < $length)
            $s .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        return $s;
    }
}