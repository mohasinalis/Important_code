<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class push_notification extends CI_Controller {

    function __construct() {
        parent::__construct();
    }

    public function index() {

        $query = $this->db->select("first_name,device_type,test_token as device_token2")
                ->where("test_token!=", "0")
                ->where("test_token!=", "")
                ->get("users");
        $admin_notification_list = $query->result();
        //   pr($admin_notification_list, 1);


        $anroid_device_bundle = array();
        $ios_device_bundle = array();
        foreach ($admin_notification_list as $row) {
            $message_notify = array(
                "na" => "1",
                "jhr" => "1",
                "acj" => "12",
                "type" => "acj",
                "msg" => "Accepted By $row->first_name",
                "title" => "MSN",
            );
            if ($row->device_type == 'android' && $row->device_token2 != '') {
                $anroid_device_bundle[] = array(
                    "device_token" => $row->device_token2, //device_token
                    "message" => $message_notify, //device_token
                );
            } elseif ($row->device_type == 'iOS' && $row->device_token2 != '') {
                $ios_device_bundle[] = array(
                    "device_token" => $row->device_token2, //device_token
                    "message" => $message_notify, //device_token
                );
            }
        }
        // pr($ios_device_bundle, 1);
        $no_of_notification = 1000; // set the max limit it's 1000 each request
        $sendnoti_perrequest = $no_of_notification;

        // Bulk notification send to ios one max limit 1000
        if (!empty($ios_device_bundle)) {
            $ios_count = sizeof($ios_device_bundle);
            $ios_loop = max(ceil($ios_count / $sendnoti_perrequest), 1);
            $k = 0;
            $j = 0;
            $noofreq = $no_of_notification;

            for ($i = 0; $i < $ios_loop; $i++) {
                unset($split_devicetoken_ios);
                for ($j = $k; $j < $noofreq; $j++) {
                    if (isset($ios_device_bundle[$j])):
                        $split_devicetoken_ios[] = $ios_device_bundle[$j];
                    endif;
                }
                $this->send_notification_to_ios($split_devicetoken_ios);
                $k = $j;
                $noofreq = $noofreq + $sendnoti_perrequest;
            }
        }
        // Bulk notification send to Android one max limit 1000
        if (!empty($anroid_device_bundle)) {
            $android_count = sizeof($anroid_device_bundle);
            $android_loop = max(ceil($android_count / $sendnoti_perrequest), 1);
            $k = 0;
            $j = 0;
            $noofreq = $no_of_notification;
            for ($i = 0; $i < $android_loop; $i++) {
                unset($split_devicetoken_android);
                for ($j = $k; $j < $noofreq; $j++) {
                    if (isset($anroid_device_bundle[$j])):
                        $split_devicetoken_android[] = $anroid_device_bundle[$j];
                    endif;
                }
                $this->send_notification_to_android($split_devicetoken_android);
                $k = $j;
                $noofreq = $noofreq + $sendnoti_perrequest;
            }
        }
    }

    public function send_notification_to_ios($registatoin_ids) {

        $registatoin_ids_array = $registatoin_ids;
        $i = 1;
        repeat:
        // Put your private key's passphrase here:  
        $passphrase = 'mohasinali';
        // Put your alert message here:
        //This message will popup on user device
        $ctx = stream_context_create();
        //stream_context_set_option($ctx, 'ssl', 'local_cert', '/var/www/html/project/assets/NewBuild_sandbox.pem');  // for staging
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'D:\xampp\htdocs\project\assets\NewBuild_sandbox.pem'); // For local
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        // Open a connection to the APNS server
        $fp = @stream_socket_client(
                        'ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx); // for staging
        // 'ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx); 
        //  $fp = @stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);  // for live

        if ($fp) {
            foreach ($registatoin_ids_array as $row) {
                // Create the payload body
                $body['aps'] = array(
                    'alert' => $row['message']['msg'],
                    'info' => $row['message'],
                    'sound' => 'default',
                    'badge' => 0,
                );
                $registatoin_ids = $row['device_token'];
                //}
                //$body['server'] = $notifArr;
                // Encode the payload as JSON


                $payload = json_encode($body);
                // Build the binary notification
                $msg = chr(0) . pack('n', 32) . pack('H*', $registatoin_ids) . pack('n', strlen($payload)) . $payload;

                $inner = chr(1)
                        . pack('n', 32)
                        . pack('H*', $registatoin_ids)
                        . chr(2)
                        . pack('n', strlen($payload))
                        . $payload
                        . chr(3)
                        . pack('n', 4)
                        . pack('N', $registatoin_ids)
                        . chr(5)
                        . pack('n', 1)
                        . chr(10);

                $notification = chr(2)
                        . pack('N', strlen($inner))
                        . $inner;
                error_reporting(0);
                // Send it to the server
                $result = fwrite($fp, $msg, strlen($msg));
                if ($result == 0 && $i < 3) {
                    fclose($fp);
                    sleep(5);  // 5 second stop
                    $i++;
                    goto repeat;
                }
                //return $result;
            }
        } else {
            return 'Hello World';
        }
        // Close the connection to the server
        fclose($fp);
    }

    public function send_notification_to_android($registatoin_ids) {
        $registatoin_ids_array = $registatoin_ids;
        foreach ($registatoin_ids_array as $row) {
            $registrationIds = array($row['device_token']);
            $response['notification'] = $row['message'];
            $result = json_encode($response);
            $msg = array
                (
                "body" => $row['message']['msg'],
                "title" => "MSN",
                "vibrate" => 1,
                "sound" => "/res/raw/beep.wav",
                "click_action" => "OPEN_ACTIVITY_1",
            );

            $data = array
                (
                'body' => $result,
                'title' => 'MSN',
                'vibrate' => 1,
                'sound' => 1,
                "click_action" => "OPEN_ACTIVITY_1",
            );

            $fields = array
                (
                'registration_ids' => $registrationIds,
                'notification' => $msg,
                'data' => $data
            );


            $headers = array
                (
                'Authorization: key=' . '--server key--',
                'Content-Type: application/json'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            // return $result;
        }
    }

    public function send_notification_to_android_main($registatoin_ids, $message_notify) {
        $registrationIds = array($registatoin_ids);
        $response['notification'] = $message_notify;
        $result = json_encode($response);
        $msg = array
            (
            "body" => $message_notify["msg"],
            "title" => "MSN",
            "vibrate" => 1,
            "sound" => "/res/raw/beep.wav",
            "click_action" => "OPEN_ACTIVITY_1",
        );

        $data = array
            (
            'body' => $result,
            'title' => 'MSN',
            'vibrate' => 1,
            'sound' => 1,
            "click_action" => "OPEN_ACTIVITY_1",
        );

        $fields = array
            (
            'registration_ids' => $registrationIds,
            'notification' => $msg,
            'data' => $data
        );


        $headers = array
            (
            'Authorization: key=' . '--server key--',
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
