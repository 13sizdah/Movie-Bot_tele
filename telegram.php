<?php

class telegram
{
    public $token;
    public $db;
    public $query;
    public $queryResult;
    public function __construct($token, $host, $username, $password, $dbname)
    {
        $this->token = $token;
        try {
            $this->db = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci")
            );
        } catch (PDOException $error) {
            echo "Unable to connect to database.";
        }
    }
    public function __destruct()
    {
        $this->db = null;
    }
    // get users msg + info
    public function getTxt()
    {
        $text = json_decode(file_get_contents('php://input'));
        return $text;
    }
    // send message to specific user
    public function sendMessage($userid, $text)
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/sendMessage?chat_id=' . $userid . '&text=' . $text;
        file_get_contents($url);
    }
    public function sendMessageCURL($userid, $text, $options)
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/sendMessage';
        $keyboard = $this->makeMenu($options);
        $postfields = array(
            'chat_id' => $userid,
            'text' => $text,
            //'parse_mode' => 'HTML'
            'reply_markup' => $keyboard
        );
        $this->executeCURL($url, $postfields);
    }

    public function sendHTML($userid, $text, $options)
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/sendMessage';
        $keyboard = $this->makeMenu($options);
        $postfields = array(
            'chat_id' => $userid,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        );
        $this->executeCURL($url, $postfields);
    }
    public function makeMenu($options)
    {
        $keyboard = array(
            'keyboard' => $options,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
            'selective' => true
        );
        return $keyboard = json_encode($keyboard);
    }

    public function getChatMember($channel, $user_id)
    {
        $get = $this->exeCURL('getChatMember', [
            'chat_id' => $channel,
            'user_id' => $user_id
        ]);
        $data = $get->result->status;
        return $data;
    }
    public function deleteMessage($chatid, $msgid)
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/deleteMessage?chat_id=' . $chatid . '&message_id=' . $msgid;
        file_get_contents($url);
    }
    public function executeCURL($url, $postfields)
    {
        $ch = curl_init();
        //$timeout = 100 ; // 10 seconds
        // rawurlencode for sanitizing malicius inputs
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: multipart/form-data"
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        //curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $contents = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($contents, true);
        return $output;
    }
    public function exeCURL($method, $datas = [])
    {
        $url = "https://api.telegram.org/bot" . TOKEN . "/" . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datas));
        $res = curl_exec($ch);
        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        } else {
            return json_decode($res);
        }
    }
}


function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . TOKEN . "/" . $method;
    
    // اگر reply_markup وجود دارد، از executeCURL استفاده می‌کنیم (مشابه sendMessageCURL)
    // که می‌دانیم برای reply_markup کار می‌کند
    if (isset($datas['reply_markup'])) {
        global $telegram;
        if (!isset($telegram)) {
            $telegram = new telegram(TOKEN, HOST, USERNAME, PASSWORD, DBNAME);
        }
        $result = $telegram->executeCURL($url, $datas);
        // executeCURL یک array برمی‌گرداند، اما بقیه کد object می‌خواهد
        if ($result === null || empty($result)) {
            return null;
        }
        return json_decode(json_encode($result));
    } else {
        // برای فیلدهای عادی از http_build_query استفاده می‌کنیم (مشابه exeCURL)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datas));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        if (curl_error($ch)) {
            error_log("Telegram API Error: " . curl_error($ch));
            curl_close($ch);
            return null;
        } else {
            curl_close($ch);
            $decoded = json_decode($res);
            if (!$decoded) {
                error_log("Telegram API: Failed to decode JSON response for method $method. Response: " . substr($res, 0, 500));
                return null;
            }
            if (isset($decoded->ok) && !$decoded->ok) {
                error_log("Telegram API Response Error for method $method: " . (isset($decoded->description) ? $decoded->description : 'Unknown error') . " | Response: " . substr($res, 0, 500));
            }
            return $decoded;
        }
    }
}


function get_type($id)
{
    $url = "https://api.telegram.org/bot" . TOKEN . "/getFile?file_id=$id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}
